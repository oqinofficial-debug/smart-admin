<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jf_model
 *
 * CRUD master JF (mst_jf) + kelola snapshot kemunculan JF per periode
 * (trx_jf_periode), yang jadi dasar halaman "JF aktif per periode".
 */
class Jf_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ---------------------------------------------------------------
    // CRUD master JF
    // ---------------------------------------------------------------

    public function get_all($only_active = false)
    {
        $this->db->select('j.*, k.nama AS kelompok_produk_nama')
                  ->from('mst_jf j')
                  ->join('mst_kelompok_produk k', 'k.id = j.kelompok_produk_id', 'left')
                  ->order_by('j.jf', 'ASC');
        if ($only_active) {
            $this->db->where('j.status_jf', 'AKTIF');
        }
        return $this->db->get()->result_array();
    }

    public function get($id)
    {
        return $this->db->where('id', $id)->get('mst_jf')->row_array();
    }

    /**
     * Cek apakah kode JF sudah dipakai (untuk validasi unik di form),
     * $exclude_id dipakai saat edit supaya JF itu sendiri tidak kehitung.
     */
    public function get_by_jf($jf, $exclude_id = null)
    {
        $this->db->where('jf', $jf);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get('mst_jf')->row_array();
    }

    public function create(array $data)
    {
        $this->db->insert('mst_jf', $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('id', $id)->update('mst_jf', $data);
        return $this->db->affected_rows();
    }

    /**
     * Tandai JF final -- setelah ini JF tidak lagi muncul di daftar
     * "JF aktif", walaupun baris trx_jf_periode lama tetap disimpan
     * sebagai histori (mekanisme pemicunya belum dibuat, method ini
     * cuma "tombol"-nya).
     */
    public function set_final($id)
    {
        $this->db->where('id', $id)->update('mst_jf', array('status_jf' => 'FINAL'));
        return $this->db->affected_rows();
    }

    /**
     * Hard delete master JF. Ditolak DB (FK RESTRICT) kalau JF ini masih
     * dipakai di trx_laporan_produksi -- baris trx_jf_periode ikut terhapus
     * otomatis (ON DELETE CASCADE) karena itu cuma snapshot turunan.
     */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id)->delete('mst_jf');
            return array('success' => true, 'message' => 'JF dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, JF ini masih dipakai di data laporan produksi.',
            );
        }
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel). Tiap elemen $rows
     * adalah array kolom mentah hasil parse_bulk_paste():
     * [jf, product?, qty?, bapob?, chip?, customer?, po?, kelompok_produk(nama)?, status_jf?]
     *
     * Kalau kode JF SUDAH ADA, baris yang bersangkutan DI-REPLACE (semua
     * kolom yang diisi ditimpa) -- beda dari add() biasa yang menolak kode
     * JF duplikat. Kalau belum ada, jadi baris baru (status_jf default
     * AKTIF kalau kolom status kosong).
     *
     * Kolom "Kelompok Produk" diisi NAMA (bukan id), dicocokkan ke
     * mst_kelompok_produk; kalau namanya tidak ditemukan baris itu gagal
     * (bukan di-null-kan diam-diam, supaya salah ketik ketahuan).
     * Kolom status_jf pada baris REPLACE dibiarkan kalau kosong (tidak
     * menimpa status FINAL yang sudah ditandai lewat tombol "Jadikan Final"),
     * kecuali diisi eksplisit AKTIF/FINAL di teks tempelan.
     *
     * @return array array('inserted'=>int, 'updated'=>int,
     *               'errors'=>array(array('line'=>int, 'message'=>string)))
     */
    public function bulk_upsert(array $rows)
    {
        $inserted = 0;
        $updated  = 0;
        $errors   = array();

        foreach ($rows as $i => $cols) {
            $line       = $i + 1;
            $jf         = isset($cols[0]) ? trim($cols[0]) : '';
            $product    = isset($cols[1]) ? trim($cols[1]) : '';
            $qty_raw    = isset($cols[2]) ? trim($cols[2]) : '';
            $bapob      = isset($cols[3]) ? trim($cols[3]) : '';
            $chip       = isset($cols[4]) ? trim($cols[4]) : '';
            $customer   = isset($cols[5]) ? trim($cols[5]) : '';
            $po         = isset($cols[6]) ? trim($cols[6]) : '';
            $kp_nama    = isset($cols[7]) ? trim($cols[7]) : '';
            $status_raw = isset($cols[8]) ? strtoupper(trim($cols[8])) : '';

            if ($jf === '') {
                $errors[] = array('line' => $line, 'message' => 'Kode JF wajib diisi.');
                continue;
            }
            if (mb_strlen($jf) > 50) {
                $errors[] = array('line' => $line, 'message' => 'Kode JF melebihi 50 karakter.');
                continue;
            }
            if ($qty_raw !== '' && !is_numeric(str_replace(',', '.', $qty_raw))) {
                $errors[] = array('line' => $line, 'message' => 'Qty harus angka (dapat: "' . $qty_raw . '").');
                continue;
            }
            if ($status_raw !== '' && !in_array($status_raw, array('AKTIF', 'FINAL'), true)) {
                $errors[] = array('line' => $line, 'message' => 'Status JF harus AKTIF atau FINAL (dapat: "' . $status_raw . '").');
                continue;
            }

            $kelompok_produk_id = null;
            if ($kp_nama !== '') {
                $kp = $this->db->get_where('mst_kelompok_produk', array('nama' => $kp_nama))->row_array();
                if (!$kp) {
                    $kp = $this->db->query(
                        'SELECT id FROM mst_kelompok_produk WHERE LOWER(nama) = LOWER(?) LIMIT 1',
                        array($kp_nama)
                    )->row_array();
                }
                if (!$kp) {
                    $errors[] = array('line' => $line, 'message' => 'Kelompok Produk "' . $kp_nama . '" tidak ditemukan.');
                    continue;
                }
                $kelompok_produk_id = $kp['id'];
            }

            $data = array(
                'product'            => $product !== '' ? $product : null,
                'qty'                => $qty_raw === '' ? null : (float) str_replace(',', '.', $qty_raw),
                'bapob'              => $bapob !== '' ? $bapob : null,
                'chip'               => $chip !== '' ? $chip : null,
                'customer'           => $customer !== '' ? $customer : null,
                'po'                 => $po !== '' ? $po : null,
                'kelompok_produk_id' => $kelompok_produk_id,
            );

            $existing = $this->get_by_jf($jf);

            try {
                if ($existing) {
                    if ($status_raw !== '') {
                        $data['status_jf'] = $status_raw;
                    }
                    $this->db->where('id', $existing['id'])->update('mst_jf', $data);
                    $updated++;
                } else {
                    $data['jf']        = $jf;
                    $data['status_jf'] = $status_raw !== '' ? $status_raw : 'AKTIF';
                    $this->db->insert('mst_jf', $data);
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = array('line' => $line, 'message' => 'Gagal simpan: ' . $e->getMessage());
            }
        }

        return array('inserted' => $inserted, 'updated' => $updated, 'errors' => $errors);
    }

    // ---------------------------------------------------------------
    // Snapshot JF per periode (trx_jf_periode)
    // ---------------------------------------------------------------

    /**
     * Upsert kemunculan JF di suatu periode. Dipanggil dari proses import
     * SETELAH insert baris laporan produksi berhasil, dengan kumpulan
     * pasangan (jf_id, periode) yang SUDAH di-unik-kan -- bukan query
     * per baris data mentah.
     *
     * @param array $pairs [['jf_id' => 1, 'periode' => '2026-07'], ...]
     */
    public function sync_periode(array $pairs)
    {
        if (empty($pairs)) {
            return;
        }

        $unique = array();
        foreach ($pairs as $p) {
            if (empty($p['jf_id']) || empty($p['periode'])) {
                continue;
            }
            $unique[$p['jf_id'] . '|' . $p['periode']] = $p;
        }

        $sql = "INSERT INTO trx_jf_periode (jf_id, periode, first_seen_at, last_seen_at)
                VALUES (?, ?, now(), now())
                ON CONFLICT (jf_id, periode)
                DO UPDATE SET last_seen_at = now()";

        foreach ($unique as $p) {
            $this->db->query($sql, array($p['jf_id'], $p['periode']));
        }
    }

    /**
     * Daftar JF aktif (status_jf = AKTIF) yang terdeteksi pada periode
     * tertentu -- untuk halaman "list JF aktif per periode".
     */
    public function get_active_by_periode($periode)
    {
        return $this->db->select('j.id, j.jf, j.product, j.customer, j.po, j.status_jf, p.periode, p.first_seen_at, p.last_seen_at')
                          ->from('trx_jf_periode p')
                          ->join('mst_jf j', 'j.id = p.jf_id')
                          ->where('p.periode', $periode)
                          ->where('j.status_jf', 'AKTIF')
                          ->order_by('j.jf', 'ASC')
                          ->get()
                          ->result_array();
    }
}