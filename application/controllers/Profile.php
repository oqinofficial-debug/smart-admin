<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Modul "Akun Saya" -- setiap user yang login (siapa pun, tanpa perlu hak
 * akses modul apa pun) boleh mengganti username dan/atau password miliknya
 * sendiri lewat sini. Tidak ada require_access() di controller ini karena
 * ini murni self-service, bukan manajemen user lain (itu tugas User.php).
 */
class Profile extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        if ($this->input->method() === 'post') {
            $this->_handle_update();
            return;
        }

        $data = array(
            'title'     => 'Akun Saya - ' . APP_NAME,
            'menus'     => $this->menus,
            'user_data' => $this->User_model->get_by_id($this->user['id']),
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('profile/index', $data);
        $this->load->view('templates/footer', $data);
    }

    private function _handle_update()
    {
        $id = $this->user['id'];

        $this->form_validation->set_rules('username', 'Username', 'required|trim|min_length[3]|max_length[50]|callback__username_taken');
        $this->form_validation->set_rules('current_password', 'Password Saat Ini', 'required|callback__current_password_valid');
        $this->form_validation->set_rules('new_password', 'Password Baru', 'min_length[6]');
        $this->form_validation->set_rules('confirm_password', 'Konfirmasi Password Baru', 'matches[new_password]');

        if ($this->form_validation->run()) {
            $username     = $this->input->post('username', TRUE);
            $new_password = $this->input->post('new_password');

            $update = array('username' => $username);
            if (!empty($new_password)) {
                $update['password'] = $new_password;
            }

            $this->User_model->update($id, $update);

            // Sinkronkan session supaya nama/username di header langsung
            // ikut berubah tanpa perlu login ulang.
            $this->session->set_userdata('username', $username);

            $this->session->set_flashdata('success', 'Akun berhasil diperbarui.' .
                (!empty($new_password) ? ' Password baru sudah aktif.' : ''));
            redirect('profile');
            return;
        }

        $data = array(
            'title'     => 'Akun Saya - ' . APP_NAME,
            'menus'     => $this->menus,
            'user_data' => $this->User_model->get_by_id($id),
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('profile/index', $data);
        $this->load->view('templates/footer', $data);
    }

    /**
     * Callback form_validation: pastikan username baru tidak dipakai user lain.
     * Boleh sama dengan username sendiri (tidak dianggap konflik).
     */
    public function _username_taken($username)
    {
        if ($this->User_model->username_taken_by_other($username, $this->user['id'])) {
            $this->form_validation->set_message('_username_taken', 'Username sudah dipakai user lain.');
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Callback form_validation: pastikan password saat ini benar-benar cocok
     * sebelum mengizinkan perubahan apa pun. Wajib diisi setiap kali submit,
     * baik hanya ganti username maupun sekalian ganti password.
     */
    public function _current_password_valid($password)
    {
        if (!$this->User_model->verify_password($this->user['id'], $password)) {
            $this->form_validation->set_message('_current_password_valid', 'Password saat ini salah.');
            return FALSE;
        }
        return TRUE;
    }
}
