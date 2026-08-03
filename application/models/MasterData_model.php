<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MasterData_model
 *
 * Model GENERIK untuk master data berstruktur sama: kode + nama + is_active.
 * Menangani 5 tabel: mst_shift, mst_mesin, mst_aktivitas, mst_proses,
 * mst_pekerjaan_borong — dipilih lewat parameter $type.
 *
 * Kalau nanti ada master baru dengan struktur sama, tinggal tambah satu
 * baris di $config, tidak perlu bikin model baru.
 */
class MasterData_model extends CI_Model
{
    private $config = array(
        'shift'     => array('table' => 'mst_shift',            'label' => 'Shift'),
        'mesin'     => array('table' => 'mst_mesin',             'label' => 'Mesin'),
        'aktivitas' => array('table' => 'mst_aktivitas',         'label' => 'Aktivitas'),
        'proses'    => array('table' => 'mst_proses',            'label' => 'Proses'),
        'borong'    => array('table' => 'mst_pekerjaan_borong',  'label' => 'Pekerjaan Borong'),
        'jf'        => array('table' => 'mst_jf',                'label' => 'JF (Job Order)'),
    );

    public function get_types()
    {
        return $this->config;
    }

    public function get_label($type)
    {
        return isset($this->config[$type]) ? $this->config[$type]['label'] : null;
    }

    public function is_valid_type($type)
    {
        return isset($this->config[$type]);
    }

    private function _table($type)
    {
        return $this->config[$type]['table'];
    }

    public function get_all($type)
    {
        $rows = $this->db->order_by('nama', 'ASC')->get($this->_table($type))->result_array();
        foreach ($rows as &$row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $rows;
    }

    public function get($type, $id)
    {
        $row = $this->db->where('id', $id)->get($this->_table($type))->row_array();
        if ($row) {
            $row['is_active'] = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function get_by_kode($type, $kode, $exclude_id = null)
    {
        $this->db->where('kode', $kode);
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return $this->db->get($this->_table($type))->row_array();
    }

    public function create($type, $data)
    {
        $this->db->insert($this->_table($type), array(
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
        ));
        return $this->db->insert_id();
    }

    public function update($type, $id, $data)
    {
        $this->db->where('id', $id)->update($this->_table($type), array(
            'kode'      => $data['kode'],
            'nama'      => $data['nama'],
            'is_active' => (bool) $data['is_active'],
        ));
    }

    /** Hard delete. Ditolak kalau DB menolak karena FK RESTRICT dari trx_laporan_produksi. */
    public function delete($type, $id)
    {
        try {
            $this->db->where('id', $id)->delete($this->_table($type));
            return array('success' => true, 'message' => ucfirst($this->get_label($type)) . ' dihapus.');
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Tidak bisa dihapus, kemungkinan masih dipakai di data laporan.',
            );
        }
    }
}
