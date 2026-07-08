<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('session', 'form_validation'));
        $this->load->model('User_model');
        $this->load->helper(array('app_helper', 'permission_helper'));
    }

    public function index()
    {
        // Kalau sudah login, langsung ke dashboard
        if (is_logged_in()) {
            redirect('dashboard');
            return;
        }

        $data = array(
            'title'   => 'Login - ' . APP_NAME,
            'expired' => $this->input->get('expired') ? TRUE : FALSE,
        );

        $this->load->view('auth/login', $data);
    }

    public function login()
    {
        $this->form_validation->set_rules('username', 'Username', 'required|trim');
        $this->form_validation->set_rules('password', 'Password', 'required');

        if ($this->form_validation->run() === FALSE) {
            $data = array(
                'title'   => 'Login - ' . APP_NAME,
                'expired' => FALSE,
                'error'   => validation_errors(),
            );
            $this->load->view('auth/login', $data);
            return;
        }

        $username = $this->input->post('username', TRUE);
        $password = $this->input->post('password');

        $user = $this->User_model->verify_login($username, $password);

        if (!$user) {
            $data = array(
                'title'   => 'Login - ' . APP_NAME,
                'expired' => FALSE,
                'error'   => 'Username atau password salah.',
            );
            $this->load->view('auth/login', $data);
            return;
        }

        $this->session->set_userdata(array(
            SESSION_LOGIN_KEY => TRUE,
            'user_id'         => $user['id'],
            'username'        => $user['username'],
            'fullname'        => $user['fullname'],
            'level'           => $user['level'],
            'last_activity'   => time(),
        ));

        $this->User_model->update_last_login($user['id']);

        redirect('dashboard');
    }

    public function logout()
    {
        $this->session->sess_destroy();
        redirect('auth');
    }
}
