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