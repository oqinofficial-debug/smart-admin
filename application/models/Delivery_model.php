<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Delivery_model
 *
 * CRUD catatan kiriman per JF (trx_delivery_record): No. JF, Tanggal Kirim,
 * Aktual Kirim (qty barang yang benar-benar terkirim), No. SP, Jenis SP.
 * Ini murni data pencatatan -- BUKAN mekanisme final JF otomatis (final JF
 * tetap manual & global, lihat Jf_model::set_final()). Data di sini
 * nantinya jadi salah satu bahan pertimbangan (akumulasi qty terkirim vs
 * qty JF) untuk mendeteksi anomali, tapi perhitungan itu belum ada di sini.
 */
class Delivery_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Daftar semua kiriman, join ke mst_jf supaya kode JF & product
     * langsung tampil di list tanpa query terpisah.
     */
    public function get_all()
    {
        return $this->db->select('d.*, j.jf AS jf_kode, j.product AS jf_product, j.status_jf')
                          ->from('trx_delivery_record d')
                          ->join('mst_jf j', 'j.id = d.jf_id')
                          ->order_by('d.tanggal_kirim', 'DESC')
                          ->order_by('d.id', 'DESC')
                          ->get()
                          ->result_array();
    }

    public function get($id)
    {
        return $this->db->select('d.*, j.jf AS jf_kode, j.product AS jf_product, j.status_jf')
                          ->from('trx_delivery_record d')
                          ->join('mst_jf j', 'j.id = d.jf_id')
                          ->where('d.id', $id)
                          ->get()
                          ->row_array();
    }

    /**
     * Semua kiriman untuk satu JF, dipakai nanti sebagai dasar hitung
     * akumulasi qty terkirim vs qty JF (belum diimplementasikan di sini).
     */
    public function get_by_jf($jf_id)
    {
        return $this->db->where('jf_id', $jf_id)
                          ->order_by('tanggal_kirim', 'ASC')
                          ->get('trx_delivery_record')
                          ->result_array();
    }

    public function create(array $data)
    {
        $this->db->insert('trx_delivery_record', $data);
        return $this->db->insert_id();
    }

    public function update($id, array $data)
    {
        $this->db->where('id', $id)->update('trx_delivery_record', $data);
        return $this->db->affected_rows();
    }

    public function delete($id)
    {
        $this->db->where('id', $id)->delete('trx_delivery_record');
        return $this->db->affected_rows();
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel). Tiap elemen $rows
     * adalah array kolom mentah hasil parse_bulk_paste():
     * [no_jf, tanggal_kirim, aktual_kirim(qty)?, no_sp, jenis_sp?]
     *
     * Beda dari master data lain (Master Data/Karyawan/JF) yang punya satu
     * kolom kode unik, delivery record tidak punya kolom kode tunggal --
     * jadi kecocokan baris "sudah ada" dicek dari PASANGAN (No. JF + No. SP).
     * Kalau pasangan itu SUDAH ADA, baris DI-REPLACE (tanggal kirim, aktual
     * kirim/qty, jenis SP ditimpa). Kalau belum ada, jadi baris baru.
     *
     * No. JF harus sudah terdaftar di Master JF (tidak dibuatkan otomatis)
     * -- kalau tidak ketemu, baris itu gagal.
     *
     * @param int $inputer_id dipakai untuk baris baru saja (kolom inputer_id
     *            tidak ditimpa ulang saat replace, supaya jejak siapa yang
     *            input pertama kali tetap ada).
     * @return array array('inserted'=>int, 'updated'=>int,
     *               'errors'=>array(array('line'=>int, 'message'=>string)))
     */
    public function bulk_upsert(array $rows, $inputer_id)
    {
        $this->load->library('value_converter');

        $inserted     = 0;
        $updated      = 0;
        $errors       = array();
        $fg_candidates = array(); // baris sukses & aktual_kirim > 0 -- kandidat auto-alokasi FG

        foreach ($rows as $i => $cols) {
            $line         = $i + 1;
            $jf_kode      = isset($cols[0]) ? trim($cols[0]) : '';
            $tanggal_raw  = isset($cols[1]) ? trim($cols[1]) : '';
            $aktual_raw   = isset($cols[2]) ? trim($cols[2]) : '';
            $no_sp        = isset($cols[3]) ? trim($cols[3]) : '';
            $jenis_sp     = isset($cols[4]) ? trim($cols[4]) : '';

            if ($jf_kode === '' || $no_sp === '') {
                $errors[] = array('line' => $line, 'message' => 'No. JF dan No. SP wajib diisi.');
                continue;
            }
            if (mb_strlen($no_sp) > 100) {
                $errors[] = array('line' => $line, 'message' => 'No. SP melebihi 100 karakter.');
                continue;
            }

            $jf_row = $this->db->where('jf', $jf_kode)->get('mst_jf')->row_array();
            if (!$jf_row) {
                $errors[] = array('line' => $line, 'message' => 'No. JF "' . $jf_kode . '" tidak ditemukan di Master JF.');
                continue;
            }

            if ($tanggal_raw === '') {
                $errors[] = array('line' => $line, 'message' => 'Tanggal Kirim wajib diisi.');
                continue;
            }
            $tanggal_kirim = $this->value_converter->to_date($tanggal_raw);
            if ($tanggal_kirim === null) {
                $errors[] = array('line' => $line, 'message' => 'Tanggal Kirim tidak valid (dapat: "' . $tanggal_raw . '").');
                continue;
            }

            $aktual_kirim = null;
            if ($aktual_raw !== '') {
                if (!is_numeric(str_replace(',', '.', $aktual_raw))) {
                    $errors[] = array('line' => $line, 'message' => 'Aktual Kirim (Qty) harus angka (dapat: "' . $aktual_raw . '").');
                    continue;
                }
                $aktual_kirim = (float) str_replace(',', '.', $aktual_raw);
            }

            $data = array(
                'jf_id'         => $jf_row['id'],
                'tanggal_kirim' => $tanggal_kirim,
                'aktual_kirim'  => $aktual_kirim,
                'no_sp'         => $no_sp,
                'jenis_sp'      => $jenis_sp !== '' ? $jenis_sp : null,
            );

            $existing = $this->db->where('jf_id', $jf_row['id'])
                                  ->where('no_sp', $no_sp)
                                  ->get('trx_delivery_record')
                                  ->row_array();

            try {
                if ($existing) {
                    $this->db->where('id', $existing['id'])->update('trx_delivery_record', $data);
                    $updated++;
                    $delivery_id = $existing['id'];
                } else {
                    $data['inputer_id'] = $inputer_id;
                    $this->db->insert('trx_delivery_record', $data);
                    $inserted++;
                    $delivery_id = $this->db->insert_id();
                }

                if ($aktual_kirim !== null && $aktual_kirim > 0) {
                    $fg_candidates[] = array(
                        'delivery_id'  => $delivery_id,
                        'jf_id'        => $jf_row['id'],
                        'jf_kode'      => $jf_kode,
                        'no_sp'        => $no_sp,
                        'aktual_kirim' => $aktual_kirim,
                    );
                }
            } catch (Exception $e) {
                $errors[] = array('line' => $line, 'message' => 'Gagal simpan: ' . $e->getMessage());
            }
        }

        return array(
            'inserted'      => $inserted,
            'updated'       => $updated,
            'errors'        => $errors,
            'fg_candidates' => $fg_candidates,
        );
    }

    // ---------------------------------------------------------------
    // Pencantolan stok FG (trx_delivery_pemakaian_fg) -- Bagian 3.4 rancangan
    // ---------------------------------------------------------------

    public function get_pemakaian_fg($delivery_id)
    {
        return $this->db->select("f.*, j.jf, p.nama AS proses_nama, m.periode AS monitoring_periode")
            ->from('trx_delivery_pemakaian_fg f')
            ->join('trx_monitoring_produksi m', 'm.id = f.monitoring_id')
            ->join('mst_jf j', 'j.id = m.jf_id')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->where('f.delivery_id', $delivery_id)
            ->order_by('f.created_at', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Cari baris monitoring dengan status_output = FINISH_GOOD_STOK untuk
     * satu JF, lengkap sisa stok FG (realisasi_good_qty dikurangi total
     * sudah dipakai kiriman manapun -- bukan hanya kiriman ini).
     */
    public function search_stok_fg($jf_id)
    {
        return $this->db->select("m.id AS monitoring_id, p.nama AS proses_nama, d.department_name AS department_nama,
                            m.periode, m.realisasi_good_qty,
                            m.realisasi_good_qty - COALESCE(pakai.total_pakai, 0) AS sisa_qty")
            ->from('trx_monitoring_produksi m')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->join('mst_department d', 'd.id = m.department_id')
            ->join(
                "(SELECT monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_delivery_pemakaian_fg
                  GROUP BY monitoring_id) pakai",
                'pakai.monitoring_id = m.id',
                'left'
            )
            ->where('m.jf_id', $jf_id)
            ->where('m.status_output', 'FINISH_GOOD_STOK')
            ->order_by('m.periode', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Sama seperti search_stok_fg() tapi diurut periode ASC (FIFO -- stok
     * paling lama diserap duluan) dan hanya baris bersisa (>0).
     */
    public function search_stok_fg_fifo($jf_id)
    {
        $rows = $this->search_stok_fg($jf_id);
        $rows = array_values(array_filter($rows, function ($r) {
            return (float) $r['sisa_qty'] > 0;
        }));
        usort($rows, function ($a, $b) {
            return strcmp($a['periode'], $b['periode']);
        });
        return $rows;
    }

    /**
     * Hitung rencana alokasi FIFO stok FG untuk qty tertentu milik satu JF.
     * TIDAK menyimpan apa pun -- murni preview (poin 1 rancangan: auto-
     * hitung, konfirmasi manual sebelum disimpan).
     *
     * @return array('allocations'=>[['monitoring_id','proses_nama',
     *               'department_nama','periode','sisa_qty','alokasi_qty'], ...],
     *               'shortfall'=>float, 'warning'=>string|null)
     */
    public function preview_fg_allocation($jf_id, $qty)
    {
        $sisa_qty_dibutuhkan = (float) $qty;
        $rows = $this->search_stok_fg_fifo($jf_id);
        $allocations = array();

        foreach ($rows as $r) {
            if ($sisa_qty_dibutuhkan <= 0) {
                break;
            }
            $ambil = min($sisa_qty_dibutuhkan, (float) $r['sisa_qty']);
            $allocations[] = array(
                'monitoring_id'   => $r['monitoring_id'],
                'proses_nama'     => $r['proses_nama'],
                'department_nama' => $r['department_nama'],
                'periode'         => $r['periode'],
                'sisa_qty'        => (float) $r['sisa_qty'],
                'alokasi_qty'     => $ambil,
            );
            $sisa_qty_dibutuhkan -= $ambil;
        }

        $warning = null;
        if ($sisa_qty_dibutuhkan > 0) {
            if (!empty($allocations)) {
                // stok ada tapi tidak cukup -- tambahkan sisanya ke baris
                // FIFO terakhir (over-alokasi, soft warning, tetap disimpan
                // kalau user konfirmasi).
                $last = count($allocations) - 1;
                $allocations[$last]['alokasi_qty'] += $sisa_qty_dibutuhkan;
                $warning = 'Stok FG tidak cukup. Kekurangan ' . $sisa_qty_dibutuhkan .
                           ' dibebankan ke periode ' . $allocations[$last]['periode'] . ' (melebihi sisa stok).';
            } else {
                $warning = 'Tidak ada stok FG (status Finish Good Stok) tersedia untuk JF ini.';
            }
        }

        return array(
            'allocations' => $allocations,
            'shortfall'   => max(0, $sisa_qty_dibutuhkan),
            'warning'     => $warning,
        );
    }

    /** Hapus semua cantolan FG milik satu delivery (dipakai sebelum re-apply auto-alokasi). */
    public function delete_pemakaian_fg_by_delivery($delivery_id)
    {
        $this->db->where('delivery_id', $delivery_id)->delete('trx_delivery_pemakaian_fg');
        return $this->db->affected_rows();
    }

    /**
     * Terapkan hasil preview_fg_allocation() ke trx_delivery_pemakaian_fg.
     * Mengganti (replace) seluruh cantolan FG milik delivery ini supaya
     * tombol "Terapkan" aman diklik ulang (idempotent), bukan menumpuk.
     *
     * @param array $allocations [['monitoring_id'=>.., 'alokasi_qty'=>..], ...]
     */
    public function apply_fg_allocation($delivery_id, array $allocations, $inputer_id)
    {
        $this->delete_pemakaian_fg_by_delivery($delivery_id);
        $count = 0;
        foreach ($allocations as $a) {
            if ((float) $a['alokasi_qty'] <= 0) {
                continue;
            }
            $this->create_pemakaian_fg($delivery_id, $a['monitoring_id'], $a['alokasi_qty'], $inputer_id);
            $count++;
        }
        return $count;
    }

    public function get_sisa_stok_fg($monitoring_id)
    {
        $m = $this->db->select('realisasi_good_qty')->where('id', $monitoring_id)
            ->get('trx_monitoring_produksi')->row_array();
        if (!$m) {
            return null;
        }

        $pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('monitoring_id', $monitoring_id)
            ->get('trx_delivery_pemakaian_fg')
            ->row_array();

        return (float) $m['realisasi_good_qty'] - (float) $pakai['total'];
    }

    public function create_pemakaian_fg($delivery_id, $monitoring_id, $qty_pakai, $inputer_id)
    {
        $this->db->insert('trx_delivery_pemakaian_fg', array(
            'delivery_id'   => $delivery_id,
            'monitoring_id' => $monitoring_id,
            'qty_pakai'     => $qty_pakai,
            'inputer_id'    => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function delete_pemakaian_fg($id)
    {
        $this->db->where('id', $id)->delete('trx_delivery_pemakaian_fg');
        return $this->db->affected_rows();
    }

    // ---------------------------------------------------------------
    // Stok WIP gudang / antar-proses (trx_wip_pemakaian) -- pola sama
    // dengan blok stok FG di atas, sumber tabel pemakaian berbeda.
    // ---------------------------------------------------------------

    /**
     * Cari baris monitoring dengan status_output = WIP_STOK untuk satu JF,
     * lengkap sisa stok WIP (realisasi_good_qty dikurangi total sudah
     * dipakai proses berikutnya manapun).
     *
     * Sisa qty dihitung GABUNGAN dari dua tempat pemakaian yang sama-sama
     * menyerap stok WIP ini: trx_wip_pemakaian (alokasi FIFO modul
     * Delivery ini) DAN trx_pemakaian_material (jenis_material='WIP',
     * cantolan manual di modul Monitoring Produksi). Sebelumnya hanya
     * trx_wip_pemakaian yang dihitung di sini sehingga stok yang sudah
     * dicantolkan lewat Monitoring Produksi tidak ikut mengurangi sisa --
     * berisiko WIP yang sama dialokasikan dobel. Pola ini sama dengan
     * get_sisa_stok_fg() / search_sumber_fg() milik modul FG.
     */
    public function search_stok_wip($jf_id)
    {
        return $this->db->select("m.id AS monitoring_id, p.nama AS proses_nama, d.department_name AS department_nama,
                            m.periode, m.realisasi_good_qty,
                            m.realisasi_good_qty - COALESCE(dl_pakai.total_pakai, 0) - COALESCE(pm_pakai.total_pakai, 0) AS sisa_qty")
            ->from('trx_monitoring_produksi m')
            ->join('mst_proses p', 'p.id = m.proses_id')
            ->join('mst_department d', 'd.id = m.department_id')
            ->join(
                "(SELECT monitoring_id_asal, SUM(qty_pakai) AS total_pakai
                  FROM trx_wip_pemakaian
                  GROUP BY monitoring_id_asal) dl_pakai",
                'dl_pakai.monitoring_id_asal = m.id',
                'left'
            )
            ->join(
                "(SELECT sumber_monitoring_id, SUM(qty_pakai) AS total_pakai
                  FROM trx_pemakaian_material
                  WHERE jenis_material = 'WIP'
                  GROUP BY sumber_monitoring_id) pm_pakai",
                'pm_pakai.sumber_monitoring_id = m.id',
                'left'
            )
            ->where('m.jf_id', $jf_id)
            ->where('m.status_output', 'WIP_STOK')
            ->order_by('m.periode', 'DESC')
            ->get()
            ->result_array();
    }

    /**
     * Sama seperti search_stok_wip() tapi diurut periode ASC (FIFO) dan
     * hanya baris bersisa (>0).
     */
    public function search_stok_wip_fifo($jf_id)
    {
        $rows = $this->search_stok_wip($jf_id);
        $rows = array_values(array_filter($rows, function ($r) {
            return (float) $r['sisa_qty'] > 0;
        }));
        usort($rows, function ($a, $b) {
            return strcmp($a['periode'], $b['periode']);
        });
        return $rows;
    }

    /**
     * Hitung rencana alokasi FIFO stok WIP untuk qty tertentu milik satu
     * JF, yang akan diserap oleh $monitoring_id_pakai (baris proses
     * berikutnya). TIDAK menyimpan apa pun -- murni preview.
     *
     * @return array('allocations'=>[['monitoring_id','proses_nama',
     *               'department_nama','periode','sisa_qty','alokasi_qty'], ...],
     *               'shortfall'=>float, 'warning'=>string|null)
     */
    public function preview_wip_allocation($jf_id, $qty)
    {
        $sisa_qty_dibutuhkan = (float) $qty;
        $rows = $this->search_stok_wip_fifo($jf_id);
        $allocations = array();

        foreach ($rows as $r) {
            if ($sisa_qty_dibutuhkan <= 0) {
                break;
            }
            $ambil = min($sisa_qty_dibutuhkan, (float) $r['sisa_qty']);
            $allocations[] = array(
                'monitoring_id'   => $r['monitoring_id'],
                'proses_nama'     => $r['proses_nama'],
                'department_nama' => $r['department_nama'],
                'periode'         => $r['periode'],
                'sisa_qty'        => (float) $r['sisa_qty'],
                'alokasi_qty'     => $ambil,
            );
            $sisa_qty_dibutuhkan -= $ambil;
        }

        $warning = null;
        if ($sisa_qty_dibutuhkan > 0) {
            if (!empty($allocations)) {
                $last = count($allocations) - 1;
                $allocations[$last]['alokasi_qty'] += $sisa_qty_dibutuhkan;
                $warning = 'Stok WIP tidak cukup. Kekurangan ' . $sisa_qty_dibutuhkan .
                           ' dibebankan ke periode ' . $allocations[$last]['periode'] . ' (melebihi sisa stok).';
            } else {
                $warning = 'Tidak ada stok WIP (status WIP Stok) tersedia untuk JF ini.';
            }
        }

        return array(
            'allocations' => $allocations,
            'shortfall'   => max(0, $sisa_qty_dibutuhkan),
            'warning'     => $warning,
        );
    }

    /** Hapus semua cantolan WIP milik satu baris monitoring pemakai (dipakai sebelum re-apply auto-alokasi). */
    public function delete_pemakaian_wip_by_monitoring_pakai($monitoring_id_pakai)
    {
        $this->db->where('monitoring_id_pakai', $monitoring_id_pakai)->delete('trx_wip_pemakaian');
        return $this->db->affected_rows();
    }

    /**
     * Terapkan hasil preview_wip_allocation() ke trx_wip_pemakaian.
     * Mengganti (replace) seluruh cantolan WIP milik baris monitoring
     * pemakai ini supaya tombol "Terapkan" aman diklik ulang (idempotent).
     *
     * @param array $allocations [['monitoring_id'=>.., 'alokasi_qty'=>..], ...]
     */
    public function apply_wip_allocation($monitoring_id_pakai, array $allocations, $inputer_id)
    {
        $this->delete_pemakaian_wip_by_monitoring_pakai($monitoring_id_pakai);
        $count = 0;
        foreach ($allocations as $a) {
            if ((float) $a['alokasi_qty'] <= 0) {
                continue;
            }
            $this->create_pemakaian_wip($a['monitoring_id'], $monitoring_id_pakai, $a['alokasi_qty'], $inputer_id);
            $count++;
        }
        return $count;
    }

    /**
     * Sisa stok WIP satu baris monitoring tertentu -- gabungan pemakaian
     * dari trx_wip_pemakaian dan trx_pemakaian_material (WIP), lihat
     * catatan di search_stok_wip(). Dipakai untuk validasi sebelum
     * create_pemakaian_wip() manual (bukan lewat preview/apply FIFO).
     */
    public function get_sisa_stok_wip($monitoring_id)
    {
        $m = $this->db->select('realisasi_good_qty')->where('id', $monitoring_id)
            ->get('trx_monitoring_produksi')->row_array();
        if (!$m) {
            return null;
        }

        $dl_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('monitoring_id_asal', $monitoring_id)
            ->get('trx_wip_pemakaian')
            ->row_array();

        $pm_pakai = $this->db->select('COALESCE(SUM(qty_pakai), 0) AS total')
            ->where('sumber_monitoring_id', $monitoring_id)
            ->where('jenis_material', 'WIP')
            ->get('trx_pemakaian_material')
            ->row_array();

        return (float) $m['realisasi_good_qty'] - (float) $dl_pakai['total'] - (float) $pm_pakai['total'];
    }

    public function create_pemakaian_wip($monitoring_id_asal, $monitoring_id_pakai, $qty_pakai, $inputer_id)
    {
        $this->db->insert('trx_wip_pemakaian', array(
            'monitoring_id_asal'  => $monitoring_id_asal,
            'monitoring_id_pakai' => $monitoring_id_pakai,
            'qty_pakai'           => $qty_pakai,
            'inputer_id'          => $inputer_id,
        ));
        return $this->db->insert_id();
    }

    public function delete_pemakaian_wip($id)
    {
        $this->db->where('id', $id)->delete('trx_wip_pemakaian');
        return $this->db->affected_rows();
    }

    // ---------------------------------------------------------------
    // Kelengkapan laporan per periode -- TIDAK pakai tabel master routing.
    // "Departemen mana yang seharusnya lapor" diturunkan dari histori
    // trx_monitoring_produksi milik JF yang sama (periode-periode
    // sebelumnya), karena data ini sifatnya laporan aktual, bukan
    // rancangan/definisi proses yang ditentukan di muka.
    // ---------------------------------------------------------------

    /**
     * Departemen+proses yang PERNAH melapor untuk JF ini, sebelum $periode
     * yang sedang dicek. Ini "ekspektasi" yang didapat dari histori,
     * bukan master.
     */
    private function get_histori_departemen_proses_jf($jf_id, $periode_sebelum)
    {
        return $this->db->select('DISTINCT department_id, proses_id')
            ->from('trx_monitoring_produksi')
            ->where('jf_id', $jf_id)
            ->where('periode <', $periode_sebelum)
            ->get()
            ->result_array();
    }

    /**
     * Bandingkan histori (ekspektasi turunan) dengan realisasi periode ini.
     *
     * @param array|null $dept_ids  Hasil Import_model::get_user_allowed_departments().
     *                    null = tidak dibatasi (lihat semua departemen).
     *                    Array = hanya department_id di dalamnya yang
     *                    dikembalikan -- jadi tiap user cuma lihat
     *                    outstanding departemennya sendiri, guard sama
     *                    seperti endpoint lain di controller ini.
     *
     * @return array(
     *   'belum_input' => [...department_id/proses_id yang biasanya lapor
     *                     tapi absen di periode ini -- perlu ditindaklanjuti],
     *   'baru_muncul' => [...yang lapor di periode ini tapi belum pernah
     *                     ada di histori -- informasi, bukan warning],
     *   'catatan'     => string|null  -- null kalau histori tidak ada (JF
     *                     baru / periode pertama), karena saat itu sistem
     *                     memang belum bisa membedakan kedua kasus.
     * )
     */
    public function cek_kelengkapan_periode($jf_id, $periode, $dept_ids = null)
    {
        $histori = $this->get_histori_departemen_proses_jf($jf_id, $periode);

        if (empty($histori)) {
            return array(
                'belum_input' => array(),
                'baru_muncul' => array(),
                'catatan'     => 'Belum ada histori periode sebelumnya untuk JF ini -- sistem belum bisa membedakan "belum input" vs "memang tidak dilewati" sampai minimal 1 periode lampau tersedia.',
            );
        }

        $actual = $this->db->select('DISTINCT department_id, proses_id')
            ->from('trx_monitoring_produksi')
            ->where('jf_id', $jf_id)
            ->where('periode', $periode)
            ->get()
            ->result_array();

        $key = function ($r) { return $r['department_id'] . '-' . $r['proses_id']; };
        $histori_map = array();
        foreach ($histori as $h) { $histori_map[$key($h)] = $h; }
        $actual_map = array();
        foreach ($actual as $a) { $actual_map[$key($a)] = $a; }

        $belum_input = array();
        foreach ($histori_map as $k => $h) {
            if (!isset($actual_map[$k])) {
                $belum_input[] = $h;
            }
        }
        $baru_muncul = array();
        foreach ($actual_map as $k => $a) {
            if (!isset($histori_map[$k])) {
                $baru_muncul[] = $a;
            }
        }

        // Guard department: sama seperti endpoint lain, kalau user dibatasi
        // (dept_ids bukan null), hanya kembalikan outstanding milik
        // departemennya sendiri.
        if ($dept_ids !== null) {
            $filter = function ($rows) use ($dept_ids) {
                return array_values(array_filter($rows, function ($r) use ($dept_ids) {
                    return in_array((int) $r['department_id'], $dept_ids, true);
                }));
            };
            $belum_input = $filter($belum_input);
            $baru_muncul = $filter($baru_muncul);
        }

        // Lengkapi nama departemen/proses untuk ditampilkan.
        $lengkapi = function ($rows) {
            foreach ($rows as &$r) {
                $d = $this->db->select('department_name')->where('id', $r['department_id'])
                    ->get('mst_department')->row_array();
                $p = $this->db->select('nama')->where('id', $r['proses_id'])
                    ->get('mst_proses')->row_array();
                $r['department_nama'] = $d ? $d['department_name'] : null;
                $r['proses_nama'] = $p ? $p['nama'] : null;
            }
            return $rows;
        };

        return array(
            'belum_input' => $lengkapi($belum_input),
            'baru_muncul' => $lengkapi($baru_muncul),
            'catatan'     => null,
        );
    }
}