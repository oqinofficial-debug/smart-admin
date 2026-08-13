<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MasterData_model
 *
 * Model GENERIK untuk master data berstruktur sama: kode + nama + is_active.
 * Menangani: mst_shift, mst_mesin, mst_aktivitas, mst_proses,
 * mst_pekerjaan_borong, mst_kelompok_produk — dipilih lewat parameter $type.
 *
 * Kalau nanti ada master baru dengan struktur sama, tinggal tambah satu
 * baris di $config, tidak perlu bikin model baru.
 *
 * CATATAN: entri 'jf' SENGAJA TIDAK ADA di sini lagi. mst_jf sudah
 * di-redesain (kolom jf, product, qty, bapob, chip, customer, po,
 * kelompok_produk_id, status_jf -- bukan lagi kode/nama/is_active), jadi
 * tidak cocok dengan CRUD generik ini. Master JF sekarang punya
 * Jf_model.php + controller Jf.php sendiri. Menambahkan 'jf' kembali ke
 * $config ini akan membuat /masterdata/index/jf error (kolom nama/kode
 * tidak ada di mst_jf).
 */
class MasterData_model extends CI_Model
{
    private $config = array(
        'shift'            => array('table' => 'mst_shift',            'label' => 'Shift'),
        'mesin'            => array('table' => 'mst_mesin',             'label' => 'Mesin'),
        'aktivitas'        => array('table' => 'mst_aktivitas',         'label' => 'Aktivitas'),
        'proses'           => array('table' => 'mst_proses',            'label' => 'Proses'),
        'borong'           => array('table' => 'mst_pekerjaan_borong',  'label' => 'Pekerjaan Borong'),
        'kelompok_produk'  => array('table' => 'mst_kelompok_produk',   'label' => 'Kelompok Produk'),
    );

    public function get_types()
    {
        return $this->config;
    }

    /**
     * Contoh 2 baris data untuk halaman "Tambah Massal", disesuaikan per
     * $type supaya instruksinya relevan (bukan selalu contoh Shift walau
     * lagi input Mesin/Aktivitas/dst).
     *
     * @return array tiap elemen: array(kode, nama, aktif)
     */
    public function get_bulk_example($type)
    {
        $examples = array(
            'shift'           => array(array('SH1', 'Shift Pagi', '1'), array('SH2', 'Shift Siang', '1')),
            'mesin'           => array(array('MSN01', 'Mesin Cetak 1', '1'), array('MSN02', 'Mesin Cetak 2', '1')),
            'aktivitas'       => array(array('AKT01', 'Pengecekan QC', '1'), array('AKT02', 'Pengepakan', '1')),
            'proses'          => array(array('PR01', 'Pencetakan', '1'), array('PR02', 'Finishing', '1')),
            'borong'          => array(array('BR01', 'Jahit Borongan', '1'), array('BR02', 'Packing Borongan', '1')),
            'kelompok_produk' => array(array('KP01', 'Kelompok Produk A', '1'), array('KP02', 'Kelompok Produk B', '1')),
        );

        return isset($examples[$type]) ? $examples[$type] : array(array('KODE1', 'Nama Data 1', '1'), array('KODE2', 'Nama Data 2', '1'));
    }

    public function get_label($type)
    {
        return isset($this->config[$type]) ? $this->config[$type]['label'] : null;
    }

    public function is_valid_type($type)
    {
        return isset($this->config[$type]);
    }

    private function _table($type)
    {
        return $this->config[$type]['table'];
    }

    public function get_all($type)
    {
        $rows = $this->db->order_by('kode', 'ASC')->get($this->_table($type))->result_array();
        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($type, $id)
    {
        $row = $this->db->where('id', $id)->get($this->_table($type))->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_kode($type, $kode, $exclude_id = null)
    {
        $this->db->where('kode', $kode);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->_table($type))->row_array();
    }

    public function create($type, $data)
    {
        $this->db->insert($this->_table($type), array(
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ));
        return $this->db->insert_id();
    }

    public function update($type, $id, $data)
    {
        $this->db->where('id', $id)->update($this->_table($type), array(
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'is_active' => (bool) $data['is_active'],
        ));
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel). Tiap elemen $rows
     * adalah array kolom mentah hasil parse_bulk_paste(): [kode, nama, aktif?].
     *
     * Kalau kode SUDAH ADA di tabel ini, baris yang bersangkutan DI-REPLACE
     * (nama + status aktif ditimpa) -- beda dari add() biasa yang menolak
     * kode duplikat. Kalau belum ada, jadi baris baru.
     *
     * @return array array('inserted'=>int, 'updated'=>int,
     *               'errors'=>array(array('line'=>int, 'message'=>string)))
     */
    public function bulk_upsert($type, array $rows)
    {
        $table    = $this->_table($type);
        $inserted = 0;
        $updated  = 0;
        $errors   = array();

        foreach ($rows as $i => $cols) {
            $line = $i + 1;
            $kode = isset($cols[0]) ? trim($cols[0]) : '';
            $nama = isset($cols[1]) ? trim($cols[1]) : '';

            if ($kode === '' || $nama === '') {
                $errors[] = array('line' => $line, 'message' => 'Kode dan Nama wajib diisi.');
                continue;
            }
            if (mb_strlen($kode) > 50 || mb_strlen($nama) > 200) {
                $errors[] = array('line' => $line, 'message' => 'Kode/Nama melebihi panjang maksimum.');
                continue;
            }

            $is_active = parse_flexible_bool(isset($cols[2]) ? $cols[2] : '', true);
            $existing  = $this->db->where('kode', $kode)->get($table)->row_array();

            try {
                if ($existing) {
                    $this->db->where('id', $existing['id'])->update($table, array(
                        'nama'      => $nama,
                        'is_active' => $is_active,
                    ));
                    $updated++;
                } else {
                    $this->db->insert($table, array(
                        'kode'      => $kode,
                        'nama'      => $nama,
                        'is_active' => $is_active,
                    ));
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = array('line' => $line, 'message' => 'Gagal simpan: ' . $e->getMessage());
            }
        }

        return array('inserted' => $inserted, 'updated' => $updated, 'errors' => $errors);
    }

    /** Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari trx_laporan_produksi. */
    public function delete($type, $id)
    {
        try {
            $this->db->where('id', $id)->delete($this->_table($type));
            return array('success' => true, 'message' => ucfirst($this->get_label($type)) . ' dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, kemungkinan masih dipakai di data laporan.',
            );
        }
    }
}