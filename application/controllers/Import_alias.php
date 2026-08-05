<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_alias
 *
 * Halaman pengaturan alias kolom untuk fitur Import Data Laporan Produksi.
 * Dipetakan ke URL /import/alias (lihat routes.php).
 *
 * Akses memakai menu_code 'import' yang sama dengan halaman import itu
 * sendiri: hanya level yang punya can_edit di menu 'import' yang boleh
 * mengubah pengaturan field/alias (biasanya level Master).
 */
class Import_alias extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Import_alias_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('import', 'edit');

        $data['title']         = 'Alias Kolom Import - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['kolom']         = $this->Import_alias_model->get_all_with_alias();
        $data['sheet_aliases'] = $this->Import_alias_model->get_sheet_aliases();
        $data['access']        = cek_akses('import');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import_alias/index', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('import', 'edit');

        $kolom = $this->Import_alias_model->get_kolom($id);
        if (!$kolom) {
            show_404();
        }

        $this->form_validation->set_rules('field_label', 'Label Field', 'required|trim|max_length[100]');

        if ($this->input->method() === 'post' && $this->form_validation->run() === TRUE) {
            $this->Import_alias_model->update_kolom($id, array(
                'field_label' => $this->input->post('field_label', true),
                'is_required' => (bool) $this->input->post('is_required'),
                'is_active'   => (bool) $this->input->post('is_active'),
            ));
            $this->session->set_flashdata('success', 'Pengaturan field "' . $kolom['field_label'] . '" diperbarui.');
            redirect('import/alias');
        }

        $data['title']  = 'Edit Field Import - ' . APP_NAME;
        $data['menus']  = $this->menus;
        $data['kolom']  = $kolom;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import_alias/form', $data);
        $this->load->view('templates/footer');
    }

    public function add_alias()
    {
        $this->require_access('import', 'edit');

        $kolom_id   = (int) $this->input->post('kolom_id');
        $alias_text = trim((string) $this->input->post('alias_text'));

        $kolom = $this->Import_alias_model->get_kolom($kolom_id);

        if (!$kolom) {
            $this->session->set_flashdata('error', 'Field tujuan tidak ditemukan.');
        } elseif ($alias_text === '') {
            $this->session->set_flashdata('error', 'Nama alias tidak boleh kosong.');
        } elseif ($this->Import_alias_model->alias_exists($alias_text)) {
            $this->session->set_flashdata('error', 'Alias "' . htmlspecialchars($alias_text) . '" sudah dipakai di field lain.');
        } else {
            $this->Import_alias_model->add_alias($kolom_id, $alias_text);
            $this->session->set_flashdata('success', 'Alias "' . htmlspecialchars($alias_text) . '" ditambahkan ke field "' . $kolom['field_label'] . '".');
        }

        redirect('import/alias');
    }

    public function delete_alias($id)
    {
        $this->require_access('import', 'edit');

        $alias = $this->Import_alias_model->get_alias($id);
        if ($alias) {
            $this->Import_alias_model->delete_alias($id);
            $this->session->set_flashdata('success', 'Alias dihapus.');
        }

        redirect('import/alias');
    }

    /**
     * Tambah nama sheet yang dikenali otomatis (lihat
     * Import_alias_model::find_matching_sheet()).
     */
    public function add_sheet_alias()
    {
        $this->require_access('import', 'edit');

        $alias_text = trim((string) $this->input->post('alias_text'));

        if ($alias_text === '') {
            $this->session->set_flashdata('error', 'Nama sheet tidak boleh kosong.');
        } elseif ($this->Import_alias_model->sheet_alias_exists($alias_text)) {
            $this->session->set_flashdata('error', 'Nama sheet "' . htmlspecialchars($alias_text) . '" sudah terdaftar.');
        } else {
            $this->Import_alias_model->add_sheet_alias($alias_text);
            $this->session->set_flashdata('success', 'Nama sheet "' . htmlspecialchars($alias_text) . '" ditambahkan ke daftar sheet default.');
        }

        redirect('import/alias');
    }

    public function delete_sheet_alias($id)
    {
        $this->require_access('import', 'edit');

        $alias = $this->Import_alias_model->get_sheet_alias($id);
        if ($alias) {
            $this->Import_alias_model->delete_sheet_alias($id);
            $this->session->set_flashdata('success', 'Nama sheet dihapus dari daftar default.');
        }

        redirect('import/alias');
    }
}
