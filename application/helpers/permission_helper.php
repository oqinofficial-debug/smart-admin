<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('is_logged_in')) {
    function is_logged_in()
    {
        $CI =& get_instance();
        return (bool) $CI->session->userdata(SESSION_LOGIN_KEY);
    }
}

if (!function_exists('current_user')) {
    /**
     * Ambil semua data user yang sedang login dari session
     */
    function current_user()
    {
        $CI =& get_instance();
        return array(
            'id'       => $CI->session->userdata('user_id'),
            'username' => $CI->session->userdata('username'),
            'fullname' => $CI->session->userdata('fullname'),
        );
    }
}

if (!function_exists('current_user_department_label')) {
    /**
     * Label bagian/departemen user yang sedang login, untuk ditampilkan di header.
     * - Kalau can_view_all_departments = true          -> "Semua Bagian"
     * - Kalau punya department dengan is_primary       -> nama department itu
     * - Kalau punya department tapi tidak ada primary  -> department pertama
     * - Kalau tidak terdaftar di department manapun    -> "-"
     */
    function current_user_department_label()
    {
        $CI =& get_instance();
        $user_id = $CI->session->userdata('user_id');

        if (!$user_id) {
            return '-';
        }

        $user = $CI->db->select('can_view_all_departments')
            ->where('id', $user_id)
            ->get('mst_user')
            ->row_array();

        if ($user && normalize_bool($user['can_view_all_departments'])) {
            return 'Semua Bagian';
        }

        $dept = $CI->db->select('d.department_name')
            ->from('mst_user_department ud')
            ->join('mst_department d', 'd.id = ud.department_id')
            ->where('ud.user_id', $user_id)
            ->order_by('ud.is_primary', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        return $dept ? $dept['department_name'] : '-';
    }
}

if (!function_exists('cek_akses')) {
    /**
     * Cek akses menu tertentu untuk user yang sedang login.
     * Tidak ada fallback ke role global -- kalau user tidak punya pengaturan
     * eksplisit untuk modul ini, dianggap tidak ada akses sama sekali.
     *
     * @param string $menu_code kode menu, contoh 'user', 'import'
     */
    function cek_akses($menu_code)
    {
        $CI =& get_instance();
        $CI->load->model('Menu_model');

        $user_id = $CI->session->userdata('user_id');

        return $CI->Menu_model->get_access($menu_code, $user_id);
    }
}