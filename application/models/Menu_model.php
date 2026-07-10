<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Menu_model extends CI_Model
{
    /**
     * Ambil daftar menu (yang can_view = true) untuk user tertentu.
     * Level yang dipakai per menu = level override di mst_user_menu_access
     * kalau ada, kalau tidak ada baru pakai $default_level (level global user).
     */
    public function get_menu_for_level($default_level, $user_id = null)
    {
        $sql = "
            SELECT m.id, m.parent_id, m.menu_code, m.menu_name, m.menu_url, m.menu_icon, m.sort_order,
                   COALESCE(uma.level, ?) AS effective_level,
                   COALESCE(a.can_view, FALSE)   AS can_view,
                   COALESCE(a.can_input, FALSE)  AS can_input,
                   COALESCE(a.can_edit, FALSE)   AS can_edit,
                   COALESCE(a.can_delete, FALSE) AS can_delete
            FROM mst_menu m
            LEFT JOIN mst_user_menu_access uma ON uma.menu_id = m.id AND uma.user_id = ?
            LEFT JOIN mst_menu_access a ON a.menu_id = m.id AND a.level = COALESCE(uma.level, ?)
            WHERE m.is_active = TRUE
            ORDER BY m.sort_order ASC
        ";

        $result = $this->db->query($sql, array($default_level, $user_id, $default_level))->result_array();

        // hanya kembalikan menu yang benar-benar boleh dilihat
        return array_values(array_filter($result, function ($row) {
            return $row['can_view'];
        }));
    }

    /**
     * Ambil hak akses untuk satu menu spesifik (dipakai helper cek_akses()).
     * Sama seperti di atas: cek dulu role khusus modul ini, baru fallback ke level global.
     */
    public function get_access($menu_code, $default_level, $user_id = null)
    {
        $menu = $this->db->get_where('mst_menu', array('menu_code' => $menu_code))->row_array();
        if (!$menu) {
            return FALSE;
        }

        $override = $this->db->get_where('mst_user_menu_access', array(
            'menu_id' => $menu['id'],
            'user_id' => $user_id,
        ))->row_array();

        $effective_level = $override ? (int) $override['level'] : (int) $default_level;

        return $this->db->get_where('mst_menu_access', array(
            'menu_id' => $menu['id'],
            'level'   => $effective_level,
        ))->row_array();
    }

    /**
     * Ambil role efektif user untuk satu modul (dipakai kalau perlu tampilkan
     * badge/level di view, bukan cuma boolean hak akses).
     */
    public function get_effective_level($menu_code, $default_level, $user_id = null)
    {
        $menu = $this->db->get_where('mst_menu', array('menu_code' => $menu_code))->row_array();
        if (!$menu) {
            return (int) $default_level;
        }

        $override = $this->db->get_where('mst_user_menu_access', array(
            'menu_id' => $menu['id'],
            'user_id' => $user_id,
        ))->row_array();

        return $override ? (int) $override['level'] : (int) $default_level;
    }

    /**
     * Set / update role user untuk modul tertentu. Dipakai nanti di UI
     * pengaturan akses (misal halaman User > atur akses).
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

    /**
     * Hapus override -> user kembali pakai level global untuk modul ini.
     */
    public function clear_user_module_level($user_id, $menu_id)
    {
        return $this->db->where(array('user_id' => $user_id, 'menu_id' => $menu_id))
                         ->delete('mst_user_menu_access');
    }

    public function get_all()
    {
        return $this->db->order_by('sort_order', 'ASC')->get('mst_menu')->result_array();
    }
}
