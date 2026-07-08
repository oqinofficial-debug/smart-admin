<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends CI_Model
{
    /**
     * Ambil daftar menu (yang can_view = true) untuk level tertentu,
     * dengan override per-user kalau ada di mst_user_menu_access.
     */
    public function get_menu_for_level($level, $user_id = null)
    {
        $sql = "
            SELECT m.id, m.parent_id, m.menu_code, m.menu_name, m.menu_url, m.menu_icon, m.sort_order,
                   COALESCE(u.can_view, a.can_view, FALSE)   AS can_view,
                   COALESCE(u.can_input, a.can_input, FALSE) AS can_input,
                   COALESCE(u.can_edit, a.can_edit, FALSE)   AS can_edit,
                   COALESCE(u.can_delete, a.can_delete, FALSE) AS can_delete
            FROM mst_menu m
            LEFT JOIN mst_menu_access a ON a.menu_id = m.id AND a.level = ?
            LEFT JOIN mst_user_menu_access u ON u.menu_id = m.id AND u.user_id = ?
            WHERE m.is_active = TRUE
            ORDER BY m.sort_order ASC
        ";

        $result = $this->db->query($sql, array($level, $user_id))->result_array();

        // hanya kembalikan menu yang benar-benar boleh dilihat
        return array_values(array_filter($result, function ($row) {
            return $row['can_view'];
        }));
    }

    /**
     * Ambil hak akses untuk satu menu spesifik (dipakai helper cek_akses()).
     */
    public function get_access($menu_code, $level, $user_id = null)
    {
        $menu = $this->db->get_where('mst_menu', array('menu_code' => $menu_code))->row_array();
        if (!$menu) {
            return FALSE;
        }

        $override = $this->db->get_where('mst_user_menu_access', array(
            'menu_id' => $menu['id'],
            'user_id' => $user_id,
        ))->row_array();

        if ($override) {
            return $override;
        }

        return $this->db->get_where('mst_menu_access', array(
            'menu_id' => $menu['id'],
            'level'   => $level,
        ))->row_array();
    }

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->get('mst_menu')->result_array();
    }
}
