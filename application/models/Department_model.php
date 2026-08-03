<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Department_model
 *
 * CRUD mst_department, relasi many-to-many user<->departemen
 * (mst_user_department), dan flag bypass can_view_all_departments.
 *
 * Catatan boolean: driver CI3 + PostgreSQL mengembalikan 't'/'f' untuk
 * kolom boolean, bukan true/false PHP asli. Semua dinormalisasi lewat
 * normalize_bool() (lihat app_helper_addition.php).
 */
class Department_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ---------------------------------------------------------------
    // CRUD mst_department
    // ---------------------------------------------------------------

    public function get_all()
    {
        $sql = "
            SELECT d.*,
                   COUNT(ud.id) AS member_count
            FROM mst_department d
            LEFT JOIN mst_user_department ud ON ud.department_id = d.id
            GROUP BY d.id
            ORDER BY d.department_name ASC
        ";
        $rows = $this->db->query($sql)->result_array();

        foreach ($rows as &$row) {
            $row['is_active']    = normalize_bool($row['is_active']);
            $row['member_count'] = (int) $row['member_count'];
        }
        return $rows;
    }

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get('mst_department')->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_code($code, $exclude_id = null)
    {
        $this->db->where('department_code', $code);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get('mst_department')->row_array();
    }

    public function create($data)
    {
        $this->db->insert('mst_department', [
            'department_code' => $data['department_code'],
            'department_name' => $data['department_name'],
            'is_active'       => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ]);
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update('mst_department', [
            'department_code' => $data['department_code'],
            'department_name' => $data['department_name'],
            'is_active'       => (bool) $data['is_active'],
        ]);
    }

    /**
     * Hard delete (sesuai keputusan). Ditolak kalau masih ada anggota,
     * atau kalau DB menolak karena FK RESTRICT dari tabel data modul
     * lain (yang belum tentu diketahui model ini).
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public function delete($id)
    {
        $member_count = $this->count_members($id);
        if ($member_count > 0) {
            return [
                'success' => false,
                'message' => "Tidak bisa dihapus, masih ada {$member_count} user di departemen ini.",
            ];
        }

        try {
            $this->db->where('id', $id)->delete('mst_department');
            return ['success' => true, 'message' => 'Departemen dihapus.'];
        } catch (Exception $e) {
            // Kemungkinan besar FK RESTRICT dari tabel data modul lain
            return [
                'success' => false,
                'message' => 'Tidak bisa dihapus, masih ada data lain yang mereferensikan departemen ini.',
            ];
        }
    }

    public function count_members($department_id)
    {
        return (int) $this->db->where('department_id', $department_id)
            ->count_all_results('mst_user_department');
    }

    // ---------------------------------------------------------------
    // Relasi user <-> departemen
    // ---------------------------------------------------------------

    /** Semua departemen milik satu user, termasuk flag is_primary. */
    public function get_user_departments($user_id)
    {
        $sql = "
            SELECT ud.id, ud.department_id, ud.is_primary,
                   d.department_code, d.department_name, d.is_active
            FROM mst_user_department ud
            JOIN mst_department d ON d.id = ud.department_id
            WHERE ud.user_id = ?
            ORDER BY d.department_name ASC
        ";
        $rows = $this->db->query($sql, [$user_id])->result_array();
        foreach ($rows as &$row) {
            $row['is_primary'] = normalize_bool($row['is_primary']);
            $row['is_active']  = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    /**
     * Array flat department_id milik user. Ini yang dipakai modul lain
     * nanti untuk filter visibilitas data:
     *   WHERE is_public = TRUE OR department_id IN (...)
     */
    public function get_user_department_ids($user_id)
    {
        $rows = $this->db->select('department_id')
            ->where('user_id', $user_id)
            ->get('mst_user_department')
            ->result_array();
        return array_map(function ($r) { return (int) $r['department_id']; }, $rows);
    }

    public function get_primary_department_id($user_id)
    {
        $row = $this->db->select('department_id')
            ->where('user_id', $user_id)
            ->where('is_primary', true)
            ->get('mst_user_department')
            ->row_array();
        return $row ? (int) $row['department_id'] : null;
    }

    /**
     * Sinkronkan departemen milik user, dipanggil dari User.php::edit().
     *
     * @param int   $user_id
     * @param array $department_ids semua department_id yang dicentang
     * @param mixed $primary_id     salah satu dari $department_ids, atau null
     */
    public function set_user_departments($user_id, array $department_ids, $primary_id = null)
    {
        $this->db->trans_start();

        $this->db->where('user_id', $user_id)->delete('mst_user_department');

        foreach ($department_ids as $department_id) {
            $this->db->insert('mst_user_department', [
                'user_id'       => $user_id,
                'department_id' => $department_id,
                'is_primary'    => ($primary_id !== null && (int) $primary_id === (int) $department_id),
            ]);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
