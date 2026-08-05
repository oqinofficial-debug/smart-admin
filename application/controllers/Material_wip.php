<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Material_wip
 *
 * Manajemen master mst_material_wip. Tiap baris WIP wajib merujuk satu
 * JF asal (jf_asal_id) -- dropdown pemilihannya diambil dari Jf_model
 * yang sudah ada, tidak query mst_jf sendiri di sini.
 *
 * Menu code: 'material_wip'. Perlu baris di mst_menu + mst_menu_access
 * supaya require_access() tidak menolak semua orang -- lihat catatan
 * migrasi di bagian bawah file ini.
 */
class Material_wip extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Material_wip_model');
        $this->load->model('Jf_model'); // buat dropdown JF asal
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('material_wip', 'view');

        $data['title']    = 'Master Material WIP - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['materials'] = $this->Material_wip_model->get_all();
        $data['access']   = cek_akses('material_wip');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_wip/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('material_wip', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode       = $this->input->post('kode_material', true);
                $jf_asal_id = $this->input->post('jf_asal_id', true);

                if ($this->Material_wip_model->get_by_kode_jf($kode, $jf_asal_id)) {
                    $this->session->set_flashdata('error', 'Kode material + JF asal ini sudah ada.');
                } else {
                    $this->Material_wip_model->create([
                        'kode_material' => $kode,
                        'nama_material' => $this->input->post('nama_material', true),
                        'jf_asal_id'    => $jf_asal_id,
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Material WIP ditambahkan.');
                    redirect('material_wip');
                    return;
                }
            }
        }

        $data['title']    = 'Tambah Material WIP - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['material'] = null;
        $data['jf_list']  = $this->Jf_model->get_all();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_wip/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('material_wip', 'edit');

        $material = $this->Material_wip_model->get($id);
        if (!$material) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode       = $this->input->post('kode_material', true);
                $jf_asal_id = $this->input->post('jf_asal_id', true);
                $existing   = $this->Material_wip_model->get_by_kode_jf($kode, $jf_asal_id, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode material + JF asal ini sudah ada.');
                } else {
                    $this->Material_wip_model->update($id, [
                        'kode_material' => $kode,
                        'nama_material' => $this->input->post('nama_material', true),
                        'jf_asal_id'    => $jf_asal_id,
                        'is_active'     => (bool) $this->input->post('is_active'),
                    ]);
                    $this->session->set_flashdata('success', 'Material WIP diperbarui.');
                    redirect('material_wip');
                    return;
                }
            }
        }

        $data['title']    = 'Edit Material WIP - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['material'] = $material;
        $data['jf_list']  = $this->Jf_model->get_all();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('material_wip/form', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('material_wip', 'delete');

        $result = $this->Material_wip_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('material_wip');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('kode_material', 'Kode Material', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('nama_material', 'Nama Material', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('jf_asal_id', 'JF Asal', 'required|trim|numeric');
    }
}

/*
| -------------------------------------------------------------------------
| CATATAN MIGRASI -- jalankan sekali di DB (dev & deploy):
| -------------------------------------------------------------------------
| INSERT INTO mst_menu (parent_id, menu_code, menu_name, menu_url, menu_icon, sort_order, is_active)
| VALUES (0, 'material_wip', 'Material WIP', 'material_wip', 'recycle', 71, true);
|
| -- lalu set akses per level (contoh: level1=view saja, level2=+input, level3=full),
| -- ambil id menu dari baris di atas:
| INSERT INTO mst_menu_access (menu_id, level, can_view, can_input, can_edit, can_delete)
| SELECT id, 1, true,  false, false, false FROM mst_menu WHERE menu_code = 'material_wip'
| UNION ALL
| SELECT id, 2, true,  true,  false, false FROM mst_menu WHERE menu_code = 'material_wip'
| UNION ALL
| SELECT id, 3, true,  true,  true,  true  FROM mst_menu WHERE menu_code = 'material_wip';
*/
