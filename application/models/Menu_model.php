<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends CI_Model
{
    /**
     * PostgreSQL driver CI3 mengembalikan kolom boolean sebagai string 't'/'f',
     * bukan boolean PHP asli. Fungsi ini menormalkan jadi boolean PHP yang benar.
     */
    private function _bool($val)
    {
        if (is_bool($val)) {
            return $val;
        }
        return in_array($val, array('t', 'true', '1', 1), TRUE);
    }

    private function _normalize_access_row($row)
    {
        if (!$row) {
            return $row;
        }
        $row['can_view']   = $this->_bool($row['can_view']);
        $row['can_input']  = $this->_bool($row['can_input']);
        $row['can_edit']   = $this->_bool($row['can_edit']);
        $row['can_delete'] = $this->_bool($row['can_delete']);
        return $row;
    }

    /**
     * Ambil daftar menu (yang can_view = true) untuk user tertentu.
     * TIDAK ADA fallback ke role global -- kalau user belum diatur untuk
     * suatu modul, effective_level = 0 (tidak ada akses).
     *
     * Pengecualian: menu "dashboard" selalu can_view = TRUE untuk siapapun
     * yang sudah login, karena controller Dashboard memang tidak pernah
     * digerbang oleh require_access(). Tanpa pengecualian ini, user baru
     * yang belum diatur levelnya sama sekali akan kehilangan link Dashboard
     * di sidebar walau sebenarnya tetap bisa mengaksesnya lewat URL.
     */
    public function get_menu_for_level($user_id)
    {
        $sql = "
            SELECT m.id, m.parent_id, m.menu_code, m.menu_name, m.menu_url, m.menu_icon, m.sort_order,
                   COALESCE(uma.level, 0) AS effective_level,
                   CASE
                       WHEN m.menu_code = 'dashboard' THEN TRUE
                       ELSE COALESCE(a.can_view, FALSE)
                   END AS can_view,
                   COALESCE(a.can_input, FALSE)  AS can_input,
                   COALESCE(a.can_edit, FALSE)   AS can_edit,
                   COALESCE(a.can_delete, FALSE) AS can_delete
            FROM mst_menu m
            LEFT JOIN mst_user_menu_access uma ON uma.menu_id = m.id AND uma.user_id = ?
            LEFT JOIN mst_menu_access a ON a.menu_id = m.id AND a.level = COALESCE(uma.level, 0)
            WHERE m.is_active = TRUE
            ORDER BY m.sort_order ASC
        ";

        $result = $this->db->query($sql, array($user_id))->result_array();
        $result = array_map(array($this, '_normalize_access_row'), $result);

        return array_values(array_filter($result, function ($row) {
            return $row['can_view'] === TRUE;
        }));
    }

    /**
     * Ambil hak akses untuk satu menu spesifik (dipakai helper cek_akses()).
     * Tidak ada fallback -- kalau tidak ada pengaturan, dianggap level 0.
     */
    public function get_access($menu_code, $user_id)
    {
        $menu = $this->db->get_where('mst_menu', array('menu_code' => $menu_code))->row_array();
        if (!$menu) {
            return FALSE;
        }

        $override = $this->db->get_where('mst_user_menu_access', array(
            'menu_id' => $menu['id'],
            'user_id' => $user_id,
        ))->row_array();

        $effective_level = $override ? (int) $override['level'] : 0;

        $access = $this->db->get_where('mst_menu_access', array(
            'menu_id' => $menu['id'],
            'level'   => $effective_level,
        ))->row_array();

        $access = $this->_normalize_access_row($access);

        // Sama seperti di get_menu_for_level(): Dashboard selalu can_view = TRUE
        // untuk siapapun yang sudah login, terlepas dari level akses mereka.
        if ($menu_code === 'dashboard') {
            if (!$access) {
                $access = array('can_view' => TRUE, 'can_input' => FALSE, 'can_edit' => FALSE, 'can_delete' => FALSE);
            } else {
                $access['can_view'] = TRUE;
            }
        }

        return $access;
    }

    /**
     * Ambil level efektif user untuk satu modul (dipakai di form pengaturan akses).
     */
    public function get_effective_level($menu_code, $user_id)
    {
        $menu = $this->db->get_where('mst_menu', array('menu_code' => $menu_code))->row_array();
        if (!$menu) {
            return 0;
        }

        $override = $this->db->get_where('mst_user_menu_access', array(
            'menu_id' => $menu['id'],
            'user_id' => $user_id,
        ))->row_array();

        return $override ? (int) $override['level'] : 0;
    }

    /**
     * Set level akses user untuk modul tertentu (selalu eksplisit, termasuk 0).
     */
    public function set_user_module_level($user_id, $menu_id, $level)
    {
        $existing = $this->db->get_where('mst_user_menu_access', array(
            'user_id' => $user_id,
            'menu_id' => $menu_id,
        ))->row_array();

        if ($existing) {
            return $this->db->where('id', $existing['id'])
                             ->update('mst_user_menu_access', array('level' => $level));
        }

        return $this->db->insert('mst_user_menu_access', array(
            'user_id' => $user_id,
            'menu_id' => $menu_id,
            'level'   => $level,
        ));
    }

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->get('mst_menu')->result_array();
    }
}