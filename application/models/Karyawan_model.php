<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Karyawan_model extends CI_Model
{
    protected $table = 'mst_karyawan';

    public function get_all()
    {
        $rows = $this->db->order_by('nama', 'ASC')->get($this->table)->result_array();
        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($id)
    {
        $row = $this->db->where('id', $id)->get($this->table)->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_nik($nik, $exclude_id = null)
    {
        $this->db->where('nik', $nik);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->table)->row_array();
    }

    public function create($data)
    {
        $this->db->insert($this->table, array(
            'nik'                => $data['nik'],
            'nama'               => $data['nama'],
            'status_kepegawaian' => $data['status_kepegawaian'],
            'is_active'          => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ));
        return $this->db->insert_id();
    }

    public function update($id, $data)
    {
        $this->db->where('id', $id)->update($this->table, array(
            'nik'                => $data['nik'],
            'nama'               => $data['nama'],
            'status_kepegawaian' => $data['status_kepegawaian'],
            'is_active'          => (bool) $data['is_active'],
        ));
    }

    /**
     * Tambah massal via copy-paste (mis. dari Excel). Tiap elemen $rows
     * adalah array kolom mentah hasil parse_bulk_paste():
     * [nik, nama, status_kepegawaian, aktif?].
     *
     * Kalau NIK SUDAH ADA, baris yang bersangkutan DI-REPLACE (nama, status
     * kepegawaian, status aktif ditimpa) -- beda dari add() biasa yang
     * menolak NIK duplikat. Kalau belum ada, jadi baris baru.
     *
     * @return array array('inserted'=>int, 'updated'=>int,
     *               'errors'=>array(array('line'=>int, 'message'=>string)))
     */
    public function bulk_upsert(array $rows)
    {
        $inserted = 0;
        $updated  = 0;
        $errors   = array();

        foreach ($rows as $i => $cols) {
            $line   = $i + 1;
            $nik    = isset($cols[0]) ? trim($cols[0]) : '';
            $nama   = isset($cols[1]) ? trim($cols[1]) : '';
            $status = isset($cols[2]) ? strtoupper(trim($cols[2])) : '';

            if ($nik === '' || $nama === '') {
                $errors[] = array('line' => $line, 'message' => 'NIK dan Nama wajib diisi.');
                continue;
            }
            if (!in_array($status, array('HARIAN', 'BORONG'), true)) {
                $errors[] = array('line' => $line, 'message' => 'Status Kepegawaian harus HARIAN atau BORONG (dapat: "' . $status . '").');
                continue;
            }
            if (mb_strlen($nik) > 50 || mb_strlen($nama) > 100) {
                $errors[] = array('line' => $line, 'message' => 'NIK/Nama melebihi panjang maksimum.');
                continue;
            }

            $is_active = parse_flexible_bool(isset($cols[3]) ? $cols[3] : '', true);
            $existing  = $this->db->where('nik', $nik)->get($this->table)->row_array();

            try {
                if ($existing) {
                    $this->db->where('id', $existing['id'])->update($this->table, array(
                        'nama'                => $nama,
                        'status_kepegawaian'  => $status,
                        'is_active'           => $is_active,
                    ));
                    $updated++;
                } else {
                    $this->db->insert($this->table, array(
                        'nik'                => $nik,
                        'nama'               => $nama,
                        'status_kepegawaian' => $status,
                        'is_active'          => $is_active,
                    ));
                    $inserted++;
                }
            } catch (Exception $e) {
                $errors[] = array('line' => $line, 'message' => 'Gagal simpan: ' . $e->getMessage());
            }
        }

        return array('inserted' => $inserted, 'updated' => $updated, 'errors' => $errors);
    }

    /** Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari trx_laporan_produksi. */
    public function delete($id)
    {
        try {
            $this->db->where('id', $id)->delete($this->table);
            return array('success' => true, 'message' => 'Karyawan dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, kemungkinan masih dipakai di data laporan (SPV/LL/Operator).',
            );
        }
    }
}
