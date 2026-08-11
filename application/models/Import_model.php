<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_model
 *
 * Berisi logika inti import data Laporan Produksi dari Excel/CSV/TXT:
 *   - resolve lookup (kode/NIK di file -> id di tabel master), termasuk versi
 *     BULK (1 query untuk semua kode unik) supaya cepat untuk file besar.
 *   - validasi 1 baris data
 *   - insert ke trx_laporan_produksi pakai insert_batch native (bukan loop
 *     satu-satu) supaya jauh lebih cepat untuk ribuan baris.
 *
 * Perubahan dari versi sebelumnya:
 *   - Tambah *_bulk() untuk tiap lookup: 1 query "WHERE kode IN (...)" untuk
 *     semua nilai unik yang muncul di file, bukan 1 query per baris.
 *   - insert_batch_transactional() sekarang benar-benar pakai
 *     $this->db->insert_batch() per potongan (chunk), bukan loop insert().
 *   - Tambah delete_periode_import_rows() untuk fitur "timpa data periode ini".
 *   - Lookup JF sekarang ke kolom mst_jf.jf (bukan lagi mst_jf.kode),
 *     menyesuaikan redesain master JF.
 *   - insert_batch_transactional() sekarang juga sinkronisasi trx_jf_periode
 *     (snapshot "JF ini muncul di periode ini") dalam transaksi yang sama,
 *     lewat Jf_model::sync_periode().
 */
class Import_model extends CI_Model
{
    /** Cache lookup per-nilai, dipertahankan untuk lookup satuan (dipakai di tempat lain di luar import massal). */
    private $_cache = array();

