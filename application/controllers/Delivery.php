<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery
 *
 * CRUD catatan kiriman per JF (trx_delivery_record). Field: No. JF
 * (referensi ke mst_jf), Tanggal Kirim, Aktual Kirim (qty barang yang
 * benar-benar terkirim), No. SP, Jenis SP.
 *
 * Ini bukan proses finalisasi JF -- finalisasi tetap manual & global lewat
 * menu JF (Jf::final()).
 *
 * Menu code: 'delivery'. Perlu baris di mst_menu + mst_menu_access,
 * lihat migrations/2026_08_05_delivery_record.sql.
 */
class Delivery extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Delivery_model');
        $this->load->model('Jf_model'); // buat ambil daftar JF untuk dropdown
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->require_access('delivery', 'view');

        $data['title']     = 'Delivery Record - ' . APP_NAME;
        $data['menus']     = $this->menus;
        $data['deliveries'] = $this->Delivery_model->get_all();
        $data['access']    = cek_akses('delivery');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('delivery/index', $data);
        $this->load->view('templates/footer');
    }

    public function add()
    {
        $this->require_access('delivery', 'input');

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $data = $this->_collect_post();
                $data['inputer_id'] = $this->user['id'];

                $this->Delivery_model->create($data);
                $this->session->set_flashdata('success', 'Delivery record ditambahkan.');
                redirect('delivery');
                return;
            }
        }

        $data['title']         = 'Tambah Delivery Record - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['delivery_row']  = null;
        $data['jf_list']       = $this->Jf_model->get_all();
        $data['access']        = cek_akses('delivery');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('delivery/form', $data);
        $this->load->view('templates/footer');
    }

    public function edit($id)
    {
        $this->require_access('delivery', 'edit');

        $delivery_row = $this->Delivery_model->get($id);
        if (!$delivery_row) {
            show_404();
        }

        if ($this->input->method() === 'post') {
            $this->_validate();

            if ($this->form_validation->run() === TRUE) {
                $this->Delivery_model->update($id, $this->_collect_post());
                $this->session->set_flashdata('success', 'Delivery record diperbarui.');
                redirect('delivery');
                return;
            }
        }

        $data['title']         = 'Edit Delivery Record - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['delivery_row']  = $delivery_row;
        $data['jf_list']       = $this->Jf_model->get_all();
        $data['access']        = cek_akses('delivery');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('delivery/form', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel: No. JF<TAB>Tanggal
     * Kirim<TAB>Aktual Kirim<TAB>No. SP<TAB>Jenis SP). Baris dengan
     * pasangan (No. JF + No. SP) yang SUDAH ADA akan DI-REPLACE, bukan
     * ditolak.
     */
    public function bulk()
    {
        $this->require_access('delivery', 'input');

        $result = null;

        if ($this->input->method() === 'post') {
            $raw = (string) $this->input->post('data');

            if (trim($raw) === '') {
                $this->session->set_flashdata('error', 'Data tempelan masih kosong.');
            } else {
                $result = $this->Delivery_model->bulk_upsert(parse_bulk_paste($raw), $this->user['id']);

                if ($result['inserted'] === 0 && $result['updated'] === 0) {
                    $this->session->set_flashdata('error', 'Tidak ada baris yang berhasil diproses. Periksa detail error di bawah.');
                } else {
                    $msg = $result['inserted'] . ' delivery record baru ditambahkan, ' . $result['updated'] . ' data di-replace.';
                    if (!empty($result['errors'])) {
                        $msg .= ' ' . count($result['errors']) . ' baris gagal, lihat detail di bawah.';
                    }
                    $this->session->set_flashdata('success', $msg);
                }
            }
        }

        $data['title']  = 'Tambah Massal Delivery Record - ' . APP_NAME;
        $data['menus']  = $this->menus;
        $data['result'] = $result;
        $data['raw']    = $this->input->post('data');

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('delivery/bulk', $data);
        $this->load->view('templates/footer');
    }

    public function delete($id)
    {
        $this->require_access('delivery', 'delete');

        $delivery_row = $this->Delivery_model->get($id);
        if (!$delivery_row) {
            show_404();
        }

        $this->Delivery_model->delete($id);
        $this->session->set_flashdata('success', 'Delivery record dihapus.');
        redirect('delivery');
    }

    // ---------------------------------------------------------------
    // AJAX -- cantolan stok FG (poin 9 rancangan: mengurangi stok FG
    // hasil monitoring untuk memenuhi satu delivery record tertentu)
    // ---------------------------------------------------------------

    /** Autocomplete sumber stok FG untuk satu JF. GET: jf_id */
    public function fg_search()
    {
        $this->require_access('delivery', 'input');
        $jf_id = (int) $this->input->get('jf_id', true);
        $this->_json($this->Delivery_model->search_stok_fg($jf_id));
    }

    public function fg_list($delivery_id)
    {
        $this->require_access('delivery', 'view');
        $this->_json($this->Delivery_model->get_pemakaian_fg($delivery_id));
    }

    /**
     * Tambah cantolan FG. POST: delivery_id, monitoring_id, qty_pakai.
     * Validasi sisa stok FG bersifat WARNING (soft, konsisten dengan
     * pemakaian bahan di modul monitoring) -- tetap disimpan.
     */
    public function fg_add()
    {
        $this->require_access('delivery', 'input');

        $delivery_id   = (int) $this->input->post('delivery_id', true);
        $monitoring_id = (int) $this->input->post('monitoring_id', true);
        $qty_pakai     = $this->input->post('qty_pakai', true);

        if (!$this->Delivery_model->get($delivery_id) || $qty_pakai === '' || $qty_pakai === null) {
            $this->_json(array('success' => false, 'message' => 'Data cantolan FG tidak lengkap.'), 400);
            return;
        }

        $sisa = $this->Delivery_model->get_sisa_stok_fg($monitoring_id);
        if ($sisa === null) {
            $this->_json(array('success' => false, 'message' => 'Sumber stok FG tidak ditemukan.'), 400);
            return;
        }

        $warning = null;
        if ((float) $qty_pakai > $sisa) {
            $warning = 'Qty pakai (' . $qty_pakai . ') melebihi sisa stok FG (' . $sisa . '). Data tetap disimpan -- mohon cek kembali.';
        }

        $id = $this->Delivery_model->create_pemakaian_fg($delivery_id, $monitoring_id, $qty_pakai, $this->user['id']);
        $this->_json(array('success' => true, 'id' => $id, 'warning' => $warning));
    }

    public function fg_delete($id)
    {
        $this->require_access('delivery', 'delete');
        $this->Delivery_model->delete_pemakaian_fg($id);
        $this->_json(array('success' => true));
    }

    private function _json($data, $http_code = 200)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($http_code)
            ->set_output(json_encode($data));
    }

    private function _validate()
    {
        $this->form_validation->set_rules('jf_id', 'No. JF', 'required|trim|integer');
        $this->form_validation->set_rules('tanggal_kirim', 'Tanggal Kirim', 'required|trim');
        $this->form_validation->set_rules('aktual_kirim', 'Aktual Kirim (Qty)', 'trim|numeric');
        $this->form_validation->set_rules('no_sp', 'No. SP', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('jenis_sp', 'Jenis SP', 'trim|max_length[50]');
    }

    private function _collect_post()
    {
        $aktual_kirim = $this->input->post('aktual_kirim', true);

        return array(
            'jf_id'         => (int) $this->input->post('jf_id', true),
            'tanggal_kirim' => $this->input->post('tanggal_kirim', true),
            'aktual_kirim'  => ($aktual_kirim === '' || $aktual_kirim === null) ? null : $aktual_kirim,
            'no_sp'         => $this->input->post('no_sp', true),
            'jenis_sp'      => $this->input->post('jenis_sp', true) ?: null,
        );
    }
}
