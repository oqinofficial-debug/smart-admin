<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import
 *
 * Halaman Import Data Laporan Produksi dari file Excel (.xlsx).
 * URL: /import (lihat routes.php untuk /import/preview & /import/process)
 *
 * Alur 3 langkah:
 *   1. index()   -> form upload file
 *   2. preview() -> baca header file, auto-map ke field tujuan pakai alias
 *                   (lihat Import_alias_model), user boleh koreksi manual,
 *                   tampilkan preview beberapa baris data
 *   3. process() -> baca ulang file lengkap, resolve tiap kode/NIK ke id,
 *                   validasi, lalu insert. Baris yang gagal dilaporkan
 *                   per-baris (tidak menggagalkan seluruh file).
 *
 * File yang diupload disimpan sementara di UPLOAD_TEMP_PATH, dan mapping
 * kolom yang dikonfirmasi user disimpan di session di antara langkah
 * preview -> process (bukan seluruh isi file, supaya session tetap kecil).
 */
class Import extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Import_model');
        $this->load->model('Import_alias_model');
        $this->load->library('Xlsx_reader');

        if (!is_dir(UPLOAD_TEMP_PATH)) {
            @mkdir(UPLOAD_TEMP_PATH, DIR_WRITE_MODE, true);
        }
    }

    public function index()
    {
        $this->require_access('import', 'view');

        $data['title']  = 'Import Data Laporan Produksi - ' . APP_NAME;
        $data['menus']  = $this->menus;
        $data['access'] = cek_akses('import');
        $data['fields'] = $this->Import_alias_model->get_active_alias_map();

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/index', $data);
        $this->load->view('templates/footer');
    }

    public function preview()
    {
        $this->require_access('import', 'input');

        if ($this->input->method() !== 'post' || empty($_FILES['file']['name'])) {
            $this->session->set_flashdata('error', 'Silakan pilih file Excel (.xlsx) terlebih dahulu.');
            redirect('import');
        }

        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $this->session->set_flashdata('error', 'Format file harus .xlsx (Excel 2007 ke atas). File .xls lama tidak didukung, simpan ulang sebagai .xlsx.');
            redirect('import');
        }

        $temp_name = 'import_' . $this->user['id'] . '_' . time() . '_' . mt_rand(1000, 9999) . '.xlsx';
        $temp_path = UPLOAD_TEMP_PATH . $temp_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $temp_path)) {
            $this->session->set_flashdata('error', 'Gagal menyimpan file yang diupload.');
            redirect('import');
        }

        try {
            $rows = $this->xlsx_reader->read($temp_path);
        } catch (Exception $e) {
            @unlink($temp_path);
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('import');
        }

        if (count($rows) < 2) {
            @unlink($temp_path);
            $this->session->set_flashdata('error', 'File tidak berisi data (minimal harus ada baris header + 1 baris data).');
            redirect('import');
        }

        $header_row  = array_shift($rows); // baris pertama = nama kolom
        $data_rows   = $rows;               // sisanya = data
        $alias_map   = $this->Import_alias_model->get_active_alias_map();
        $auto_mapping = $this->_auto_map_header($header_row, $alias_map);

        // simpan di session untuk dipakai lagi di process(), bukan seluruh data
        $this->session->set_userdata('import_temp_file', $temp_name);
        $this->session->set_userdata('import_header_row', $header_row);

        $data['title']        = 'Preview Import - ' . APP_NAME;
        $data['menus']        = $this->menus;
        $data['header_row']   = $header_row;      // ['A' => 'Tanggal', 'B' => 'NIK Operator', ...]
        $data['auto_mapping'] = $auto_mapping;     // ['A' => 'tanggal', 'B' => 'nik_operator', 'C' => null, ...]
        $data['fields']       = $alias_map;        // untuk isi dropdown pilihan field tujuan
        $data['preview_rows'] = array_slice($data_rows, 0, 5);
        $data['total_rows']   = count($data_rows);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/preview', $data);
        $this->load->view('templates/footer');
    }

    public function process()
    {
        $this->require_access('import', 'input');

        $temp_name = $this->session->userdata('import_temp_file');
        $header_row = $this->session->userdata('import_header_row');

        if (!$temp_name || !$header_row) {
            $this->session->set_flashdata('error', 'Sesi import kedaluwarsa, silakan upload ulang file.');
            redirect('import');
        }

        $temp_path = UPLOAD_TEMP_PATH . $temp_name;
        if (!is_file($temp_path)) {
            $this->session->set_flashdata('error', 'File sementara sudah tidak ada, silakan upload ulang.');
            redirect('import');
        }

        // mapping final hasil koreksi user di halaman preview: kolom_huruf => field_key (atau kosong = abaikan)
        $mapping = $this->input->post('mapping');
        if (!is_array($mapping)) {
            $mapping = array();
        }

        $alias_map = $this->Import_alias_model->get_active_alias_map();

        try {
            $rows = $this->xlsx_reader->read($temp_path);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca ulang file: ' . $e->getMessage());
            redirect('import');
        }
        array_shift($rows); // buang baris header, sudah dipakai untuk mapping

        $allowed_departments = $this->Import_model->get_user_allowed_departments($this->user['id']);

        $success_count = 0;
        $errors = array(); // ['row' => nomor baris data (mulai 1), 'message' => ..]

        foreach ($rows as $i => $raw_row) {
            $row_number = $i + 1;

            // lewati baris yang benar-benar kosong (semua sel kosong)
            if (count(array_filter($raw_row, function ($v) { return trim((string) $v) !== ''; })) === 0) {
                continue;
            }

            list($resolved, $row_errors) = $this->_resolve_row($raw_row, $mapping, $alias_map, $allowed_departments);

            if (!empty($row_errors)) {
                $errors[] = array('row' => $row_number, 'message' => implode('; ', $row_errors));
                continue;
            }

            try {
                $resolved['inputer_id'] = $this->user['id'];
                $this->Import_model->insert_laporan($resolved);
                $success_count++;
            } catch (Exception $e) {
                $errors[] = array('row' => $row_number, 'message' => 'Gagal disimpan ke database: ' . $e->getMessage());
            }
        }

        @unlink($temp_path);
        $this->session->unset_userdata('import_temp_file');
        $this->session->unset_userdata('import_header_row');

        $data['title']         = 'Hasil Import - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['success_count'] = $success_count;
        $data['errors']        = $errors;
        $data['total_rows']    = count($rows);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/result', $data);
        $this->load->view('templates/footer');
    }

    // ---------------------------------------------------------------
    // Helper privat
    // ---------------------------------------------------------------

    /**
     * Cocokkan tiap header kolom Excel ke field_key berdasarkan alias
     * (perbandingan case-insensitive, trim spasi). Kolom yang tidak
     * cocok alias manapun di-set null (nanti dipetakan manual oleh user
     * di halaman preview, atau memang sengaja diabaikan).
     */
    private function _auto_map_header(array $header_row, array $alias_map)
    {
        // balik index: alias (lowercase) => field_key, biar pencarian O(1)
        $lookup = array();
        foreach ($alias_map as $field_key => $info) {
            foreach ($info['aliases'] as $alias) {
                $lookup[strtolower(trim($alias))] = $field_key;
            }
        }

        $mapping = array();
        foreach ($header_row as $col_letter => $header_text) {
            $key = strtolower(trim($header_text));
            $mapping[$col_letter] = isset($lookup[$key]) ? $lookup[$key] : null;
        }

        return $mapping;
    }

    /**
     * Ubah satu baris mentah (huruf kolom => nilai) jadi array siap-insert
     * ke trx_laporan_produksi, dengan resolve lookup & validasi.
     *
     * @return array [ $resolved_data_or_empty, $error_messages ]
     */
    private function _resolve_row(array $raw_row, array $mapping, array $alias_map, $allowed_departments)
    {
        $errors = array();
        $val = array(); // field_key => nilai mentah dari excel

        foreach ($mapping as $col_letter => $field_key) {
            if ($field_key) {
                $val[$field_key] = isset($raw_row[$col_letter]) ? trim((string) $raw_row[$col_letter]) : '';
            }
        }

        // field wajib
        foreach ($alias_map as $field_key => $info) {
            if ($info['required'] && (!isset($val[$field_key]) || $val[$field_key] === '')) {
                $errors[] = $info['label'] . ' wajib diisi';
            }
        }

        if (!empty($errors)) {
            return array(array(), $errors);
        }

        $resolved = array();

        // tanggal
        if (!empty($val['tanggal'])) {
            $tanggal = $this->xlsx_reader->excel_value_to_date($val['tanggal']);
            if (!$tanggal) {
                $errors[] = 'Format tanggal tidak dikenali: "' . $val['tanggal'] . '"';
            }
            $resolved['tanggal'] = $tanggal;
        }

        // departemen (wajib)
        if (!empty($val['kode_department'])) {
            $dept_id = $this->Import_model->find_department_id($val['kode_department']);
            if (!$dept_id) {
                $errors[] = 'Kode departemen "' . $val['kode_department'] . '" tidak ditemukan';
            } elseif (is_array($allowed_departments) && !in_array($dept_id, $allowed_departments)) {
                $errors[] = 'Anda tidak punya akses ke departemen "' . $val['kode_department'] . '"';
            }
            $resolved['department_id'] = $dept_id;
        }

        // operator (wajib)
        if (!empty($val['nik_operator'])) {
            $op_id = $this->Import_model->find_karyawan_id($val['nik_operator']);
            if (!$op_id) {
                $errors[] = 'NIK operator "' . $val['nik_operator'] . '" tidak ditemukan';
            }
            $resolved['operator_id'] = $op_id;
        }

        // field lookup opsional -- kalau diisi tapi kodenya tidak ketemu, dianggap error
        // (supaya typo tidak diam-diam jadi NULL di database)
        $optional_lookups = array(
            'nik_spv'               => array('find_karyawan_id',        'spv_id',               'NIK SPV'),
            'nik_ll'                => array('find_karyawan_id',        'll_id',                'NIK LL'),
            'kode_shift'            => array('find_shift_id',           'shift_id',             'Kode shift'),
            'kode_jf'               => array('find_jf_id',              'jf_id',                'Kode JF'),
            'kode_mesin'            => array('find_mesin_id',           'mesin_id',              'Kode mesin'),
            'kode_aktivitas'        => array('find_aktivitas_id',       'kode_aktivitas_id',     'Kode aktivitas'),
            'kode_proses'           => array('find_proses_id',          'proses_id',             'Kode proses'),
            'kode_pekerjaan_borong' => array('find_pekerjaan_borong_id','pekerjaan_borong_id',   'Kode pekerjaan borong'),
        );

        foreach ($optional_lookups as $field_key => $conf) {
            list($method, $db_col, $label) = $conf;
            if (!empty($val[$field_key])) {
                $id = $this->Import_model->$method($val[$field_key]);
                if (!$id) {
                    $errors[] = $label . ' "' . $val[$field_key] . '" tidak ditemukan';
                }
                $resolved[$db_col] = $id;
            } else {
                $resolved[$db_col] = null;
            }
        }

        // waktu
        $resolved['jam_mulai']   = !empty($val['jam_mulai'])   ? $this->xlsx_reader->excel_value_to_time($val['jam_mulai'])   : null;
        $resolved['jam_selesai'] = !empty($val['jam_selesai']) ? $this->xlsx_reader->excel_value_to_time($val['jam_selesai']) : null;

        // angka
        $numeric_fields = array('durasi', 'target_jam', 'input_qty', 'input_pcs', 'input_sheet', 'qc_sampling', 'waste', 'dead', 'error_qty', 'good_pcs');
        foreach ($numeric_fields as $field_key) {
            if (!empty($val[$field_key])) {
                $normalized = str_replace(',', '.', $val[$field_key]);
                if (!is_numeric($normalized)) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field_key)) . ' harus berupa angka, ditemukan "' . $val[$field_key] . '"';
                    $resolved[$field_key] = null;
                } else {
                    $resolved[$field_key] = (float) $normalized;
                }
            } else {
                $resolved[$field_key] = null;
            }
        }

        // teks bebas
        $resolved['keterangan'] = !empty($val['keterangan']) ? $val['keterangan'] : null;

        // boolean
        $resolved['is_public'] = false;
        if (!empty($val['is_public'])) {
            $truthy = array('y', 'ya', 'yes', 'true', '1', 't', 'publik');
            $resolved['is_public'] = in_array(strtolower($val['is_public']), $truthy, true);
        }

        if (!empty($errors)) {
            return array(array(), $errors);
        }

        return array($resolved, array());
    }
}
