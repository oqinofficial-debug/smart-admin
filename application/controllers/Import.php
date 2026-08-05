<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import
 *
 * Halaman Import Data Laporan Produksi dari file Excel/CSV/TXT.
 * URL dasar: /import (lihat routes.php untuk action lainnya)
 *
 * Alur (4 langkah, langkah 2 dilewati otomatis kalau file cuma 1 sheet):
 *   1. index()               -> form upload file + pilih mode periode/tanggal
 *   2. select_sheet()/        -> HANYA muncul kalau file punya >1 sheet
 *      select_sheet_confirm()   (xlsx/xls multi-sheet)
 *   3. preview()              -> baca header file, auto-map ke field tujuan
 *                                pakai alias, user boleh koreksi manual
 *   4. process()               -> baca ulang file lengkap, filter sesuai
 *                                mode periode/tanggal, resolve tiap kode/NIK
 *                                (bulk, bukan per-baris), validasi, lalu
 *                                insert secara batch. Baris yang gagal
 *                                dilaporkan per-baris; baris di luar
 *                                periode/tanggal terpilih dilewati (bukan
 *                                error).
 *
 * Format didukung: .xlsx, .xls (Excel 97-2003), .csv, .txt
 * File yang diupload disimpan sementara di UPLOAD_TEMP_PATH, dan semua
 * state antar langkah (path file, format, sheet, mode periode/tanggal,
 * mapping kolom) disimpan di session -- bukan seluruh isi file, supaya
 * session tetap kecil.
 */
