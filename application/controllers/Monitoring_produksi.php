<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Monitoring_produksi
 *
 * Halaman rekap trx_laporan_produksi per (jf_id, periode, department_id,
 * proses_id), pencantolan pemakaian bahan (RAW/WIP), dan penentuan
 * status_output. Lihat docs/production_monitoring/RANCANGAN_DAN_STATUS.md
 * untuk rancangan lengkap.
 *
 * Guard department: non-can_view_all_departments hanya boleh melihat/
 * mengubah baris monitoring yang department_id-nya ada di
 * Import_model::get_user_allowed_departments(). Guard ini dicek ulang di
 * setiap endpoint (bukan hanya index/detail) supaya AJAX tidak bisa
 * dipanggil manual untuk department lain.
 */
class Monitoring_produksi extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Monitoring_model');
        $this->load->model('Pemakaian_material_model');
        $this->load->model('Material_raw_model');
        $this->load->model('Jf_model');
        $this->load->model('Import_model'); // reuse get_user_allowed_departments()
    }

    // ---------------------------------------------------------------
    // Halaman
    // ---------------------------------------------------------------

    public function index()
    {
        $this->require_access('monitoring_produksi', 'view');

        $periode = $this->input->get('periode', true) ?: date('Y-m');
        $dept_ids = $this->Import_model->get_user_allowed_departments($this->user['id']);

        $data['title']    = 'Production Monitoring Report - ' . APP_NAME;
        $data['menus']    = $this->menus;
        $data['access']   = cek_akses('monitoring_produksi');
        $data['periode']  = $periode;
        $data['jf_list']  = $this->Monitoring_model->get_active_jf_by_periode($periode, $dept_ids);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('monitoring_produksi/index', $data);
        $this->load->view('templates/footer');
    }

    public function detail($jf_id, $periode)
    {
        $this->require_access('monitoring_produksi', 'view');

        $dept_ids = $this->Import_model->get_user_allowed_departments($this->user['id']);
        $rows = $this->Monitoring_model->get_detail($jf_id, $periode, $dept_ids);

        if (empty($rows)) {
            show_404();
        }

        $jf = $this->Jf_model->get($jf_id);

        $data['title']       = 'Detail Monitoring - ' . ($jf ? $jf['jf'] : '') . ' - ' . APP_NAME;
        $data['menus']       = $this->menus;
        $data['access']      = cek_akses('monitoring_produksi');
        $data['jf']          = $jf;
        $data['periode']     = $periode;
        $data['rows']        = $rows;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('monitoring_produksi/detail', $data);
        $this->load->view('templates/footer');
    }

    // ---------------------------------------------------------------
    // AJAX -- pencantolan bahan
    // ---------------------------------------------------------------

    /** Autocomplete sumber WIP lintas JF. GET: q */
    public function search_wip()
    {
        $this->require_access('monitoring_produksi', 'input');
        $keyword = $this->input->get('q', true) ?: '';
        $this->_json($this->Pemakaian_material_model->search_sumber_wip($keyword));
    }

    /** Autocomplete sumber FG (stok trx_monitoring_produksi status_output=FINISH_GOOD_STOK). GET: q */
    public function search_fg()
    {
        $this->require_access('monitoring_produksi', 'input');
        $keyword = $this->input->get('q', true) ?: '';
        $this->_json($this->Pemakaian_material_model->search_sumber_fg($keyword));
    }

    /** Autocomplete master RAW. GET: q */
    public function search_raw()
    {
        $this->require_access('monitoring_produksi', 'input');
        $keyword = $this->input->get('q', true) ?: '';
        $all = $this->Material_raw_model->get_all();
        if ($keyword !== '') {
            $all = array_values(array_filter($all, function ($m) use ($keyword) {
                return stripos($m['nama_material'], $keyword) !== false
                    || stripos($m['kode_material'], $keyword) !== false;
            }));
        }
        $this->_json($all);
    }

    /**
     * Tambah cantolan bahan (RAW, WIP, atau FG). POST:
     * monitoring_id, jenis_material (RAW|WIP|FG), material_raw_id (jika RAW),
     * sumber_monitoring_id (jika WIP/FG), qty_pakai, satuan, keterangan.
     * FG = ambil dari stok trx_monitoring_produksi berstatus
     * FINISH_GOOD_STOK sebagai bahan proses lain (bukan kirim customer).
     * Validasi sisa sumber WIP/FG bersifat WARNING (poin 7 rancangan) --
     * tetap disimpan, tapi response membawa flag `warning` kalau qty_pakai
     * melebihi sisa, supaya UI bisa tampilkan konfirmasi non-blocking.
     */
    public function pemakaian_add()
    {
        $this->require_access('monitoring_produksi', 'input');

        $monitoring_id = (int) $this->input->post('monitoring_id', true);
        $jenis         = $this->input->post('jenis_material', true);
        $qty_pakai     = $this->input->post('qty_pakai', true);
        $satuan        = $this->input->post('satuan', true);
        $keterangan    = $this->input->post('keterangan', true) ?: null;

        $monitoring = $this->_guarded_monitoring($monitoring_id);
        if (!$monitoring) {
            $this->_json(array('success' => false, 'message' => 'Baris monitoring tidak ditemukan atau bukan department Anda.'), 403);
            return;
        }
        if (!in_array($jenis, array('RAW', 'WIP', 'FG'), true) || $qty_pakai === '' || $qty_pakai === null || $satuan === '') {
            $this->_json(array('success' => false, 'message' => 'Data pemakaian bahan tidak lengkap.'), 400);
            return;
        }

        $warning = null;

        if ($jenis === 'RAW') {
            $material_raw_id = (int) $this->input->post('material_raw_id', true);
            if (!$this->Material_raw_model->get($material_raw_id)) {
                $this->_json(array('success' => false, 'message' => 'Material raw tidak ditemukan.'), 400);
                return;
            }
            $id = $this->Pemakaian_material_model->create_raw(
                $monitoring_id, $material_raw_id, $qty_pakai, $satuan, $keterangan, $this->user['id']
            );
        } elseif ($jenis === 'WIP') {
            $sumber_id = (int) $this->input->post('sumber_monitoring_id', true);
            $sisa = $this->Pemakaian_material_model->get_sisa_sumber_wip($sumber_id);
            if ($sisa === null) {
                $this->_json(array('success' => false, 'message' => 'Sumber WIP tidak ditemukan.'), 400);
                return;
            }
            if ((float) $qty_pakai > $sisa) {
                $warning = 'Qty pakai (' . $qty_pakai . ') melebihi sisa sumber WIP (' . $sisa . '). Data tetap disimpan -- mohon cek kembali.';
            }
            $id = $this->Pemakaian_material_model->create_wip(
                $monitoring_id, $sumber_id, $qty_pakai, $satuan, $keterangan, $this->user['id']
            );
        } else { // FG
            $sumber_id = (int) $this->input->post('sumber_monitoring_id', true);
            $sisa = $this->Pemakaian_material_model->get_sisa_sumber_fg($sumber_id);
            if ($sisa === null) {
                $this->_json(array('success' => false, 'message' => 'Sumber FG tidak ditemukan atau bukan stok Finish Good.'), 400);
                return;
            }
            if ((float) $qty_pakai > $sisa) {
                $warning = 'Qty pakai (' . $qty_pakai . ') melebihi sisa stok FG (' . $sisa . ', sudah memperhitungkan pemakaian di modul Delivery). Data tetap disimpan -- mohon cek kembali.';
            }
            $id = $this->Pemakaian_material_model->create_fg(
                $monitoring_id, $sumber_id, $qty_pakai, $satuan, $keterangan, $this->user['id']
            );
        }

        $this->_json(array('success' => true, 'id' => $id, 'warning' => $warning));
    }

    public function pemakaian_delete($id)
    {
        $this->require_access('monitoring_produksi', 'delete');

        // guard: pastikan baris pemakaian ini milik monitoring di department user
        $rows = $this->db->where('id', $id)->get('trx_pemakaian_material')->row_array();
        if (!$rows || !$this->_guarded_monitoring($rows['monitoring_id'])) {
            $this->_json(array('success' => false, 'message' => 'Data tidak ditemukan atau bukan department Anda.'), 403);
            return;
        }

        $this->Pemakaian_material_model->delete($id);
        $this->_json(array('success' => true));
    }

    // ---------------------------------------------------------------
    // AJAX -- edit realisasi & status_output
    // ---------------------------------------------------------------

    /**
     * Update realisasi_* manual. POST: monitoring_id, realisasi_input_qty,
     * realisasi_qc_sampling, realisasi_waste, realisasi_dead,
     * realisasi_error, realisasi_good_qty, force (opsional, '1' untuk
     * konfirmasi lewati warning keras borong -- poin 8 rancangan).
     */
    public function realisasi_update()
    {
        $this->require_access('monitoring_produksi', 'edit');

        $monitoring_id = (int) $this->input->post('monitoring_id', true);
        $force         = $this->input->post('force', true) === '1';

        $monitoring = $this->_guarded_monitoring($monitoring_id);
        if (!$monitoring) {
            $this->_json(array('success' => false, 'message' => 'Baris monitoring tidak ditemukan atau bukan department Anda.'), 403);
            return;
        }

        $realisasi = array();
        foreach (array('input_qty', 'qc_sampling', 'waste', 'dead', 'error', 'good_qty') as $c) {
            $val = $this->input->post('realisasi_' . $c, true);
            if ($val !== null && $val !== '') {
                $realisasi['realisasi_' . $c] = $val;
            }
        }

        // Warning KERAS poin 8: good_qty diturunkan di bawah total kerjaan BORONG.
        if (isset($realisasi['realisasi_good_qty'])) {
            $total_borong = $this->Monitoring_model->get_total_borong_good_qty($monitoring_id);
            if ((float) $realisasi['realisasi_good_qty'] < $total_borong && !$force) {
                $this->_json(array(
                    'success'       => false,
                    'warning_keras' => true,
                    'message'       => 'Good qty (' . $realisasi['realisasi_good_qty'] . ') di bawah total sudah dikerjakan karyawan BORONG (' . $total_borong . '). Kirim ulang dengan force=1 untuk tetap lanjut.',
                ));
                return;
            }
        }

        $affected = $this->Monitoring_model->update_realisasi($monitoring_id, $realisasi, $this->user['id']);
        $this->_json(array('success' => (bool) $affected !== false));
    }

    /** POST: monitoring_id, status (PROSES_SELANJUTNYA|WIP_STOK|FINISH_GOOD_STOK|'') */
    public function status_output_set()
    {
        $this->require_access('monitoring_produksi', 'edit');

        $monitoring_id = (int) $this->input->post('monitoring_id', true);
        $status = $this->input->post('status', true);
        $status = ($status === '' ) ? null : $status;

        if (!$this->_guarded_monitoring($monitoring_id)) {
            $this->_json(array('success' => false, 'message' => 'Baris monitoring tidak ditemukan atau bukan department Anda.'), 403);
            return;
        }

        $ok = $this->Monitoring_model->set_status_output($monitoring_id, $status);
        $this->_json(array('success' => $ok !== false));
    }

    // ---------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------

    /** Ambil baris monitoring & pastikan department-nya boleh diakses user ini. */
    private function _guarded_monitoring($monitoring_id)
    {
        $m = $this->Monitoring_model->get($monitoring_id);
        if (!$m) {
            return null;
        }
        $dept_ids = $this->Import_model->get_user_allowed_departments($this->user['id']);
        if ($dept_ids !== null && !in_array((int) $m['department_id'], $dept_ids, true)) {
            return null;
        }
        return $m;
    }

    private function _json($data, $http_code = 200)
    {
        $this->output
            ->set_content_type('application/json')
            ->set_status_header($http_code)
            ->set_output(json_encode($data));
    }
}