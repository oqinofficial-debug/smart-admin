<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MasterData
 *
 * Controller GENERIK untuk shift, mesin, aktivitas, proses, pekerjaan borong.
 * URL: /masterdata/index/{type}, /masterdata/add/{type}, /masterdata/edit/{type}/{id}
 * $type harus salah satu key di MasterData_model::$config.
 */
class MasterData extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('MasterData_model');
        $this->load->library('form_validation');
    }

    public function index($type = 'shift')
    {
        $this->require_access('master_data', 'view');
        $this->_check_type($type);

        $data['title'] = 'Master Data - ' . APP_NAME;
        $data['menus'] = $this->menus;
        $data['types']  = $this->MasterData_model->get_types();
        $data['type']   = $type;
        $data['label']  = $this->MasterData_model->get_label($type);
        $data['items']  = $this->MasterData_model->get_all($type);
        $data['access'] = cek_akses('master_data');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_data/index', $data);
        $this->load->view('templates/footer');
    }

    public function add($type)
    {
        $this->require_access('master_data', 'input');
        $this->_check_type($type);

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode = $this->input->post('kode', true);

                if ($this->MasterData_model->get_by_kode($type, $kode)) {
                    $this->session->set_flashdata('error', 'Kode sudah dipakai.');
                } else {
                    $this->MasterData_model->create($type, array(
                        'kode'      => $kode,
                        'nama'      => $this->input->post('nama', true),
                        'is_active' => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', $this->MasterData_model->get_label($type) . ' ditambahkan.');
                    redirect('masterdata/index/' . $type);
                }
            }
        }

        $data['title'] = 'Tambah ' . $this->MasterData_model->get_label($type) . ' - ' . APP_NAME;
        $data['menus'] = $this->menus;
        $data['type']  = $type;
        $data['label'] = $this->MasterData_model->get_label($type);
        $data['item']  = null;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_data/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($type, $id)
    {
        $this->require_access('master_data', 'edit');
        $this->_check_type($type);

        $item = $this->MasterData_model->get($type, $id);
        if (!$item) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode = $this->input->post('kode', true);
                $existing = $this->MasterData_model->get_by_kode($type, $kode, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode sudah dipakai.');
                } else {
                    $this->MasterData_model->update($type, $id, array(
                        'kode'      => $kode,
                        'nama'      => $this->input->post('nama', true),
                        'is_active' => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', $this->MasterData_model->get_label($type) . ' diperbarui.');
                    redirect('masterdata/index/' . $type);
                }
            }
        }

        $data['title'] = 'Edit ' . $this->MasterData_model->get_label($type) . ' - ' . APP_NAME;
        $data['menus'] = $this->menus;
        $data['type']  = $type;
        $data['label'] = $this->MasterData_model->get_label($type);
        $data['item']  = $item;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('master_data/form', $data);
        $this->load->view('templates/footer');
    }

    public function delete($type, $id)
    {
        $this->require_access('master_data', 'delete');
        $this->_check_type($type);

        $result = $this->MasterData_model->delete($type, $id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('masterdata/index/' . $type);
    }

    private function _check_type($type)
    {
        if (!$this->MasterData_model->is_valid_type($type)) {
            show_404();
        }
    }

    private function _validate()
    {
        $this->form_validation->set_rules('kode', 'Kode', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('nama', 'Nama', 'required|trim|max_length[200]');
    }
}
