<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Karyawan extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Karyawan_model');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('karyawan', 'view');

        $data['title']     = 'Manajemen Karyawan - ' . APP_NAME;
        $data['menus']     = $this->menus;
        $data['karyawans'] = $this->Karyawan_model->get_all();
        $data['access']    = cek_akses('karyawan');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('karyawan/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('karyawan', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $nik = $this->input->post('nik', true);

                if ($this->Karyawan_model->get_by_nik($nik)) {
                    $this->session->set_flashdata('error', 'NIK sudah dipakai.');
                } else {
                    $this->Karyawan_model->create(array(
                        'nik'                => $nik,
                        'nama'               => $this->input->post('nama', true),
                        'status_kepegawaian' => $this->input->post('status_kepegawaian'),
                        'is_active'          => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', 'Karyawan ditambahkan.');
                    redirect('karyawan');
                }
            }
        }

        $data['title']    = 'Tambah Karyawan - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['karyawan'] = null;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('karyawan/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('karyawan', 'edit');

        $karyawan = $this->Karyawan_model->get($id);
        if (!$karyawan) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $nik = $this->input->post('nik', true);
                $existing = $this->Karyawan_model->get_by_nik($nik, $id);

                if ($existing) {
                    $this->session->set_flashdata('error', 'NIK sudah dipakai.');
                } else {
                    $this->Karyawan_model->update($id, array(
                        'nik'                => $nik,
                        'nama'               => $this->input->post('nama', true),
                        'status_kepegawaian' => $this->input->post('status_kepegawaian'),
                        'is_active'          => (bool) $this->input->post('is_active'),
                    ));
                    $this->session->set_flashdata('success', 'Karyawan diperbarui.');
                    redirect('karyawan');
                }
            }
        }

        $data['title']    = 'Edit Karyawan - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['karyawan'] = $karyawan;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('karyawan/form', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel:
     * nik<TAB>nama<TAB>status_kepegawaian<TAB>aktif). Baris dengan NIK
     * yang SUDAH ADA akan DI-REPLACE, bukan ditolak.
     */
    public function bulk()
    {
        $this->require_access('karyawan', 'input');

        $result = null;

        if ($this->input->method() === 'post') {
            $raw = (string) $this->input->post('data');

            if (trim($raw) === '') {
                $this->session->set_flashdata('error', 'Data tempelan masih kosong.');
            } else {
                $result = $this->Karyawan_model->bulk_upsert(parse_bulk_paste($raw));

                if ($result['inserted'] === 0 && $result['updated'] === 0) {
                    $this->session->set_flashdata('error', 'Tidak ada baris yang berhasil diproses. Periksa detail error di bawah.');
                } else {
                    $msg = $result['inserted'] . ' karyawan baru ditambahkan, ' . $result['updated'] . ' data di-replace.';
                    if (!empty($result['errors'])) {
                        $msg .= ' ' . count($result['errors']) . ' baris gagal, lihat detail di bawah.';
                    }
                    $this->session->set_flashdata('success', $msg);

                    if (empty($result['errors'])) {
                        redirect('karyawan');
                    }
                }
            }
        }

        $data['title']  = 'Tambah Massal Karyawan - ' . APP_NAME;
        $data['menus']  = $this->menus;
        $data['result'] = $result;
        $data['raw']    = $this->input->post('data');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('karyawan/bulk', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('karyawan', 'delete');

        $result = $this->Karyawan_model->delete($id);
        $this->session->set_flashdata($result['success'] ? 'success' : 'error', $result['message']);
        redirect('karyawan');
    }

    private function _validate()
    {
        $this->form_validation->set_rules('nik', 'NIK', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('nama', 'Nama', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('status_kepegawaian', 'Status Kepegawaian', 'required|in_list[BORONG,HARIAN]');
    }
}