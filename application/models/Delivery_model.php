<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery_model
 *
 * CRUD catatan kiriman per JF (trx_delivery_record): No. JF, Tanggal Kirim,
 * Aktual Kirim (qty barang yang benar-benar terkirim), No. SP, Jenis SP.
 * Ini murni data pencatatan -- BUKAN mekanisme final JF otomatis (final JF
 * tetap manual & global, lihat Jf_model::set_final()). Data di sini
 * nantinya jadi salah satu bahan pertimbangan (akumulasi qty terkirim vs
 * qty JF) untuk mendeteksi anomali, tapi perhitungan itu belum ada di sini.
 */
class Delivery_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Daftar semua kiriman, join ke mst_jf supaya kode JF & product
     * langsung tampil di list tanpa query terpisah.
     */
    public function get_all()
    {
        return $this->db->select('d.*, j.jf AS jf_kode, j.product AS jf_product, j.status_jf')
                          ->from('trx_delivery_record d')
                          ->join('mst_jf j', 'j.id = d.jf_id')
                          ->order_by('d.tanggal_kirim', 'DESC')
                          ->order_by('d.id', 'DESC')
                          ->get()
                          ->result_array();
    }

    public function get($id)
    {
        return $this->db->select('d.*, j.jf AS jf_kode, j.product AS jf_product, j.status_jf')
                          ->from('trx_delivery_record d')
                          ->join('mst_jf j', 'j.id = d.jf_id')
                          ->where('d.id', $id)
                          ->get()
                          ->row_array();
    }

    /**
     * Semua kiriman untuk satu JF, dipakai nanti sebagai dasar hitung
     * akumulasi qty terkirim vs qty JF (belum diimplementasikan di sini).
     */
    public function get_by_jf($jf_id)
    {
        return $this->db->where('jf_id', $jf_id)
                          ->order_by('tanggal_kirim', 'ASC')
                          ->get('trx_delivery_record')
                          ->result_array();
    }

    public function create(array $data)
    {
        $this->db->insert('trx_delivery_record', $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('id', $id)->update('trx_delivery_record', $data);
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete('trx_delivery_record');
        return $this->db->affected_rows();
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel). Tiap elemen $rows
     * adalah array kolom mentah hasil parse_bulk_paste():
     * [no_jf, tanggal_kirim, aktual_kirim(qty)?, no_sp, jenis_sp?]
     *
     * Beda dari master data lain (Master Data/Karyawan/JF) yang punya satu
     * kolom kode unik, delivery record tidak punya kolom kode tunggal --
     * jadi kecocokan baris "sudah ada" dicek dari PASANGAN (No. JF + No. SP).
     * Kalau pasangan itu SUDAH ADA, baris DI-REPLACE (tanggal kirim, aktual
     * kirim/qty, jenis SP ditimpa). Kalau belum ada, jadi baris baru.
     *
     * No. JF harus sudah terdaftar di Master JF (tidak dibuatkan otomatis)
     * -- kalau tidak ketemu, baris itu gagal.
     *
     * @param int $inputer_id dipakai untuk baris baru saja (kolom inputer_id
     *            tidak ditimpa ulang saat replace, supaya jejak siapa yang
     *            input pertama kali tetap ada).
     * @return array array('inserted'=>int, 'updated'=>int,
     *               'errors'=>array(array('line'=>int, 'message'=>string)))
     */
    public function bulk_upsert(array $rows, $inputer_id)
    {
        $this->load->library('value_converter');

        $inserted = 0;
        $updated  = 0;
        $errors   = array();

        foreach ($rows as $i => $cols) {
            $line         = $i + 1;
            $jf_kode      = isset($cols[0]) ? trim($cols[0]) : '';
            $tanggal_raw  = isset($cols[1]) ? trim($cols[1]) : '';
            $aktual_raw   = isset($cols[2]) ? trim($cols[2]) : '';
            $no_sp        = isset($cols[3]) ? trim($cols[3]) : '';
            $jenis_sp     = isset($cols[4]) ? trim($cols[4]) : '';

            if ($jf_kode === '' || $no_sp === '') {
                $errors[] = array('line' => $line, 'message' => 'No. JF dan No. SP wajib diisi.');
                continue;
            }
            if (mb_strlen($no_sp) > 100) {
                $errors[] = array('line' => $line, 'message' => 'No. SP melebihi 100 karakter.');
                continue;
            }

            $jf_row = $this->db->where('jf', $jf_kode)->get('mst_jf')->row_array();
            if (!$jf_row) {
                $errors[] = array('line' => $line, 'message' => 'No. JF "' . $jf_kode . '" tidak ditemukan di Master JF.');
                continue;
            }

            if ($tanggal_raw === '') {
                $errors[] = array('line' => $line, 'message' => 'Tanggal Kirim wajib diisi.');
                continue;
            }
            $tanggal_kirim = $this->value_converter->to_date($tanggal_raw);
            if ($tanggal_kirim === null) {
                $errors[] = array('line' => $line, 'message' => 'Tanggal Kirim tidak valid (dapat: "' . $tanggal_raw . '").');
                continue;
            }

            $aktual_kirim = null;
            if ($aktual_raw !== '') {
                if (!is_numeric(str_replace(',', '.', $aktual_raw))) {
                    $errors[] = array('line' => $line, 'message' => 'Aktual Kirim (Qty) harus angka (dapat: "' . $aktual_raw . '").');
                    continue;
                }
                $aktual_kirim = (float) str_replace(',', '.', $aktual_raw);
            }

            $data = array(
                'jf_id'         => $jf_row['id'],
                'tanggal_kirim' => $tanggal_kirim,
                'aktual_kirim'  => $aktual_kirim,
                'no_sp'         => $no_sp,
                'jenis_sp'      => $jenis_sp !== '' ? $jenis_sp : null,
            );

            $existing = $this->db->where('jf_id', $jf_row['id'])
                                  ->where('no_sp', $no_sp)
                                  ->get('trx_delivery_record')
                                  ->row_array();

            try {
                if ($existing) {
                    $this->db->where('id', $existing['id'])->update('trx_delivery_record', $data);
                    $updated++;
                } else {
                    $data['inputer_id'] = $inputer_id;
                    $this->db->insert('trx_delivery_record', $data);
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = array('line' => $line, 'message' => 'Gagal simpan: ' . $e->getMessage());
            }
        }

        return array('inserted' => $inserted, 'updated' => $updated, 'errors' => $errors);
    }

    // ---------------------------------------------------------------
    // Pencantolan stok FG (trx_delivery_pemakaian_fg) -- Bagian 3.4 rancangan
    // ---------------------------------------------------------------

    public function get_pemakaian_fg($delivery_id)
    {
        return $this->db->select("f.*, j.jf, p.nama AS proses_nama, m.periode AS monitoring_periode")
            ->from('trx_delivery_pemakaian_fg f')
            ->join('trx_monitoring_produksi m', 'm.id = f.monitoring_id')
            ->join('mst_jf j', 'j.id = m.jf_id')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->where('f.delivery_id', $delivery_id)
            ->order_by('f.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Cari baris monitoring dengan status_output = FINISH_GOOD_STOK untuk
     * satu JF, lengkap sisa stok FG (realisasi_good_qty dikurangi total
     * sudah dipakai kiriman manapun -- bukan hanya kiriman ini).
     */
    public function search_stok_fg($jf_id)
    {
        return $this->db->select("m.id AS monitoring_id, p.nama AS proses_nama, d.department_name AS department_nama,
                            m.periode, m.realisasi_good_qty,
                            m.realisasi_good_qty - COALESCE(pakai.total_pakai, 0) AS sisa_qty")
            ->from('trx_monitoring_produksi m')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->join('mst_department d', 'd.id = m.department_id')
            ->join(
                "(SELECT monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_delivery_pemakaian_fg
                  GROUP BY monitoring_id) pakai",
                'pakai.monitoring_id = m.id',
                'left'
            )
            ->where('m.jf_id', $jf_id)
            ->where('m.status_output', 'FINISH_GOOD_STOK')
            ->order_by('m.periode', 'DESC')
            ->get()
            ->result_array();
    }

    public function get_sisa_stok_fg($monitoring_id)
    {
        $m = $this->db->select('realisasi_good_qty')->where('id', $monitoring_id)
            ->get('trx_monitoring_produksi')->row_array();
        if (!$m) {
            return null;
        }

        $pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('monitoring_id', $monitoring_id)
            ->get('trx_delivery_pemakaian_fg')
            ->row_array();

        return (float) $m['realisasi_good_qty'] - (float) $pakai['total'];
    }

    public function create_pemakaian_fg($delivery_id, $monitoring_id, $qty_pakai, $inputer_id)
    {
        $this->db->insert('trx_delivery_pemakaian_fg', array(
            'delivery_id'   => $delivery_id,
            'monitoring_id' => $monitoring_id,
            'qty_pakai'     => $qty_pakai,
            'inputer_id'    => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function delete_pemakaian_fg($id)
    {
        $this->db->where('id', $id)->delete('trx_delivery_pemakaian_fg');
        return $this->db->affected_rows();
    }
}
