<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_batch_model
 *
 * Riwayat tiap proses import (trx_import_batch): dipakai untuk audit
 * ("periode Juli 2026 sudah diimport berapa kali, oleh siapa") dan sebagai
 * penanda baris trx_laporan_produksi mana yang berasal dari import mana
 * (lewat kolom import_batch_id), yang jadi dasar fitur "timpa periode ini".
 */
class Import_batch_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Buat record batch baru dengan status 'pending', dipanggil SEBELUM
     * proses insert baris dimulai, supaya import_batch_id sudah tersedia
     * untuk ditempelkan ke tiap baris yang diinsert.
     */
    public function create(array $data)
    {
        $payload = array(
            'nama_file'       => $data['nama_file'],
            'format_file'     => $data['format_file'],
            'sheet_name'      => isset($data['sheet_name']) ? $data['sheet_name'] : null,
            'nama_laporan_id' => isset($data['nama_laporan_id']) ? $data['nama_laporan_id'] : null,
            'mode'            => $data['mode'], // all | periode | range
            'periode'         => isset($data['periode']) ? $data['periode'] : null,
            'tanggal_mulai'   => isset($data['tanggal_mulai']) ? $data['tanggal_mulai'] : null,
            'tanggal_selesai' => isset($data['tanggal_selesai']) ? $data['tanggal_selesai'] : null,
            'replace_periode' => !empty($data['replace_periode']),
            'replace_range'   => !empty($data['replace_range']),
            'user_id'         => isset($data['user_id']) ? $data['user_id'] : null,
            'status'          => 'pending',
        );
        $this->db->insert('trx_import_batch', $payload);
        return $this->db->insert_id();
    }

    /**
     * Update rekap hasil akhir setelah proses import selesai (dipanggil
     * baik kalau sukses maupun kalau gagal di tengah jalan).
     */
    public function update_result($batch_id, array $result)
    {
        $this->db->where('id', $batch_id)->update('trx_import_batch', array(
            'total_baris' => $result['total_baris'],
            'sukses'      => $result['sukses'],
            'gagal'       => $result['gagal'],
            'dilewati'    => $result['dilewati'],
            'status'      => $result['status'], // selesai | gagal
        ));
    }

    /**
     * Riwayat import untuk ditampilkan di halaman result / halaman riwayat,
     * terbaru dulu.
     */
    public function get_recent($limit = 20)
    {
        return $this->db->select('b.*, u.fullname AS nama_user, l.nama AS nama_laporan')
                         ->from('trx_import_batch b')
                         ->join('mst_user u', 'u.id = b.user_id', 'left')
                         ->join('mst_nama_laporan l', 'l.id = b.nama_laporan_id', 'left')
                         ->order_by('b.created_at', 'DESC')
                         ->limit($limit)
                         ->get()
                         ->result_array();
    }

    /**
     * Ringkasan berapa kali & berapa baris suatu PERIODE, untuk LAPORAN yang
     * sama, sudah diimport, ditampilkan sebagai peringatan halus di halaman
     * preview import ("laporan X periode Y sudah pernah diimport 2x, total
     * 340 baris") supaya user sadar sebelum memilih mode timpa/tambah, dan
     * ini jugalah dasar dari "import ulang laporan+periode yang sama bisa
     * langsung replace".
     */
    public function get_periode_summary($periode, $nama_laporan_id)
    {
        return $this->db->select('COUNT(*) AS jumlah_batch, COALESCE(SUM(sukses), 0) AS total_baris')
                         ->where('periode', $periode)
                         ->where('nama_laporan_id', $nama_laporan_id)
                         ->where('status', 'selesai')
                         ->get('trx_import_batch')
                         ->row_array();
    }

    /**
     * Versi rentang tanggal dari get_periode_summary() -- dipakai saat mode
     * import = "range". Karena satu batch rentang tanggal bisa lintas
     * periode, ini mencocokkan berdasarkan tanggal_mulai/tanggal_selesai
     * yang BERIRISAN dengan rentang yang sedang dipilih user (bukan harus
     * persis sama), supaya peringatannya tetap relevan.
     */
    public function get_range_summary($tanggal_mulai, $tanggal_selesai, $nama_laporan_id)
    {
        return $this->db->select('COUNT(*) AS jumlah_batch, COALESCE(SUM(sukses), 0) AS total_baris')
                         ->where('nama_laporan_id', $nama_laporan_id)
                         ->where('status', 'selesai')
                         ->where('tanggal_mulai IS NOT NULL', null, false)
                         ->where('tanggal_mulai <=', $tanggal_selesai)
                         ->where('tanggal_selesai >=', $tanggal_mulai)
                         ->get('trx_import_batch')
                         ->row_array();
    }
}