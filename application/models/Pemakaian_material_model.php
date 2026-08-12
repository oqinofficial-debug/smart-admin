<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pemakaian_material_model
 *
 * CRUD trx_pemakaian_material -- pencantolan bahan (RAW dari master, atau
 * WIP dari hasil proses lain yang sudah pernah diinjek, lintas JF) ke satu
 * baris trx_monitoring_produksi. Tidak ada tabel stok awal (Bagian 3.10
 * rancangan) -- semua sisa dihitung on-the-fly dari akumulasi transaksi.
 *
 * Validasi qty_pakai vs sisa bersifat WARNING (soft), bukan block keras,
 * sesuai Bagian 3.7 rancangan -- method di sini hanya MENYEDIAKAN angka
 * sisa, keputusan block/tidak ada di Controller/JS.
 */
class Pemakaian_material_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_by_monitoring($monitoring_id)
    {
        $this->db->select("pm.*, mr.kode_material AS raw_kode, mr.nama_material AS raw_nama,
                            sj.jf AS sumber_jf, sm.periode AS sumber_periode,
                            sp.nama AS sumber_proses_nama, sd.department_name AS sumber_department_nama")
            ->from('trx_pemakaian_material pm')
            ->join('mst_material_raw mr', 'mr.id = pm.material_raw_id', 'left')
            ->join('trx_monitoring_produksi sm', 'sm.id = pm.sumber_monitoring_id', 'left')
            ->join('mst_jf sj', 'sj.id = sm.jf_id', 'left')
            ->join('mst_proses sp', 'sp.id = sm.proses_id', 'left')
            ->join('mst_department sd', 'sd.id = sm.department_id', 'left')
            ->where('pm.monitoring_id', $monitoring_id)
            ->order_by('pm.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Cari baris trx_monitoring_produksi yang bisa jadi SUMBER WIP --
     * status_output IN (WIP_STOK, PROSES_SELANJUTNYA), lintas JF apapun
     * (Bagian 1.4 rancangan: "hasil proses JF lain atas hasil proses
     * tertentu"), lengkap dengan sisa qty setelah dikurangi pemakaian
     * yang sudah dicantolkan ke sana.
     *
     * Sisa qty dihitung GABUNGAN dari dua tempat pemakaian yang sama-sama
     * menyerap stok WIP ini: trx_pemakaian_material (jenis_material='WIP',
     * dipakai modul Monitoring Produksi sendiri sbg bahan proses lain) DAN
     * trx_wip_pemakaian (dipakai modul Delivery utk alokasi FIFO WIP
     * gudang/antar-proses). Sebelumnya kedua tabel ini dihitung terpisah
     * (masing-masing tidak tahu pemakaian di tabel yang lain) sehingga
     * sisa stok WIP bisa tampak lebih besar dari kenyataan di salah satu
     * modul dan berisiko WIP yang sama terpakai dobel. Pola ini sama
     * dengan search_sumber_fg()/get_sisa_sumber_fg() di bawah.
     *
     * @param string $keyword cari di kode JF / nama proses, boleh kosong
     */
    public function search_sumber_wip($keyword = '')
    {
        $this->db->select("sm.id AS monitoring_id, j.jf, p.nama AS proses_nama, d.department_name AS department_nama,
                            sm.periode, sm.status_output, sm.realisasi_good_qty,
                            sm.realisasi_good_qty - COALESCE(pm_pakai.total_pakai, 0) - COALESCE(dl_pakai.total_pakai, 0) AS sisa_qty")
            ->from('trx_monitoring_produksi sm')
            ->join('mst_jf j', 'j.id = sm.jf_id')
            ->join('mst_proses p', 'p.id = sm.proses_id')
            ->join('mst_department d', 'd.id = sm.department_id')
            ->join(
                "(SELECT sumber_monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_pemakaian_material
                  WHERE jenis_material = 'WIP'
                  GROUP BY sumber_monitoring_id) pm_pakai",
                'pm_pakai.sumber_monitoring_id = sm.id',
                'left'
            )
            ->join(
                "(SELECT monitoring_id_asal, SUM(qty_pakai) AS total_pakai
                  FROM trx_wip_pemakaian
                  GROUP BY monitoring_id_asal) dl_pakai",
                'dl_pakai.monitoring_id_asal = sm.id',
                'left'
            )
            ->where_in('sm.status_output', array('WIP_STOK', 'PROSES_SELANJUTNYA'));

        if ($keyword !== '') {
            $this->db->group_start()
                ->like('j.jf', $keyword)
                ->or_like('p.nama', $keyword)
                ->group_end();
        }

        $this->db->order_by('sm.updated_at', 'DESC')->limit(50);

        return $this->db->get()->result_array();
    }

    /**
     * Sisa qty satu sumber WIP tertentu -- gabungan pemakaian dari
     * trx_pemakaian_material (WIP) dan trx_wip_pemakaian, lihat catatan
     * di search_sumber_wip(). Dipakai untuk validasi warning saat submit
     * (soft, sama seperti get_sisa_sumber_fg()).
     */
    public function get_sisa_sumber_wip($monitoring_id)
    {
        $m = $this->db->select('realisasi_good_qty')->where('id', $monitoring_id)
            ->get('trx_monitoring_produksi')->row_array();
        if (!$m) {
            return null;
        }

        $pm_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('sumber_monitoring_id', $monitoring_id)
            ->where('jenis_material', 'WIP')
            ->get('trx_pemakaian_material')
            ->row_array();

        $dl_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('monitoring_id_asal', $monitoring_id)
            ->get('trx_wip_pemakaian')
            ->row_array();

        return (float) $m['realisasi_good_qty'] - (float) $pm_pakai['total'] - (float) $dl_pakai['total'];
    }

    /**
     * Cari baris trx_monitoring_produksi yang bisa jadi SUMBER FG --
     * status_output = FINISH_GOOD_STOK, lintas JF/department apapun.
     * Sisa qty dihitung GABUNGAN dari dua tempat pemakaian yang sama-sama
     * menyerap stok FG ini: trx_pemakaian_material (jenis_material='FG',
     * dipakai Monitoring Produksi sendiri sbg bahan proses lain) DAN
     * trx_delivery_pemakaian_fg (dipakai modul Delivery utk kirim ke
     * customer). Supaya stok FG yang sama tidak double-terpakai tanpa
     * saling tahu antara dua modul tersebut.
     *
     * @param string $keyword cari di kode JF / nama proses, boleh kosong
     */
    public function search_sumber_fg($keyword = '')
    {
        $this->db->select("sm.id AS monitoring_id, j.jf, p.nama AS proses_nama, d.department_name AS department_nama,
                            sm.periode, sm.status_output, sm.realisasi_good_qty,
                            sm.realisasi_good_qty - COALESCE(pm_pakai.total_pakai, 0) - COALESCE(dl_pakai.total_pakai, 0) AS sisa_qty")
            ->from('trx_monitoring_produksi sm')
            ->join('mst_jf j', 'j.id = sm.jf_id')
            ->join('mst_proses p', 'p.id = sm.proses_id')
            ->join('mst_department d', 'd.id = sm.department_id')
            ->join(
                "(SELECT sumber_monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_pemakaian_material
                  WHERE jenis_material = 'FG'
                  GROUP BY sumber_monitoring_id) pm_pakai",
                'pm_pakai.sumber_monitoring_id = sm.id',
                'left'
            )
            ->join(
                "(SELECT monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_delivery_pemakaian_fg
                  GROUP BY monitoring_id) dl_pakai",
                'dl_pakai.monitoring_id = sm.id',
                'left'
            )
            ->where('sm.status_output', 'FINISH_GOOD_STOK');

        if ($keyword !== '') {
            $this->db->group_start()
                ->like('j.jf', $keyword)
                ->or_like('p.nama', $keyword)
                ->group_end();
        }

        $this->db->order_by('sm.updated_at', 'DESC')->limit(50);

        return $this->db->get()->result_array();
    }

    /**
     * Sisa qty satu sumber FG tertentu -- gabungan pemakaian dari
     * trx_pemakaian_material (FG) dan trx_delivery_pemakaian_fg, lihat
     * catatan di search_sumber_fg(). Dipakai untuk validasi warning saat
     * submit (soft, sama seperti get_sisa_sumber_wip()).
     */
    public function get_sisa_sumber_fg($monitoring_id)
    {
        $m = $this->db->select('realisasi_good_qty, status_output')->where('id', $monitoring_id)
            ->get('trx_monitoring_produksi')->row_array();
        if (!$m || $m['status_output'] !== 'FINISH_GOOD_STOK') {
            return null;
        }

        $pm_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('sumber_monitoring_id', $monitoring_id)
            ->where('jenis_material', 'FG')
            ->get('trx_pemakaian_material')
            ->row_array();

        $dl_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('monitoring_id', $monitoring_id)
            ->get('trx_delivery_pemakaian_fg')
            ->row_array();

        return (float) $m['realisasi_good_qty'] - (float) $pm_pakai['total'] - (float) $dl_pakai['total'];
    }

    public function create_fg($monitoring_id, $sumber_monitoring_id, $qty_pakai, $satuan, $keterangan, $inputer_id)
    {
        $this->db->insert('trx_pemakaian_material', array(
            'monitoring_id'         => $monitoring_id,
            'jenis_material'        => 'FG',
            'sumber_monitoring_id'  => $sumber_monitoring_id,
            'qty_pakai'             => $qty_pakai,
            'satuan'                => $satuan,
            'keterangan'            => $keterangan,
            'inputer_id'            => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function create_raw($monitoring_id, $material_raw_id, $qty_pakai, $satuan, $keterangan, $inputer_id)
    {
        $this->db->insert('trx_pemakaian_material', array(
            'monitoring_id'   => $monitoring_id,
            'jenis_material'  => 'RAW',
            'material_raw_id' => $material_raw_id,
            'qty_pakai'       => $qty_pakai,
            'satuan'          => $satuan,
            'keterangan'      => $keterangan,
            'inputer_id'      => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function create_wip($monitoring_id, $sumber_monitoring_id, $qty_pakai, $satuan, $keterangan, $inputer_id)
    {
        $this->db->insert('trx_pemakaian_material', array(
            'monitoring_id'         => $monitoring_id,
            'jenis_material'        => 'WIP',
            'sumber_monitoring_id'  => $sumber_monitoring_id,
            'qty_pakai'             => $qty_pakai,
            'satuan'                => $satuan,
            'keterangan'            => $keterangan,
            'inputer_id'            => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete('trx_pemakaian_material');
        return $this->db->affected_rows();
    }
}