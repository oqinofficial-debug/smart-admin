<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jf
 *
 * CRUD master JF (mst_jf). Beda dari master data lain (shift, mesin, dst)
 * karena kolomnya lebih banyak (product, qty, bapob, chip, customer, po,
 * kelompok_produk_id, status_jf), jadi tidak dipakaikan ke MasterData
 * generik -- controller & view sendiri.
 *
 * Menu code: 'jf'. Perlu baris di mst_menu + mst_menu_access supaya
 * require_access() tidak menolak semua orang -- lihat catatan migrasi.
 */
class Jf extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Jf_model');
        $this->load->model('MasterData_model'); // buat ambil daftar kelompok produk aktif
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('jf', 'view');

        $data['title']  = 'Manajemen JF - ' . APP_NAME;
        $data['menus']  = $this->menus;
        $data['jfs']    = $this->Jf_model->get_all();
        $data['access'] = cek_akses('jf');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('jf/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('jf', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode_jf = $this->input->post('jf', true);

                if ($this->Jf_model->get_by_jf($kode_jf)) {
                    $this->session->set_flashdata('error', 'Kode JF sudah dipakai.');
                } else {
                    $this->Jf_model->create($this->_collect_post());
                    $this->session->set_flashdata('success', 'JF ditambahkan.');
                    redirect('jf');
                    return;
                }
            }
        }

        $data['title']            = 'Tambah JF - ' . APP_NAME;
        $data['menus']            = $this->menus;
        $data['jf_row']           = null;
        $data['kelompok_produk']  = $this->MasterData_model->get_all('kelompok_produk');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('jf/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('jf', 'edit');

        $jf_row = $this->Jf_model->get($id);
        if (!$jf_row) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $kode_jf  = $this->input->post('jf', true);
                $existing = $this->Jf_model->get_by_jf($kode_jf, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'Kode JF sudah dipakai.');
                } else {
                    $this->Jf_model->update($id, $this->_collect_post(true));
                    $this->session->set_flashdata('success', 'JF diperbarui.');
                    redirect('jf');
                    return;
                }
            }
        }

        $data['title']            = 'Edit JF - ' . APP_NAME;
        $data['menus']            = $this->menus;
        $data['jf_row']           = $jf_row;
        $data['kelompok_produk']  = $this->MasterData_model->get_all('kelompok_produk');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('jf/form', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Tombol cepat "Jadikan Final" dari list, tanpa lewat form edit.
     * Setelah final, JF tidak lagi dianggap aktif di periode manapun.
     */
    public function final($id)
    {
        $this->require_access('jf', 'edit');

        $jf_row = $this->Jf_model->get($id);
        if (!$jf_row) {
            show_404();
        }

        $this->Jf_model->set_final($id);
        $this->session->set_flashdata('success', 'JF "' . $jf_row['jf'] . '" ditandai FINAL.');
        redirect('jf');
    }

    public function delete($id)
    {
        $this->require_access('jf', 'delete');

        $result = $this->Jf_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('jf');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('jf', 'Kode JF', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('product', 'Product', 'trim|max_length[200]');
        $this->form_validation->set_rules('qty', 'Qty', 'trim|numeric');
        $this->form_validation->set_rules('bapob', 'BAPOB', 'trim|max_length[100]');
        $this->form_validation->set_rules('chip', 'Chip', 'trim|max_length[100]');
        $this->form_validation->set_rules('customer', 'Customer', 'trim|max_length[100]');
        $this->form_validation->set_rules('po', 'PO', 'trim|max_length[100]');
    }

    /**
     * Kumpulkan field dari POST jadi array siap insert/update.
     * $for_update = true mengikutkan status_jf (bisa diubah manual lewat
     * form edit); saat create, status_jf selalu AKTIF (default kolom DB,
     * tapi dieksplisitkan di sini juga biar jelas).
     */
    private function _collect_post($for_update = false)
    {
        $qty                = $this->input->post('qty', true);
        $kelompok_produk_id = $this->input->post('kelompok_produk_id', true);

        $data = array(
            'jf'                 => $this->input->post('jf', true),
            'product'            => $this->input->post('product', true) ?: null,
            'qty'                => ($qty === '' || $qty === null) ? null : $qty,
            'bapob'              => $this->input->post('bapob', true) ?: null,
            'chip'               => $this->input->post('chip', true) ?: null,
            'customer'           => $this->input->post('customer', true) ?: null,
            'po'                 => $this->input->post('po', true) ?: null,
            'kelompok_produk_id' => $kelompok_produk_id !== '' ? $kelompok_produk_id : null,
        );

        if ($for_update) {
            $status = $this->input->post('status_jf', true);
            $data['status_jf'] = in_array($status, array('AKTIF', 'FINAL'), true) ? $status : 'AKTIF';
        } else {
            $data['status_jf'] = 'AKTIF';
        }

        return $data;
    }
}
