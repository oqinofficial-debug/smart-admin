<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Karyawan_model extends CI_Model
{
    protected $table = 'mst_karyawan';

    public function get_all()
    {
        $rows = $this->db->order_by('nama', 'ASC')->get($this->table)->result_array();
        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get($this->table)->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_nik($nik, $exclude_id = null)
    {
        $this->db->where('nik', $nik);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->table)->row_array();
    }

    public function create($data)
    {
        $this->db->insert($this->table, array(
            'nik'                => $data['nik'],
            'nama'               => $data['nama'],
            'status_kepegawaian' => $data['status_kepegawaian'],
            'is_active'          => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ));
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update($this->table, array(
            'nik'                => $data['nik'],
            'nama'               => $data['nama'],
            'status_kepegawaian' => $data['status_kepegawaian'],
            'is_active'          => (bool) $data['is_active'],
        ));
    }

    /** Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari trx_laporan_produksi. */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id)->delete($this->table);
            return array('success' => true, 'message' => 'Karyawan dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, kemungkinan masih dipakai di data laporan (SPV/LL/Operator).',
            );
        }
    }
}