    const LOOKUP_MAP = array(
        'department'       => array('mst_department', 'department_code'),
        'karyawan'         => array('mst_karyawan', 'nik'),
        'shift'            => array('mst_shift', 'kode'),
        'jf'               => array('mst_jf', 'jf'),
        'mesin'            => array('mst_mesin', 'kode'),
        'aktivitas'        => array('mst_aktivitas', 'kode'),
        'proses'           => array('mst_proses', 'kode'),
        'pekerjaan_borong' => array('mst_pekerjaan_borong', 'kode'),
    );

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Jf_model');
        $this->load->model('Monitoring_model');
    }

    // ---------------------------------------------------------------
    // Lookup master data - satuan (dipertahankan untuk kompatibilitas)
    // ---------------------------------------------------------------

    public function find_department_id($kode) { return $this->_lookup('department', 'mst_department', 'department_code', 'id', $kode); }
    public function find_karyawan_id($nik) { return $this->_lookup('karyawan', 'mst_karyawan', 'nik', 'id', $nik); }
    public function find_shift_id($kode) { return $this->_lookup('shift', 'mst_shift', 'kode', 'id', $kode); }
    public function find_jf_id($kode) { return $this->_lookup('jf', 'mst_jf', 'jf', 'id', $kode); }
    public function find_mesin_id($kode) { return $this->_lookup('mesin', 'mst_mesin', 'kode', 'id', $kode); }
    public function find_aktivitas_id($kode) { return $this->_lookup('aktivitas', 'mst_aktivitas', 'kode', 'id', $kode); }
    public function find_proses_id($kode) { return $this->_lookup('proses', 'mst_proses', 'kode', 'id', $kode); }
    public function find_pekerjaan_borong_id($kode) { return $this->_lookup('pekerjaan_borong', 'mst_pekerjaan_borong', 'kode', 'id', $kode); }

    private function _lookup($cache_group, $table, $where_col, $select_col, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $cache_key = strtolower($value);
        if (isset($this->_cache[$cache_group][$cache_key])) {
            return $this->_cache[$cache_group][$cache_key];
        }

        $row = $this->db->select($select_col)
                         ->where('UPPER(' . $where_col . ')', strtoupper($value))
                         ->get($table)
                         ->row_array();

        $id = $row ? (int) $row[$select_col] : null;
        $this->_cache[$cache_group][$cache_key] = $id;

        return $id;
    }

    // ---------------------------------------------------------------
    // Lookup master data - BULK (kunci percepatan import file besar)
    // ---------------------------------------------------------------

    /**
     * Resolve semua nilai unik sekaligus dalam 1 query per grup, lalu simpan
     * ke cache internal supaya find_*_id() di atas otomatis kena cache-hit.
     *
     * Dipanggil SEKALI di awal proses import, sebelum loop per baris, dengan
     * kumpulan semua kode/NIK yang muncul di seluruh file untuk tiap grup.
     *
     * @param array $values_by_group ['department' => ['A','B',...], 'karyawan' => [...], ...]
     */
    public function preload_lookup_cache(array $values_by_group)
    {
        foreach ($values_by_group as $cache_group => $values) {
            if (!isset(self::LOOKUP_MAP[$cache_group]) || empty($values)) {
                continue;
            }
            list($table, $where_col) = self::LOOKUP_MAP[$cache_group];

            $unique_upper = array();
            foreach ($values as $v) {
                $v = trim((string) $v);
                if ($v !== '') {
                    $unique_upper[strtoupper($v)] = true;
                }
            }
            if (empty($unique_upper)) {
                continue;
            }

            // satu query "WHERE UPPER(kolom) IN (...)" untuk seluruh nilai unik grup ini,
            // menggantikan puluhan/ratusan/ribuan query terpisah per baris.
            $rows = $this->db->select("id, UPPER($where_col) AS kode_upper")
                              ->where_in("UPPER($where_col)", array_keys($unique_upper))
                              ->get($table)
                              ->result_array();

            if (!isset($this->_cache[$cache_group])) {
                $this->_cache[$cache_group] = array();
            }
            foreach ($rows as $r) {
                $this->_cache[$cache_group][strtolower($r['kode_upper'])] = (int) $r['id'];
            }
            // nilai yang dicari tapi tidak ketemu di DB tetap ditandai null,
            // supaya find_*_id() tidak query ulang ke DB untuk kode yang memang tidak ada.
            foreach (array_keys($unique_upper) as $upper_val) {
                $cache_key = strtolower($upper_val);
                if (!isset($this->_cache[$cache_group][$cache_key])) {
                    $this->_cache[$cache_group][$cache_key] = null;
                }
            }
        }
    }

    /**
     * Daftar department_id yang boleh diakses user (untuk membatasi baris
     * import supaya tidak mengisi laporan atas nama departemen lain).
     * User dengan can_view_all_departments = true dianggap boleh semua.
     */
    public function get_user_allowed_departments($user_id)
    {
        $user = $this->db->select('can_view_all_departments')->where('id', $user_id)->get('mst_user')->row_array();

        if ($user && normalize_bool($user['can_view_all_departments'])) {
            return null; // null = tidak dibatasi
        }

        $rows = $this->db->select('department_id')->where('user_id', $user_id)->get('mst_user_department')->result_array();
        return array_map(function ($r) { return (int) $r['department_id']; }, $rows);
    }

    // ---------------------------------------------------------------
    // Insert
    // ---------------------------------------------------------------

    public function insert_laporan(array $data)
    {
        $this->db->insert('trx_laporan_produksi', $data);
        return $this->db->insert_id();
    }

    /**
     * Insert banyak baris sekaligus dalam SATU transaksi, memakai
     * insert_batch() native CodeIgniter (menghasilkan multi-row INSERT
     * statement per chunk) alih-alih loop insert() satu per satu.
     * Ini adalah faktor terbesar percepatan import untuk file berisi
     * ribuan baris: 1 statement untuk ratusan baris, bukan ratusan
     * round-trip terpisah ke database.
     *
     * Sekaligus mengumpulkan pasangan (jf_id, periode) dari baris yang
     * diinsert, lalu sinkronisasi ke trx_jf_periode di transaksi yang
     * sama -- supaya kalau insert di-rollback, snapshot JF-per-periode
     * ikut batal (tidak "nyangkut" tanpa data laporannya).
     *
     * @param  array      $rows       array of associative array siap insert
     * @param  int        $chunk_size jumlah baris per statement insert_batch
     * @param  array|null $pre_delete opsional, untuk mode "timpa periode ini":
     *                    ['periode' => 'YYYY-MM', 'department_ids' => array|null]
     *                    dijalankan dalam transaksi YANG SAMA dengan insert,
     *                    supaya kalau insert gagal, data lama yang sudah
     *                    "ditimpa" ikut di-rollback (tidak hilang tanpa gantinya).
     * @return array ['inserted' => int, 'deleted' => int]
     * @throws Exception kalau ada query yang gagal (semua baris di-rollback)
     */
    public function insert_batch_transactional(array $rows, $chunk_size = 500, array $pre_delete = null)
    {
        $this->db->trans_begin();

        $deleted = 0;
        if ($pre_delete !== null) {
            $deleted = $this->delete_periode_import_rows(
                $pre_delete['periode'],
                isset($pre_delete['department_ids']) ? $pre_delete['department_ids'] : null
            );
        }

        $inserted = 0;
        $jf_periode_pairs = array(); // dikumpulkan sambil jalan, untuk sync ke trx_jf_periode
        $monitoring_tuples = array(); // dikumpulkan sambil jalan, untuk refresh agg_* trx_monitoring_produksi

        foreach (array_chunk($rows, $chunk_size) as $chunk) {
            $this->db->insert_batch('trx_laporan_produksi', $chunk);
            $inserted += count($chunk);

            foreach ($chunk as $row) {
                if (!empty($row['jf_id']) && !empty($row['tanggal'])) {
                    $jf_periode_pairs[] = array(
                        'jf_id'   => $row['jf_id'],
                        'periode' => substr($row['tanggal'], 0, 7), // YYYY-MM dari tanggal
                    );
                }
                if (!empty($row['jf_id']) && !empty($row['tanggal']) && !empty($row['department_id']) && !empty($row['proses_id'])) {
                    $monitoring_tuples[] = array(
                        'jf_id'         => $row['jf_id'],
                        'periode'       => substr($row['tanggal'], 0, 7),
                        'department_id' => $row['department_id'],
                        'proses_id'     => $row['proses_id'],
                    );
                }
            }
        }

        $this->Jf_model->sync_periode($jf_periode_pairs);
        $this->Monitoring_model->refresh_agg($monitoring_tuples);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            throw new Exception('Gagal menyimpan data ke database, semua baris (termasuk penghapusan data lama, jika ada) pada batch ini dibatalkan.');
        }

        $this->db->trans_commit();
        return array('inserted' => $inserted, 'deleted' => $deleted);
    }

    // ---------------------------------------------------------------
    // Periode & riwayat import batch
    // ---------------------------------------------------------------

    /**
     * Hapus baris laporan produksi milik periode tertentu YANG BERASAL DARI
     * IMPORT (import_batch_id tidak null), untuk fitur "timpa data periode
     * ini". Data yang diinput manual (import_batch_id null) sengaja TIDAK
     * ikut terhapus.
     *
     * @param string     $periode        format YYYY-MM
     * @param array|null $department_ids batasi hanya departemen ini (null = semua)
     * @return int jumlah baris yang dihapus
     */
    public function delete_periode_import_rows($periode, array $department_ids = null)
    {
        $this->db->where('periode', $periode);
        $this->db->where('import_batch_id IS NOT NULL', null, false);
        if ($department_ids !== null) {
            $this->db->where_in('department_id', $department_ids);
        }
        $this->db->delete('trx_laporan_produksi');
        return $this->db->affected_rows();
    }
}