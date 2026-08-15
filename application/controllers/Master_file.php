<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Master_file
 *
 * CRUD "Master File" -- daftar identitas nama laporan per departemen
 * (mst_nama_laporan). Ini yang dipilih user di halaman Import Data supaya
 * saat file diimport ulang dengan nama laporan + periode yang sama, sistem
 * tahu persis data lama mana yang harus di-replace (lihat Import.php).
 *
 * Menu code: 'master_file'. Perlu baris di mst_menu + mst_menu_access
 * supaya require_access() tidak menolak semua orang -- lihat
 * database/migration_master_file.sql.
 */
class Master_file extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_file_model');
        $this->load->model('Department_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('master_file', 'view');

        $filter_department = $this->input->get('department_id');
        $filter_department = ($filter_department !== null && $filter_department !== '') ? (int) $filter_department : null;

        $data['title']       = 'Master File - ' . APP_NAME;
        $data['menus']       = $this->menus;
        $data['items']       = $this->Master_file_model->get_all($filter_department);
        $data['departments'] = $this->Department_model->get_all();
        $data['filter_department'] = $filter_department;
        $data['access']      = cek_akses('master_file');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_file/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('master_file', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $department_id = (int) $this->input->post('department_id', true);
                $kode = $this->input->post('kode', true);

                if ($this->Master_file_model->get_by_kode($department_id, $kode)) {
                    $this->session->set_flashdata('error', 'Kode laporan ini sudah dipakai di departemen yang sama.');
                } else {
                    $this->Master_file_model->create(array(
                        'department_id' => $department_id,
                        'kode'          => $kode,
                        'nama'          => $this->input->post('nama', true),
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', 'Nama laporan ditambahkan.');
                    redirect('master-file');
                    return;
                }
            }
        }

        $data['title']       = 'Tambah Nama Laporan - ' . APP_NAME;
        $data['menus']       = $this->menus;
        $data['item']        = null;
        $data['departments'] = $this->Department_model->get_all();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_file/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('master_file', 'edit');

        $item = $this->Master_file_model->get($id);
        if (!$item) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $department_id = (int) $this->input->post('department_id', true);
                $kode = $this->input->post('kode', true);
                $existing = $this->Master_file_model->get_by_kode($department_id, $kode, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode laporan ini sudah dipakai di departemen yang sama.');
                } else {
                    $this->Master_file_model->update($id, array(
                        'department_id' => $department_id,
                        'kode'          => $kode,
                        'nama'          => $this->input->post('nama', true),
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', 'Nama laporan diperbarui.');
                    redirect('master-file');
                    return;
                }
            }
        }

        $data['title']       = 'Edit Nama Laporan - ' . APP_NAME;
        $data['menus']       = $this->menus;
        $data['item']        = $item;
        $data['departments'] = $this->Department_model->get_all();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_file/form', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('master_file', 'delete');

        $result = $this->Master_file_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('master-file');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('department_id', 'Departemen', 'required|trim|integer');
        $this->form_validation->set_rules('kode', 'Kode Laporan', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('nama', 'Nama Laporan', 'required|trim|max_length[200]');
    }
}