class Import extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Import_model');
        $this->load->model('Import_alias_model');
        $this->load->model('Import_batch_model');
        $this->load->library(array('Xlsx_reader', 'Xls_reader', 'Csv_reader', 'File_reader_factory', 'Value_converter'));

        if (!is_dir(UPLOAD_TEMP_PATH)) {
            @mkdir(UPLOAD_TEMP_PATH, DIR_WRITE_MODE, true);
        }
    }

    public function index()
    {
        $this->require_access('import', 'view');

        $data['title']            = 'Import Data Laporan Produksi - ' . APP_NAME;
        $data['menus']            = $this->menus;
        $data['access']           = cek_akses('import');
        $data['fields']           = $this->Import_alias_model->get_active_alias_map();
        $data['supported_ext']    = File_reader_factory::SUPPORTED_EXTENSIONS;
        $data['riwayat_import']   = $this->Import_batch_model->get_recent(10);

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/index', $data);
        $this->load->view('templates/footer');
    }

    public function preview()
    {
        $this->require_access('import', 'input');

        if ($this->input->method() !== 'post' || empty($_FILES['file']['name'])) {
            $this->session->set_flashdata('error', 'Silakan pilih file terlebih dahulu.');
            redirect('import');
        }

        $original_name = $_FILES['file']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

        if (!in_array($ext, File_reader_factory::SUPPORTED_EXTENSIONS, true)) {
            $this->session->set_flashdata('error', 'Format file ".' . $ext . '" tidak didukung. Format yang didukung: .xlsx, .xls, .csv, .txt');
            redirect('import');
        }

        // ambil & validasi mode periode/tanggal dari form index sebelum simpan file,
        // supaya user tidak upload file besar dulu baru ketahuan input mode-nya salah
        $period_filter = $this->_read_period_filter_from_post();
        if ($period_filter === false) {
            redirect('import'); // pesan error sudah diset di dalam _read_period_filter_from_post()
        }

        $temp_name = 'import_' . $this->user['id'] . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $temp_path = UPLOAD_TEMP_PATH . $temp_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $temp_path)) {
            $this->session->set_flashdata('error', 'Gagal menyimpan file yang diupload.');
            redirect('import');
        }

        try {
            File_reader_factory::assert_extension_matches_content($temp_path, $ext);
            $reader = File_reader_factory::make($ext);
            $sheets = $reader->list_sheets($temp_path);
        } catch (Exception $e) {
            @unlink($temp_path);
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('import');
        }

        // simpan state yang dibutuhkan lintas langkah (bukan isi file)
        $this->session->set_userdata('import_temp_file', $temp_name);
        $this->session->set_userdata('import_original_name', $original_name);
        $this->session->set_userdata('import_ext', $ext);
        $this->session->set_userdata('import_period_filter', $period_filter);

        if (count($sheets) > 1) {
            // cek dulu apakah nama salah satu sheet cocok dengan daftar nama
            // sheet default yang dikelola di /import/alias -- kalau persis 1
            // yang cocok, langsung pakai itu tanpa minta user pilih manual
            $matched_sheet = $this->Import_alias_model->find_matching_sheet($sheets);
            if ($matched_sheet !== null) {
                $this->session->set_userdata('import_sheet', $matched_sheet['name']);
                $this->_render_column_mapping_preview($temp_path, $ext, $matched_sheet['name']);
                return;
            }

            // tidak ada yang cocok otomatis -> minta user pilih manual
            $data['title']  = 'Pilih Sheet - ' . APP_NAME;
            $data['menus']  = $this->menus;
            $data['sheets'] = $sheets;

            $this->load->view('templates/header', $data);
            $this->load->view('templates/sidebar', $data);
            $this->load->view('import/select_sheet', $data);
            $this->load->view('templates/footer');
            return;
        }

        // cuma 1 sheet (atau csv/txt) -> langsung lanjut ke preview mapping kolom
        $sheet_name = isset($sheets[0]['name']) ? $sheets[0]['name'] : null;
        $this->session->set_userdata('import_sheet', $sheet_name);
        $this->_render_column_mapping_preview($temp_path, $ext, $sheet_name);
    }

    /**
     * Dipanggil dari form pilih sheet (hanya muncul untuk file multi-sheet).
     */
    public function select_sheet_confirm()
    {
        $this->require_access('import', 'input');

        $temp_name = $this->session->userdata('import_temp_file');
        $ext = $this->session->userdata('import_ext');
        $sheet_name = $this->input->post('sheet_name');

        if (!$temp_name || !$ext || !$sheet_name) {
            $this->session->set_flashdata('error', 'Sesi import kedaluwarsa atau sheet belum dipilih, silakan upload ulang file.');
            redirect('import');
        }

        $temp_path = UPLOAD_TEMP_PATH . $temp_name;
        if (!is_file($temp_path)) {
            $this->session->set_flashdata('error', 'File sementara sudah tidak ada, silakan upload ulang.');
            redirect('import');
        }

        $this->session->set_userdata('import_sheet', $sheet_name);
        $this->_render_column_mapping_preview($temp_path, $ext, $sheet_name);
    }

    public function process()
    {
        $this->require_access('import', 'input');

        $temp_name = $this->session->userdata('import_temp_file');
        $ext = $this->session->userdata('import_ext');
        $sheet_name = $this->session->userdata('import_sheet');
        $original_name = $this->session->userdata('import_original_name');
        $period_filter = $this->session->userdata('import_period_filter');

        if (!$temp_name || !$ext) {
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
            $reader = File_reader_factory::make($ext);
            $rows = $reader->read($temp_path, $sheet_name);
        } catch (Exception $e) {
            $this->session->set_flashdata('error', 'Gagal membaca ulang file: ' . $e->getMessage());
            redirect('import');
        }
        array_shift($rows); // buang baris header, sudah dipakai untuk mapping

        $allowed_departments = $this->Import_model->get_user_allowed_departments($this->user['id']);

        // --- Percepatan #1: resolve semua kode/NIK unik SEKALIGUS (bulk),
        //     bukan query satu-satu di dalam loop baris ---
        $this->_preload_bulk_lookups($rows, $mapping);

        // --- buat catatan batch import lebih dulu, supaya tiap baris yang
        //     berhasil bisa ditandai import_batch_id-nya ---
        $batch_id = $this->Import_batch_model->create(array(
            'nama_file'       => $original_name,
            'format_file'     => $ext,
            'sheet_name'      => $sheet_name,
            'mode'            => $period_filter['mode'],
            'periode'         => $period_filter['periode'],
            'tanggal_mulai'   => $period_filter['tanggal_mulai'],
            'tanggal_selesai' => $period_filter['tanggal_selesai'],
            'replace_periode' => $period_filter['replace_periode'],
            'user_id'         => $this->user['id'],
        ));

        $success_rows = array(); // ditampung dulu, di-insert sekaligus (batch) di akhir
        $errors = array();       // ['row' => nomor baris data, 'message' => ..]
        $skipped_count = 0;      // di luar periode/rentang tanggal yang dipilih

        foreach ($rows as $i => $raw_row) {
            $row_number = $i + 1;

            // lewati baris yang benar-benar kosong (semua sel kosong)
            if (count(array_filter($raw_row, function ($v) { return trim((string) $v) !== ''; })) === 0) {
                continue;
            }

            list($resolved, $row_errors, $skipped) = $this->_resolve_row($raw_row, $mapping, $alias_map, $allowed_departments, $period_filter);

            if ($skipped) {
                $skipped_count++;
                continue;
            }

            if (!empty($row_errors)) {
                $errors[] = array('row' => $row_number, 'message' => implode('; ', $row_errors));
                continue;
            }

            $resolved['inputer_id'] = $this->user['id'];
            $resolved['import_batch_id'] = $batch_id;
            $success_rows[] = $resolved;
        }

        // --- Percepatan #2: insert semua baris valid dalam 1 transaksi
        //     memakai insert_batch (bukan loop insert satu-satu). Kalau mode
        //     "timpa periode ini" aktif, penghapusan data lama juga masuk
        //     dalam transaksi yang sama supaya atomik. ---
        $pre_delete = null;
        if ($period_filter['mode'] === 'periode' && $period_filter['replace_periode']) {
            $pre_delete = array(
                'periode'        => $period_filter['periode'],
                'department_ids' => $allowed_departments,
            );
        }

        $success_count = 0;
        try {
            $result = $this->Import_model->insert_batch_transactional($success_rows, 500, $pre_delete);
            $success_count = $result['inserted'];
        } catch (Exception $e) {
            $this->Import_batch_model->update_result($batch_id, array(
                'total_baris' => count($rows), 'sukses' => 0, 'gagal' => count($rows), 'dilewati' => $skipped_count, 'status' => 'gagal',
            ));
            @unlink($temp_path);
            $this->_clear_import_session();
            $this->session->set_flashdata('error', $e->getMessage());
            redirect('import');
        }

        $this->Import_batch_model->update_result($batch_id, array(
            'total_baris' => count($rows),
            'sukses'      => $success_count,
            'gagal'       => count($errors),
            'dilewati'    => $skipped_count,
            'status'      => 'selesai',
        ));

        @unlink($temp_path);
        $this->_clear_import_session();

        $data['title']         = 'Hasil Import - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['success_count'] = $success_count;
        $data['errors']        = $errors;
        $data['skipped_count'] = $skipped_count;
        $data['total_rows']    = count($rows);
        $data['period_filter'] = $period_filter;

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/result', $data);
        $this->load->view('templates/footer');
    }

    // ---------------------------------------------------------------
    // Helper privat
    // ---------------------------------------------------------------

    private function _clear_import_session()
    {
        $this->session->unset_userdata('import_temp_file');
        $this->session->unset_userdata('import_original_name');
        $this->session->unset_userdata('import_ext');
        $this->session->unset_userdata('import_sheet');
        $this->session->unset_userdata('import_period_filter');
    }

    /**
     * Baca & validasi input mode periode/tanggal dari form index.
     * @return array|false array siap simpan ke session, atau false kalau invalid
     *                      (pesan error sudah di-set ke flashdata).
     */
    private function _read_period_filter_from_post()
    {
        $mode = $this->input->post('import_mode'); // all | periode | range
        if (!in_array($mode, array('all', 'periode', 'range'), true)) {
            $mode = 'all';
        }

        $filter = array(
            'mode' => $mode,
            'periode' => null,
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'replace_periode' => false,
        );

        if ($mode === 'periode') {
            $periode = trim((string) $this->input->post('periode'));
            if (!$this->value_converter->is_valid_periode($periode)) {
                $this->session->set_flashdata('error', 'Periode harus diisi dengan format tahun-bulan (YYYY-MM), misal 2026-07.');
                return false;
            }
            $filter['periode'] = $periode;
            $filter['replace_periode'] = $this->input->post('replace_periode') ? true : false;
        } elseif ($mode === 'range') {
            $mulai = trim((string) $this->input->post('tanggal_mulai'));
            $selesai = trim((string) $this->input->post('tanggal_selesai'));
            if ($mulai === '' || $selesai === '' || $mulai > $selesai) {
                $this->session->set_flashdata('error', 'Rentang tanggal tidak valid. Pastikan "dari" tidak lebih besar dari "sampai".');
                return false;
            }
            $filter['tanggal_mulai'] = $mulai;
            $filter['tanggal_selesai'] = $selesai;
        }

        return $filter;
    }

    private function _render_column_mapping_preview($temp_path, $ext, $sheet_name)
    {
        try {
            $reader = File_reader_factory::make($ext);
            $rows = $reader->read($temp_path, $sheet_name);
        } catch (Exception $e) {
            @unlink($temp_path);
            $this->_clear_import_session();
            $this->session->set_flashdata('error', 'Gagal membaca file: ' . $e->getMessage());
            redirect('import');
        }

        if (count($rows) < 2) {
            @unlink($temp_path);
            $this->_clear_import_session();
            $this->session->set_flashdata('error', 'File/sheet ini tidak berisi data (minimal harus ada baris header + 1 baris data).');
            redirect('import');
        }

        $header_row = array_shift($rows); // baris pertama = nama kolom
        $data_rows = $rows;                // sisanya = data
        $alias_map = $this->Import_alias_model->get_active_alias_map();
        $auto_mapping = $this->_auto_map_header($header_row, $alias_map);

        $this->session->set_userdata('import_header_row', $header_row);

        $data['title']         = 'Preview Import - ' . APP_NAME;
        $data['menus']         = $this->menus;
        $data['header_row']    = $header_row;      // ['A' => 'Tanggal', 'B' => 'NIK Operator', ...]
        $data['auto_mapping']  = $auto_mapping;     // ['A' => 'tanggal', 'B' => 'nik_operator', 'C' => null, ...]
        $data['fields']        = $alias_map;        // untuk isi dropdown pilihan field tujuan
        $data['preview_rows']  = array_slice($data_rows, 0, 5);
        $data['total_rows']    = count($data_rows);
        $data['sheet_name']    = $sheet_name;
        $data['ext']           = $ext;
        $data['period_filter'] = $this->session->userdata('import_period_filter');

        $data['periode_summary'] = null;
        if ($data['period_filter']['mode'] === 'periode') {
            $data['periode_summary'] = $this->Import_batch_model->get_periode_summary($data['period_filter']['periode']);
        }

        $this->load->view('templates/header', $data);
        $this->load->view('templates/sidebar', $data);
        $this->load->view('import/preview', $data);
        $this->load->view('templates/footer');
    }

    /**
     * Cocokkan tiap header kolom ke field_key berdasarkan alias
     * (perbandingan case-insensitive, trim spasi). Kolom yang tidak
     * cocok alias manapun di-set null (nanti dipetakan manual oleh user
     * di halaman preview, atau memang sengaja diabaikan).
     */
    private function _auto_map_header(array $header_row, array $alias_map)
    {
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
     * Kumpulkan semua nilai unik per kolom lookup dari SELURUH baris data,
     * lalu resolve sekaligus (1 query per grup) -- ini yang membuat import
     * ribuan baris jadi cepat, dibanding query per baris seperti sebelumnya.
     */
    private function _preload_bulk_lookups(array $rows, array $mapping)
    {
        $lookup_fields = array(
            'kode_department'       => 'department',
            'nik_operator'          => 'karyawan',
            'nik_spv'               => 'karyawan',
            'nik_ll'                => 'karyawan',
            'kode_shift'            => 'shift',
            'kode_jf'               => 'jf',
            'kode_mesin'            => 'mesin',
            'kode_aktivitas'        => 'aktivitas',
            'kode_proses'           => 'proses',
            'kode_pekerjaan_borong' => 'pekerjaan_borong',
        );

        // field_key -> huruf kolom (kebalikan dari $mapping)
        $col_by_field = array_flip(array_filter($mapping));

        $values_by_group = array();
        foreach ($lookup_fields as $field_key => $group) {
            if (!isset($col_by_field[$field_key])) {
                continue;
            }
            $col_letter = $col_by_field[$field_key];
            $values = array();
            foreach ($rows as $row) {
                if (!empty($row[$col_letter])) {
                    $values[] = trim((string) $row[$col_letter]);
                }
            }
            if (!empty($values)) {
                $values_by_group[$group] = isset($values_by_group[$group])
                    ? array_merge($values_by_group[$group], $values)
                    : $values;
            }
        }

        $this->Import_model->preload_lookup_cache($values_by_group);
    }

    /**
     * @return bool apakah tanggal masuk dalam filter periode/rentang yang dipilih
     */
    private function _tanggal_masuk_filter($tanggal_ymd, array $period_filter)
    {
        if ($period_filter['mode'] === 'all' || !$tanggal_ymd) {
            return true;
        }
        if ($period_filter['mode'] === 'periode') {
            return substr($tanggal_ymd, 0, 7) === $period_filter['periode'];
        }
        if ($period_filter['mode'] === 'range') {
            return $tanggal_ymd >= $period_filter['tanggal_mulai'] && $tanggal_ymd <= $period_filter['tanggal_selesai'];
        }
        return true;
    }

    /**
     * Ubah satu baris mentah (huruf kolom => nilai) jadi array siap-insert
     * ke trx_laporan_produksi, dengan resolve lookup, validasi, dan filter
     * periode/tanggal.
     *
     * @return array [ $resolved_data_or_empty, $error_messages, $skipped ]
     */
    private function _resolve_row(array $raw_row, array $mapping, array $alias_map, $allowed_departments, array $period_filter)
    {
        $errors = array();
        $val = array(); // field_key => nilai mentah dari file

        foreach ($mapping as $col_letter => $field_key) {
            if ($field_key) {
                $val[$field_key] = isset($raw_row[$col_letter]) ? trim((string) $raw_row[$col_letter]) : '';
            }
        }

        // --- filter periode/rentang tanggal, sebelum validasi field lain
        //     supaya baris di luar rentang tidak dianggap "error" ---
        if ($period_filter['mode'] !== 'all' && !empty($val['tanggal'])) {
            $tanggal_check = $this->value_converter->to_date($val['tanggal']);
            if ($tanggal_check && !$this->_tanggal_masuk_filter($tanggal_check, $period_filter)) {
                return array(array(), array(), true);
            }
        }

        // field wajib
        foreach ($alias_map as $field_key => $info) {
            if ($info['required'] && (!isset($val[$field_key]) || $val[$field_key] === '')) {
                $errors[] = $info['label'] . ' wajib diisi';
            }
        }

        if (!empty($errors)) {
            return array(array(), $errors, false);
        }

        $resolved = array();

        // tanggal
        if (!empty($val['tanggal'])) {
            $tanggal = $this->value_converter->to_date($val['tanggal']);
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
        $resolved['jam_mulai']   = !empty($val['jam_mulai'])   ? $this->value_converter->to_time($val['jam_mulai'])   : null;
        $resolved['jam_selesai'] = !empty($val['jam_selesai']) ? $this->value_converter->to_time($val['jam_selesai']) : null;

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
            return array(array(), $errors, false);
        }

        return array($resolved, array(), false);
    }
}
