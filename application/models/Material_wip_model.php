<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Material_wip_model
 *
 * CRUD mst_material_wip. Beda dari RAW: WIP adalah material sisa/ex dari
 * JF sebelumnya yang dipakai lagi (bukan output biasa), jadi tiap baris
 * WIP wajib merujuk satu jf_asal_id. Kode material yang sama boleh
 * muncul berkali-kali kalau berasal dari JF sisa yang berbeda-beda
 * (unique index-nya kode_material + jf_asal_id, bukan kode_material saja).
 *
 * Status "asal JF" (AKTIF/FINAL) TIDAK disalin ke tabel ini -- selalu
 * diambil live lewat view v_material_wip supaya otomatis ikut berubah
 * kalau JF asalnya di-final-kan, tanpa perlu update manual di sini.
 */
class Material_wip_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /** List untuk halaman index, lewat view supaya status_asal_jf selalu live. */
    public function get_all()
    {
        $rows = $this->db->order_by('nama_material', 'ASC')
            ->get('v_material_wip')
            ->result_array();

        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    /** Baris mentah (bukan lewat view) untuk keperluan form edit. */
    public function get($id)
    {
        $row = $this->db->where('id', $id)->get('mst_material_wip')->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    /** Cek kombinasi kode material + JF asal sudah dipakai atau belum (sesuai unique index). */
    public function get_by_kode_jf($kode, $jf_asal_id, $exclude_id = null)
    {
        $this->db->where('LOWER(kode_material)', strtolower($kode));
        $this->db->where('jf_asal_id', $jf_asal_id);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get('mst_material_wip')->row_array();
    }

    public function create($data)
    {
        $this->db->insert('mst_material_wip', [
            'kode_material' => $data['kode_material'],
            'nama_material' => $data['nama_material'],
            'jf_asal_id'    => $data['jf_asal_id'],
            'is_active'     => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update('mst_material_wip', [
            'kode_material' => $data['kode_material'],
            'nama_material' => $data['nama_material'],
            'jf_asal_id'    => $data['jf_asal_id'],
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
            $this->db->where('id', $id)->delete('mst_material_wip');
            return ['success' => true, 'message' => 'Material WIP dihapus.'];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Tidak bisa dihapus, material ini masih dipakai di data lain.',
            ];
        }
    }
}
