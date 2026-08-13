<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kelengkapan_setor
 *
 * Rekap "department+proses mana yang biasanya lapor untuk suatu JF tapi
 * belum lapor di periode berjalan", lintas SEMUA JF (bukan per-JF spt
 * cek_kelengkapan_periode() aslinya). Modul terpisah dari Monitoring
 * Produksi supaya aksesnya bisa diatur sendiri lewat menu (mis. cuma
 * admin/koordinator yg boleh lihat, bukan semua orang yg boleh lihat
 * Monitoring Produksi) -- diatur lewat User > hak akses modul, sama
 * seperti modul lain, tidak ada mekanisme akses baru.
 *
 * Read-only: hanya method view (can_view), tidak ada input/edit/delete
 * -- ini murni laporan turunan dari trx_monitoring_produksi.
 *
 * Guard department: sama seperti Monitoring_produksi, non-
 * can_view_all_departments hanya melihat outstanding milik department
 * yang di-assign ke dia (Import_model::get_user_allowed_departments()).
 */
class Kelengkapan_setor extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Delivery_model'); // cek_kelengkapan_periode() & get_kelengkapan_setor_report()
        $this->load->model('Import_model');   // reuse get_user_allowed_departments()
    }

    public function index()
    {
        $this->require_access('kelengkapan_setor', 'view');

        $periode  = $this->input->get('periode', true) ?: date('Y-m');
        $dept_ids = $this->Import_model->get_user_allowed_departments($this->user['id']);

        $data['title']   = 'Kelengkapan Setor - ' . APP_NAME;
        $data['menus']   = $this->menus;
        $data['access']  = cek_akses('kelengkapan_setor');
        $data['periode'] = $periode;
        $data['report']  = $this->Delivery_model->get_kelengkapan_setor_report($periode, $dept_ids);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('kelengkapan_setor/index', $data);
        $this->load->view('templates/footer', $data);
    }
}
