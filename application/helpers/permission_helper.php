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
            'level'    => (int) $CI->session->userdata('level'),
        );
    }
}

if (!function_exists('current_level')) {
    function current_level()
    {
        $CI =& get_instance();
        return (int) $CI->session->userdata('level');
    }
}

if (!function_exists('has_min_level')) {
    /**
     * Cek apakah level user >= level minimal yang dibutuhkan
     * Contoh: has_min_level(ROLE_MASTER) -> true hanya kalau level user 3
     */
    function has_min_level($min_level)
    {
        return current_level() >= (int) $min_level;
    }
}

if (!function_exists('cek_akses')) {
    /**
     * Cek akses menu tertentu untuk user yang sedang login.
     * Mengembalikan array hak akses: view/input/edit/delete, atau FALSE kalau tidak ada akses.
     *
     * @param string $menu_code kode menu, contoh 'user', 'import'
     */
    function cek_akses($menu_code)
    {
        $CI =& get_instance();
        $CI->load->model('Menu_model');

        $level = current_level();
        $user_id = $CI->session->userdata('user_id');

        return $CI->Menu_model->get_access($menu_code, $level, $user_id);
    }
}
