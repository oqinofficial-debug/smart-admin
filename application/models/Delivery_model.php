<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery_model
 *
 * CRUD catatan kiriman per JF (trx_delivery_record): No. JF, Tanggal Kirim,
 * Aktual Kirim, No. SP, Jenis SP. Ini murni data pencatatan -- BUKAN
 * mekanisme final JF otomatis (final JF tetap manual & global, lihat
 * Jf_model::set_final()). Data di sini nantinya jadi salah satu bahan
 * pertimbangan (akumulasi qty terkirim vs qty JF) untuk mendeteksi anomali,
 * tapi perhitungan itu belum ada di sini.
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
        return $this->db->select("m.id AS monitoring_id, p.nama AS proses_nama, d.nama AS department_nama,
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
