<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Material_raw
 *
 * Manajemen master mst_material_raw. Halaman terpisah dari Material_wip
 * karena keduanya punya kolom dan makna yang berbeda (lihat catatan di
 * masing-masing model), walau tampilannya mirip.
 *
 * Menu code: 'material_raw'. Perlu baris di mst_menu + mst_menu_access
 * supaya require_access() tidak menolak semua orang -- lihat catatan
 * migrasi di bagian bawah file ini.
 */
class Material_raw extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Material_raw_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('material_raw', 'view');

        $data['title']    = 'Master Material RAW - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['materials'] = $this->Material_raw_model->get_all();
        $data['access']   = cek_akses('material_raw');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_raw/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('material_raw', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode = $this->input->post('kode_material', true);

                if ($this->Material_raw_model->get_by_kode($kode)) {
                    $this->session->set_flashdata('error', 'Kode material sudah dipakai.');
                } else {
                    $this->Material_raw_model->create([
                        'kode_material' => $kode,
                        'nama_material' => $this->input->post('nama_material', true),
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Material RAW ditambahkan.');
                    redirect('material_raw');
                    return;
                }
            }
        }

        $data['title']    = 'Tambah Material RAW - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['material'] = null;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_raw/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('material_raw', 'edit');

        $material = $this->Material_raw_model->get($id);
        if (!$material) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode     = $this->input->post('kode_material', true);
                $existing = $this->Material_raw_model->get_by_kode($kode, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode material sudah dipakai.');
                } else {
                    $this->Material_raw_model->update($id, [
                        'kode_material' => $kode,
                        'nama_material' => $this->input->post('nama_material', true),
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Material RAW diperbarui.');
                    redirect('material_raw');
                    return;
                }
            }
        }

        $data['title']    = 'Edit Material RAW - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['material'] = $material;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_raw/form', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('material_raw', 'delete');

        $result = $this->Material_raw_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('material_raw');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('kode_material', 'Kode Material', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('nama_material', 'Nama Material', 'required|trim|max_length[150]');
    }
}

/*
| -------------------------------------------------------------------------
| CATATAN MIGRASI -- jalankan sekali di DB (dev & deploy):
| -------------------------------------------------------------------------
| INSERT INTO mst_menu (parent_id, menu_code, menu_name, menu_url, menu_icon, sort_order, is_active)
| VALUES (0, 'material_raw', 'Material RAW', 'material_raw', 'cubes', 70, true);
|
| -- lalu set akses per level (contoh: level1=view saja, level2=+input, level3=full),
| -- ambil id menu dari baris di atas:
| INSERT INTO mst_menu_access (menu_id, level, can_view, can_input, can_edit, can_delete)
| SELECT id, 1, true,  false, false, false FROM mst_menu WHERE menu_code = 'material_raw'
| UNION ALL
| SELECT id, 2, true,  true,  false, false FROM mst_menu WHERE menu_code = 'material_raw'
| UNION ALL
| SELECT id, 3, true,  true,  true,  true  FROM mst_menu WHERE menu_code = 'material_raw';
*/
