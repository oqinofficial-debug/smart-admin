<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Material_raw_model
 *
 * CRUD mst_material_raw. Kode material di sini TIDAK terikat departemen
 * atau JF apapun -- itu murni identitas material mentah. Departemen baru
 * muncul nanti di tabel transaksi pemakaian (trx_pemakaian_material).
 */
class Material_raw_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_all()
    {
        $rows = $this->db->order_by('nama_material', 'ASC')
            ->get('mst_material_raw')
            ->result_array();

        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get('mst_material_raw')->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    /** Cek kode material sudah dipakai atau belum (case-insensitive, sesuai unique index). */
    public function get_by_kode($kode, $exclude_id = null)
    {
        $this->db->where('LOWER(kode_material)', strtolower($kode));
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get('mst_material_raw')->row_array();
    }

    public function create($data)
    {
        $this->db->insert('mst_material_raw', [
            'kode_material' => $data['kode_material'],
            'nama_material' => $data['nama_material'],
            'is_active'     => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update('mst_material_raw', [
            'kode_material' => $data['kode_material'],
            'nama_material' => $data['nama_material'],
            'is_active'     => (bool) $data['is_active'],
        ]);
    }

    /**
     * Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari tabel
     * transaksi pemakaian material (belum ada saat file ini dibuat, tapi
     * disiapkan supaya aman begitu tabelnya ada).
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id)->delete('mst_material_raw');
            return ['success' => true, 'message' => 'Material RAW dihapus.'];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Tidak bisa dihapus, material ini masih dipakai di data lain.',
            ];
        }
    }
}
