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
}
