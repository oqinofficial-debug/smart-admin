<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master_file_model
 *
 * CRUD mst_nama_laporan -- "identitas laporan" per departemen yang dipilih
 * user di form Import Data. Beda dari MasterData_model generik (shift,
 * mesin, dst) karena tiap baris di sini WAJIB terikat ke satu department_id,
 * dan kode-nya cuma unik di dalam departemen tersebut (bukan unik global),
 * jadi dibuat model & controller sendiri -- pola yang sama dengan
 * Jf_model / Material_raw_model.
 */
class Master_file_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ---------------------------------------------------------------
    // Baca
    // ---------------------------------------------------------------

    /**
     * @param int|null $department_id filter opsional
     * @return array tiap baris sudah termasuk nama_department (join)
     */
    public function get_all($department_id = null)
    {
        $this->db->select('l.*, d.department_name, d.department_code')
                  ->from('mst_nama_laporan l')
                  ->join('mst_department d', 'd.id = l.department_id', 'left')
                  ->order_by('d.department_code', 'ASC')
                  ->order_by('l.nama', 'ASC');

        if ($department_id) {
            $this->db->where('l.department_id', $department_id);
        }

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($id)
    {
        $row = $this->db->select('l.*, d.department_name, d.department_code')
                          ->from('mst_nama_laporan l')
                          ->join('mst_department d', 'd.id = l.department_id', 'left')
                          ->where('l.id', $id)
                          ->get()
                          ->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_kode($department_id, $kode, $exclude_id = null)
    {
        $this->db->where('department_id', $department_id)->where('kode', $kode);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get('mst_nama_laporan')->row_array();
    }

    /**
     * Daftar laporan AKTIF, dibatasi ke departemen yang boleh diakses user
     * (null $department_ids = tidak dibatasi, untuk user can_view_all_departments).
     * Dipakai untuk isi dropdown "Nama Laporan" di form Import Data.
     *
     * @param array|null $department_ids
     * @return array dikelompokkan per department_id, tiap elemen [id, kode, nama]
     */
    public function get_active_for_import(array $department_ids = null)
    {
        $this->db->select('l.id, l.kode, l.nama, l.department_id, d.department_name, d.department_code')
                  ->from('mst_nama_laporan l')
                  ->join('mst_department d', 'd.id = l.department_id', 'left')
                  ->where('l.is_active', true)
                  ->order_by('d.department_code', 'ASC')
                  ->order_by('l.nama', 'ASC');

        if ($department_ids !== null) {
            if (empty($department_ids)) {
                return array(); // user tidak punya departemen sama sekali
            }
            $this->db->where_in('l.department_id', $department_ids);
        }

        return $this->db->get()->result_array();
    }

    public function get_by_id($id)
    {
        return $this->db->where('id', $id)->get('mst_nama_laporan')->row_array();
    }

    // ---------------------------------------------------------------
    // Tulis
    // ---------------------------------------------------------------

    public function create(array $data)
    {
        $this->db->insert('mst_nama_laporan', array(
            'department_id' => $data['department_id'],
            'kode'          => $data['kode'],
            'nama'          => $data['nama'],
            'is_active'     => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ));
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('id', $id)->update('mst_nama_laporan', array(
            'department_id' => $data['department_id'],
            'kode'          => $data['kode'],
            'nama'          => $data['nama'],
            'is_active'     => (bool) $data['is_active'],
        ));
    }

    /** Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari trx_laporan_produksi / trx_import_batch. */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id)->delete('mst_nama_laporan');
            return array('success' => true, 'message' => 'Nama laporan dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, kemungkinan sudah pernah dipakai untuk import data. Nonaktifkan saja kalau sudah tidak dipakai.',
            );
        }
    }
}
