<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->model('Menu_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('user', 'view');

        $data = array(
            'title' => 'Manajemen User - ' . APP_NAME,
            'menus' => $this->menus,
            'users' => $this->User_model->get_all(),
            'current_user_id' => $this->user['id'], // dipakai di view untuk cek "jangan hapus diri sendiri"
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('user/list', $data);
        $this->load->view('templates/footer', $data);
    }

    public function create()
    {
        $this->require_access('user', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate(TRUE);

            if ($this->form_validation->run()) {
                $this->User_model->create(array(
                    'username' => $this->input->post('username', TRUE),
                    'password' => $this->input->post('password'),
                    'fullname' => $this->input->post('fullname', TRUE),
                    'level'    => (int) $this->input->post('level'),
                    'is_active'=> $this->input->post('is_active') ? TRUE : FALSE,
                ));

                $this->session->set_flashdata('success', 'User baru berhasil ditambahkan.');
                redirect('user');
                return;
            }
        }

        $data = array(
            'title'      => 'Tambah User - ' . APP_NAME,
            'menus'      => $this->menus,
            'mode'       => 'create',
            'user_data'  => array('level' => ROLE_VIEWER, 'is_active' => TRUE),
            'module_access' => array(),
            'all_menus'  => array(),
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('user/form', $data);
        $this->load->view('templates/footer', $data);
    }

    public function edit($id = null)
    {
        $this->require_access('user', 'edit');

        $user_data = $this->User_model->get_by_id($id);
        if (!$user_data) {
            show_404();
            return;
        }

        if ($this->input->method() === 'post') {
            $this->_validate(FALSE, $id);

            if ($this->form_validation->run()) {
                $update = array(
                    'username' => $this->input->post('username', TRUE),
                    'fullname' => $this->input->post('fullname', TRUE),
                    'level'    => (int) $this->input->post('level'),
                    'is_active'=> $this->input->post('is_active') ? TRUE : FALSE,
                );

                $new_password = $this->input->post('password');
                if (!empty($new_password)) {
                    $update['password'] = $new_password;
                }

                $this->User_model->update($id, $update);

                // simpan override akses per modul
                $all_menus = $this->Menu_model->get_all();
                foreach ($all_menus as $menu) {
                    $field = 'access_' . $menu['id'];
                    $value = $this->input->post($field); // '' = ikut level global, angka = override

                    if ($value === '' || $value === NULL) {
                        $this->Menu_model->clear_user_module_level($id, $menu['id']);
                    } else {
                        $this->Menu_model->set_user_module_level($id, $menu['id'], (int) $value);
                    }
                }

                $this->session->set_flashdata('success', 'Data user berhasil diperbarui.');
                redirect('user');
                return;
            }
        }

        $all_menus = $this->Menu_model->get_all();
        $module_access = array();
        foreach ($all_menus as $menu) {
            $module_access[$menu['id']] = $this->Menu_model->get_effective_level(
                $menu['menu_code'], $user_data['level'], $id
            );
        }

        $data = array(
            'title'         => 'Edit User - ' . APP_NAME,
            'menus'         => $this->menus,
            'mode'          => 'edit',
            'user_data'     => $user_data,
            'all_menus'     => $all_menus,
            'module_access' => $module_access,
        );

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('user/form', $data);
        $this->load->view('templates/footer', $data);
    }

    public function delete($id = null)
    {
        $this->require_access('user', 'delete');

        if ((int) $id === (int) $this->user['id']) {
            $this->session->set_flashdata('error', 'Tidak bisa menghapus akun yang sedang login.');
            redirect('user');
            return;
        }

        $this->User_model->delete($id);
        $this->session->set_flashdata('success', 'User berhasil dihapus.');
        redirect('user');
    }

    private function _validate($is_create, $current_id = null)
    {
        $this->form_validation->set_rules('username', 'Username', 'required|trim|min_length[3]|max_length[50]');
        $this->form_validation->set_rules('fullname', 'Nama Lengkap', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('level', 'Role', 'required|in_list[1,2,3]');

        if ($is_create) {
            $this->form_validation->set_rules('password', 'Password', 'required|min_length[6]');
        } else {
            $this->form_validation->set_rules('password', 'Password', 'min_length[6]');
        }

        // cek username unik (kecuali punya sendiri saat edit)
        $username = $this->input->post('username', TRUE);
        if ($username) {
            $existing = $this->User_model->find_by_username_any_status($username);
            if ($existing && (int) $existing['id'] !== (int) $current_id) {
                $this->form_validation->set_rules('username', 'Username', 'required|callback__username_taken');
            }
        }
    }

    public function _username_taken($username)
    {
        $this->form_validation->set_message('_username_taken', 'Username sudah dipakai user lain.');
        return FALSE;
    }
}
