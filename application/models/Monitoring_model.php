<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Monitoring_model
 *
 * CRUD & agregasi trx_monitoring_produksi -- grouping trx_laporan_produksi
 * per (jf_id, periode, department_id, proses_id).
 *
 * agg_* SELALU dihitung ulang dari trx_laporan_produksi (dipanggil dari
 * Import_model setelah insert_batch_transactional, lewat refresh_agg()).
 * realisasi_* diisi = agg_* hanya saat baris pertama kali dibuat, sesudah
 * itu murni domain user (update_realisasi()) dan TIDAK pernah ditimpa oleh
 * refresh_agg(), sesuai Bagian 3.2 rancangan.
 */
class Monitoring_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    // ---------------------------------------------------------------
    // Agregasi (dipanggil dari Import_model setelah insert)
    // ---------------------------------------------------------------

    /**
     * Hitung ulang agg_* untuk sekumpulan kombinasi (jf_id, periode,
     * department_id, proses_id) dan upsert ke trx_monitoring_produksi.
     * Baris baru: realisasi_* = agg_*, is_match = true.
     * Baris lama: agg_* ditimpa, realisasi_* TIDAK disentuh, is_match
     * dihitung ulang berdasarkan realisasi_* yang sudah ada.
     *
     * Dipanggil DALAM transaksi yang sama dengan insert_batch_transactional
     * milik Import_model, supaya kalau insert di-rollback, refresh ini
     * ikut batal.
     *
     * @param array $tuples [['jf_id'=>1,'periode'=>'2026-07','department_id'=>2,'proses_id'=>3], ...]
     *                       tidak perlu unik sendiri, method ini yang unik-kan.
     */
    public function refresh_agg(array $tuples)
    {
        if (empty($tuples)) {
            return;
        }

        $unique = array();
        foreach ($tuples as $t) {
            if (empty($t['jf_id']) || empty($t['periode']) || empty($t['department_id']) || empty($t['proses_id'])) {
                continue;
            }
            $key = $t['jf_id'] . '|' . $t['periode'] . '|' . $t['department_id'] . '|' . $t['proses_id'];
            $unique[$key] = $t;
        }

        foreach ($unique as $t) {
            $agg = $this->db->select("
                    COALESCE(SUM(input_qty), 0)    AS agg_input_qty,
                    COALESCE(SUM(qc_sampling), 0)  AS agg_qc_sampling,
                    COALESCE(SUM(waste), 0)        AS agg_waste,
                    COALESCE(SUM(dead), 0)         AS agg_dead,
                    COALESCE(SUM(error), 0)        AS agg_error,
                    COALESCE(SUM(good_qty), 0)     AS agg_good_qty
                ")
                ->where('jf_id', $t['jf_id'])
                ->where('periode', $t['periode'])
                ->where('department_id', $t['department_id'])
                ->where('proses_id', $t['proses_id'])
                ->get('trx_laporan_produksi')
                ->row_array();

            $existing = $this->db->where('jf_id', $t['jf_id'])
                ->where('periode', $t['periode'])
                ->where('department_id', $t['department_id'])
                ->where('proses_id', $t['proses_id'])
                ->get('trx_monitoring_produksi')
                ->row_array();

            if (!$existing) {
                // baris baru: realisasi_* = agg_* saat dibuat
                $this->db->insert('trx_monitoring_produksi', array(
                    'jf_id'                 => $t['jf_id'],
                    'periode'               => $t['periode'],
                    'department_id'         => $t['department_id'],
                    'proses_id'             => $t['proses_id'],
                    'agg_input_qty'         => $agg['agg_input_qty'],
                    'agg_qc_sampling'       => $agg['agg_qc_sampling'],
                    'agg_waste'             => $agg['agg_waste'],
                    'agg_dead'              => $agg['agg_dead'],
                    'agg_error'             => $agg['agg_error'],
                    'agg_good_qty'          => $agg['agg_good_qty'],
                    'realisasi_input_qty'   => $agg['agg_input_qty'],
                    'realisasi_qc_sampling' => $agg['agg_qc_sampling'],
                    'realisasi_waste'       => $agg['agg_waste'],
                    'realisasi_dead'        => $agg['agg_dead'],
                    'realisasi_error'       => $agg['agg_error'],
                    'realisasi_good_qty'    => $agg['agg_good_qty'],
                    'is_match'              => true,
                ));
            } else {
                // baris lama: hanya agg_* yang di-refresh, realisasi_* tetap punya user
                $this->db->where('id', $existing['id'])->update('trx_monitoring_produksi', array(
                    'agg_input_qty'   => $agg['agg_input_qty'],
                    'agg_qc_sampling' => $agg['agg_qc_sampling'],
                    'agg_waste'       => $agg['agg_waste'],
                    'agg_dead'        => $agg['agg_dead'],
                    'agg_error'       => $agg['agg_error'],
                    'agg_good_qty'    => $agg['agg_good_qty'],
                    'is_match'        => $this->_is_match($agg, $existing),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ));
            }
        }
    }

    private function _is_match(array $agg, array $existing)
    {
        $cols = array('input_qty', 'qc_sampling', 'waste', 'dead', 'error', 'good_qty');
        foreach ($cols as $c) {
            // bandingkan sebagai angka (numeric dari DB bisa balik sebagai string)
            if ((float) $agg['agg_' . $c] !== (float) $existing['realisasi_' . $c]) {
                return false;
            }
        }
        return true;
    }

    // ---------------------------------------------------------------
    // List "JF aktif per periode", terfilter department user
    // ---------------------------------------------------------------

    /**
     * Daftar JF aktif yang punya baris monitoring pada periode tsb,
     * dibatasi ke department_ids user (null = tidak dibatasi, lihat
     * Import_model::get_user_allowed_departments() untuk pola yang sama).
     * Dikelompokkan per JF (agregat department_id yang muncul disertakan
     * sebagai info, bukan untuk filter lanjutan).
     */
    public function get_active_jf_by_periode($periode, array $department_ids = null)
    {
        $this->db->select('j.id AS jf_id, j.jf, j.product, j.customer, j.status_jf,
                            m.department_id, d.department_name AS department_nama')
            ->from('trx_monitoring_produksi m')
            ->join('mst_jf j', 'j.id = m.jf_id')
            ->join('mst_department d', 'd.id = m.department_id')
            ->where('m.periode', $periode)
            ->where('j.status_jf', 'AKTIF')
            ->group_by('j.id, j.jf, j.product, j.customer, j.status_jf, m.department_id, d.department_name')
            ->order_by('j.jf', 'ASC');

        if ($department_ids !== null) {
            $this->db->where_in('m.department_id', $department_ids);
        }

        return $this->db->get()->result_array();
    }

    // ---------------------------------------------------------------
    // Detail: daftar proses + ringkasan angka untuk 1 JF + periode
    // ---------------------------------------------------------------

    public function get_detail($jf_id, $periode, array $department_ids = null)
    {
        $this->db->select('m.*, p.nama AS proses_nama, p.kode AS proses_kode, d.department_name AS department_nama')
            ->from('trx_monitoring_produksi m')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->join('mst_department d', 'd.id = m.department_id')
            ->where('m.jf_id', $jf_id)
            ->where('m.periode', $periode)
            ->order_by('d.department_name', 'ASC')
            ->order_by('p.nama', 'ASC');

        if ($department_ids !== null) {
            $this->db->where_in('m.department_id', $department_ids);
        }

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$r) {
            $r['is_match'] = normalize_bool($r['is_match']);
        }
        return $rows;
    }

    public function get($id)
    {
        return $this->db->where('id', $id)->get('trx_monitoring_produksi')->row_array();
    }

    // ---------------------------------------------------------------
    // Summary produksi per JF per periode: pemakaian material s/d kirim
    // ---------------------------------------------------------------

    /**
     * Ringkasan "pemakaian material sampai kirim" untuk satu JF + periode,
     * dirakit dari baris-baris monitoring ($rows, hasil get_detail() --
     * sudah terfilter department user) ditambah tabel pemakaian/delivery
     * terkait.
     *
     * Dirakit dari $rows, bukan re-query jf_id+periode dari sini: supaya
     * guard department yang sudah dijalankan Controller di get_detail()
     * otomatis ikut membatasi baris apa saja yang masuk hitungan summary
     * -- tidak ada celah user melihat angka dari department yang bukan
     * haknya.
     *
     * @param array $rows hasil Monitoring_model::get_detail()
     */
    public function get_summary(array $rows)
    {
        if (empty($rows)) {
            return null;
        }

        $ids = array_map(function ($r) { return $r['id']; }, $rows);

        // 1. Total realisasi keseluruhan (jumlah semua baris proses di JF+periode ini)
        $total = array(
            'input_qty' => 0, 'qc_sampling' => 0, 'waste' => 0,
            'dead' => 0, 'error' => 0, 'good_qty' => 0,
        );
        foreach ($rows as $r) {
            foreach ($total as $c => $v) {
                $total[$c] += (float) $r['realisasi_' . $c];
            }
        }

        // 2. Pemakaian RAW -- digabung per material (+ satuan, kalau-kalau beda satuan dipakai)
        $raw_usage = $this->db->select("mr.kode_material, mr.nama_material, pm.satuan,
                                          SUM(pm.qty_pakai) AS total_qty")
            ->from('trx_pemakaian_material pm')
            ->join('mst_material_raw mr', 'mr.id = pm.material_raw_id')
            ->where_in('pm.monitoring_id', $ids)
            ->where('pm.jenis_material', 'RAW')
            ->group_by('mr.kode_material, mr.nama_material, pm.satuan')
            ->order_by('mr.nama_material', 'ASC')
            ->get()->result_array();

        // 3. WIP masuk -- WIP yang dicantolkan sbg bahan proses-proses JF+periode ini (dari sumber manapun)
        $wip_masuk = (float) $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where_in('monitoring_id', $ids)
            ->where('jenis_material', 'WIP')
            ->get('trx_pemakaian_material')->row('total');

        // 4. WIP keluar -- stok WIP hasil JF+periode ini yang sudah dipakai proses lain,
        //    digabung dari dua tabel yang sama-sama menyerap stok WIP (lihat catatan
        //    di Pemakaian_material_model::search_sumber_wip()).
        $wip_keluar_pm = (float) $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where_in('sumber_monitoring_id', $ids)
            ->where('jenis_material', 'WIP')
            ->get('trx_pemakaian_material')->row('total');
        $wip_keluar_dl = (float) $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where_in('monitoring_id_asal', $ids)
            ->get('trx_wip_pemakaian')->row('total');
        $wip_keluar = $wip_keluar_pm + $wip_keluar_dl;

        // 5. WIP dihasilkan (baris berstatus WIP_STOK)
        $wip_dihasilkan = 0;
        foreach ($rows as $r) {
            if ($r['status_output'] === 'WIP_STOK') {
                $wip_dihasilkan += (float) $r['realisasi_good_qty'];
            }
        }

        // 6. FG dihasilkan (baris berstatus FINISH_GOOD_STOK), dipakai proses lain, & dikirim
        $fg_dihasilkan = 0;
        $fg_ids = array();
        foreach ($rows as $r) {
            if ($r['status_output'] === 'FINISH_GOOD_STOK') {
                $fg_dihasilkan += (float) $r['realisasi_good_qty'];
                $fg_ids[] = $r['id'];
            }
        }
        $fg_dipakai_proses_lain = (float) $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where_in('sumber_monitoring_id', $ids)
            ->where('jenis_material', 'FG')
            ->get('trx_pemakaian_material')->row('total');

        $fg_dikirim = 0;
        if (!empty($fg_ids)) {
            $fg_dikirim = (float) $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
                ->where_in('monitoring_id', $fg_ids)
                ->get('trx_delivery_pemakaian_fg')->row('total');
        }

        return array(
            'total_realisasi' => $total,
            'raw_usage'        => $raw_usage,
            'wip' => array(
                'masuk'      => $wip_masuk,
                'dihasilkan' => $wip_dihasilkan,
                'keluar'     => $wip_keluar,
                'sisa'       => $wip_dihasilkan - $wip_keluar,
            ),
            'fg' => array(
                'dihasilkan'          => $fg_dihasilkan,
                'dipakai_proses_lain' => $fg_dipakai_proses_lain,
                'dikirim'             => $fg_dikirim,
                'sisa'                => $fg_dihasilkan - $fg_dipakai_proses_lain - $fg_dikirim,
            ),
        );
    }

    /**
     * Total qty yang sudah dikerjakan karyawan status BORONG untuk baris
     * monitoring ini (dasar warning keras poin 8 rancangan). Dihitung
     * langsung dari trx_laporan_produksi, bukan disalin ke monitoring,
     * supaya selalu ikut berubah kalau ada import ulang.
     */
    public function get_total_borong_good_qty($monitoring_id)
    {
        $m = $this->get($monitoring_id);
        if (!$m) {
            return 0;
        }

        $row = $this->db->select('COALESCE(SUM(l.good_qty), 0) AS total')
            ->from('trx_laporan_produksi l')
            ->join('mst_karyawan k', 'k.id = l.operator_id')
            ->where('l.jf_id', $m['jf_id'])
            ->where('l.periode', $m['periode'])
            ->where('l.department_id', $m['department_id'])
            ->where('l.proses_id', $m['proses_id'])
            ->where('l.pekerjaan_borong_id IS NOT NULL', null, false)
            ->where('k.status_kepegawaian', 'BORONG')
            ->get()
            ->row_array();

        return (float) $row['total'];
    }

    // ---------------------------------------------------------------
    // Edit realisasi & status_output (domain user)
    // ---------------------------------------------------------------

    /**
     * Update realisasi_* manual dari user, hitung ulang is_match terhadap
     * agg_* saat ini. TIDAK melakukan validasi warning borong (poin 8) --
     * itu tanggung jawab Controller/JS sebelum memanggil ini, sesuai
     * keputusan Bagian 4.5 rancangan.
     *
     * @param array $realisasi ['realisasi_input_qty'=>.., 'realisasi_qc_sampling'=>.., ...]
     */
    public function update_realisasi($id, array $realisasi, $user_id = null)
    {
        $existing = $this->get($id);
        if (!$existing) {
            return false;
        }

        $cols = array(
            'realisasi_input_qty', 'realisasi_qc_sampling', 'realisasi_waste',
            'realisasi_dead', 'realisasi_error', 'realisasi_good_qty',
        );
        $data = array();
        foreach ($cols as $c) {
            if (isset($realisasi[$c])) {
                $data[$c] = $realisasi[$c];
            }
        }
        if (empty($data)) {
            return false;
        }

        $merged = array_merge($existing, $data);
        $is_match = true;
        foreach (array('input_qty', 'qc_sampling', 'waste', 'dead', 'error', 'good_qty') as $c) {
            if ((float) $merged['agg_' . $c] !== (float) $merged['realisasi_' . $c]) {
                $is_match = false;
                break;
            }
        }

        $data['is_match']   = $is_match;
        $data['updated_by'] = $user_id;
        $data['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('id', $id)->update('trx_monitoring_produksi', $data);
        return $this->db->affected_rows();
    }

    /**
     * Set status_output baris monitoring: PROSES_SELANJUTNYA | WIP_STOK |
     * FINISH_GOOD_STOK | null. Satu baris hanya boleh satu status
     * (Bagian 4.2 rancangan) -- itu terjamin otomatis karena ini kolom
     * tunggal, bukan tabel pivot.
     */
    public function set_status_output($id, $status)
    {
        $valid = array('PROSES_SELANJUTNYA', 'WIP_STOK', 'FINISH_GOOD_STOK', null);
        if (!in_array($status, $valid, true)) {
            return false;
        }
        $this->db->where('id', $id)->update('trx_monitoring_produksi', array(
            'status_output' => $status,
            'updated_at'    => date('Y-m-d H:i:s'),
        ));
        return $this->db->affected_rows();
    }
}