<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Base controller untuk semua halaman yang butuh login.
 * Controller publik (mis. Auth) tetap extend CI_Controller biasa.
 */
class MY_Controller extends CI_Controller
{
    protected $user;   // data user yang sedang login
    protected $menus;  // daftar menu sesuai hak akses, untuk sidebar

    public function __construct()
    {
        parent::__construct();

        $this->load->library('session');
        $this->load->helper(array('app_helper', 'permission_helper'));
        $this->load->model('Menu_model');

        if (!is_logged_in()) {
            redirect('auth');
            return;
        }

        $last_activity = $this->session->userdata('last_activity');
        if ($last_activity && (time() - $last_activity) > (SESSION_TIMEOUT_MINUTES * 60)) {
            $this->session->sess_destroy();
            redirect('auth?expired=1');
            return;
        }
        $this->session->set_userdata('last_activity', time());

        $this->user  = current_user();
        $this->menus = $this->Menu_model->get_menu_for_level($this->user['id']);
    }

    protected function require_access($menu_code, $type = 'view')
    {
        $access = cek_akses($menu_code);
        $allowed = $access && !empty($access['can_' . $type]);

        if (!$allowed) {
            show_error('Anda tidak memiliki hak akses untuk aksi ini.', 403, 'Akses Ditolak');
        }
    }
}