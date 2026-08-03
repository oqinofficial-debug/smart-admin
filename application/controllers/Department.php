<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Department
 *
 * Manajemen mst_department SAJA (tambah/edit/hapus departemen).
 * Assign user ke departemen (many-to-many + primary) dan flag bypass
 * can_view_all_departments itu urusan User.php::edit(), bukan di sini.
 */
class Department extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Department_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('department', 'view');

        $data['title']       = 'Manajemen Departemen - ' . APP_NAME;
        $data['menus']       = $this->menus;
        $data['departments'] = $this->Department_model->get_all();
        $data['access']      = cek_akses('department');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('department/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('department', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $code = $this->input->post('department_code', true);

                if ($this->Department_model->get_by_code($code)) {
                    $this->session->set_flashdata('error', 'Kode departemen sudah dipakai.');
                } else {
                    $this->Department_model->create([
                        'department_code' => $code,
                        'department_name' => $this->input->post('department_name', true),
                        'is_active'       => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Departemen ditambahkan.');
                    redirect('department');
                }
            }
        }

        $data['title']      = 'Tambah Departemen - ' . APP_NAME;
        $data['menus']      = $this->menus;
        $data['department'] = null;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('department/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('department', 'edit');

        $department = $this->Department_model->get($id);
        if (!$department) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $code = $this->input->post('department_code', true);
                $existing = $this->Department_model->get_by_code($code, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode departemen sudah dipakai.');
                } else {
                    $this->Department_model->update($id, [
                        'department_code' => $code,
                        'department_name' => $this->input->post('department_name', true),
                        'is_active'       => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Departemen diperbarui.');
                    redirect('department');
                }
            }
        }

        $data['title']      = 'Edit Departemen - ' . APP_NAME;
        $data['menus']      = $this->menus;
        $data['department'] = $department;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('department/form', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('department', 'delete');

        $result = $this->Department_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('department');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('department_code', 'Kode Departemen', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('department_name', 'Nama Departemen', 'required|trim|max_length[100]');
    }
}
