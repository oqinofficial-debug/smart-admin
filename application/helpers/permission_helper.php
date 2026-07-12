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