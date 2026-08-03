<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_model
 *
 * Berisi logika inti import data Laporan Produksi dari Excel:
 *   - resolve lookup (kode/NIK di Excel -> id di tabel master)
 *   - validasi 1 baris data
 *   - insert ke trx_laporan_produksi
 *
 * Sengaja dipisah dari parsing file (lihat Xlsx_reader) dan dari
 * auto-mapping alias (lihat Import_alias_model) supaya masing-masing
 * bagian gampang diuji/diganti terpisah.
 */
class Import_model extends CI_Model
{
    /** Cache lookup supaya tidak query berulang-ulang untuk kode yang sama dalam satu file import. */
    private $_cache = array();

    public function __construct()
    {
        parent::__construct();
    }

    // ---------------------------------------------------------------
    // Lookup master data
    // ---------------------------------------------------------------

    public function find_department_id($kode)
    {
        return $this->_lookup('department', 'mst_department', 'department_code', 'id', $kode);
    }

    public function find_karyawan_id($nik)
    {
        return $this->_lookup('karyawan', 'mst_karyawan', 'nik', 'id', $nik);
    }

    public function find_shift_id($kode)
    {
        return $this->_lookup('shift', 'mst_shift', 'kode', 'id', $kode);
    }

    public function find_jf_id($kode)
    {
        return $this->_lookup('jf', 'mst_jf', 'kode', 'id', $kode);
    }

    public function find_mesin_id($kode)
    {
        return $this->_lookup('mesin', 'mst_mesin', 'kode', 'id', $kode);
    }

    public function find_aktivitas_id($kode)
    {
        return $this->_lookup('aktivitas', 'mst_aktivitas', 'kode', 'id', $kode);
    }

    public function find_proses_id($kode)
    {
        return $this->_lookup('proses', 'mst_proses', 'kode', 'id', $kode);
    }

    public function find_pekerjaan_borong_id($kode)
    {
        return $this->_lookup('pekerjaan_borong', 'mst_pekerjaan_borong', 'kode', 'id', $kode);
    }

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

    /**
     * Insert satu baris laporan produksi yang datanya sudah divalidasi
     * & di-resolve (semua *_id sudah berupa integer atau null).
     */
    public function insert_laporan(array $data)
    {
        $this->db->insert('trx_laporan_produksi', $data);
        return $this->db->insert_id();
    }

    /**
     * Jalankan sekumpulan insert dalam satu transaksi. Kalau ada satu saja
     * yang gagal di level DB (mis. FK RESTRICT race condition), semua
     * baris dalam batch ini dibatalkan supaya tidak import setengah-setengah
     * secara diam-diam.
     *
     * @param  array $rows array of associative array siap insert
     * @return array ['inserted' => int]
     * @throws Exception kalau ada query yang gagal
     */
    public function insert_batch_transactional(array $rows)
    {
        $this->db->trans_begin();

        $inserted = 0;
        foreach ($rows as $row) {
            $this->db->insert('trx_laporan_produksi', $row);
            $inserted++;
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            throw new Exception('Gagal menyimpan data ke database, semua baris pada batch ini dibatalkan.');
        }

        $this->db->trans_commit();
        return array('inserted' => $inserted);
    }
}
