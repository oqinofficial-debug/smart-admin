<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Import_alias_model
 *
 * Mengelola mst_import_kolom (field tujuan import laporan produksi) dan
 * mst_import_alias (daftar nama kolom Excel yang dipetakan ke tiap field).
 *
 * field_key BERSIFAT TETAP (dipakai literal di Import_model saat insert ke
 * trx_laporan_produksi), jadi model ini tidak menyediakan create/delete
 * untuk mst_import_kolom -- hanya update label/wajib/aktif. Yang benar-benar
 * bisa dikelola bebas oleh user adalah daftar alias per field.
 */
class Import_alias_model extends CI_Model
{
    /**
     * Semua field tujuan beserta alias-nya, terurut sesuai sort_order.
     * Return: [ ['id'=>.., 'field_key'=>.., 'field_label'=>.., 'is_required'=>bool,
     *            'is_active'=>bool, 'aliases'=>[ ['id'=>.., 'alias_text'=>..], ... ] ], ... ]
     */
    public function get_all_with_alias()
    {
        $kolom_rows = $this->db->order_by('sort_order', 'ASC')
                                ->get('mst_import_kolom')
                                ->result_array();

        $alias_rows = $this->db->order_by('alias_text', 'ASC')
                                ->get('mst_import_alias')
                                ->result_array();

        $alias_by_kolom = array();
        foreach ($alias_rows as $a) {
            $alias_by_kolom[$a['kolom_id']][] = $a;
        }

        foreach ($kolom_rows as &$k) {
            $k['is_required'] = normalize_bool($k['is_required']);
            $k['is_active']   = normalize_bool($k['is_active']);
            $k['aliases']     = isset($alias_by_kolom[$k['id']]) ? $alias_by_kolom[$k['id']] : array();
        }

        return $kolom_rows;
    }

    public function get_kolom($id)
    {
        $row = $this->db->where('id', $id)->get('mst_import_kolom')->row_array();
        if ($row) {
            $row['is_required'] = normalize_bool($row['is_required']);
            $row['is_active']   = normalize_bool($row['is_active']);
        }
        return $row;
    }

    public function update_kolom($id, $data)
    {
        $this->db->where('id', $id)->update('mst_import_kolom', array(
            'field_label' => $data['field_label'],
            'is_required' => (bool) $data['is_required'],
            'is_active'   => (bool) $data['is_active'],
        ));
    }

    /**
     * Peta lengkap field_key => daftar alias (huruf kecil, di-trim), dipakai
     * Import_model untuk auto-mapping header excel. Hanya field yang aktif.
     */
    public function get_active_alias_map()
    {
        $sql = "
            SELECT k.field_key, k.field_label, k.is_required, a.alias_text
            FROM mst_import_kolom k
            LEFT JOIN mst_import_alias a ON a.kolom_id = k.id
            WHERE k.is_active = true
            ORDER BY k.sort_order ASC
        ";
        $rows = $this->db->query($sql)->result_array();

        $map = array();
        foreach ($rows as $r) {
            if (!isset($map[$r['field_key']])) {
                $map[$r['field_key']] = array(
                    'label'    => $r['field_label'],
                    'required' => normalize_bool($r['is_required']),
                    'aliases'  => array(),
                );
            }
            if (!empty($r['alias_text'])) {
                $map[$r['field_key']]['aliases'][] = $r['alias_text'];
            }
            // field_key sendiri selalu ikut dianggap alias yang valid
            if (!in_array($r['field_key'], $map[$r['field_key']]['aliases'])) {
                $map[$r['field_key']]['aliases'][] = $r['field_key'];
            }
        }

        return $map;
    }

    public function alias_exists($alias_text, $exclude_id = null)
    {
        $this->db->where('LOWER(alias_text)', strtolower(trim($alias_text)));
        if ($exclude_id) {
            $this->db->where('id !=', $exclude_id);
        }
        return (bool) $this->db->get('mst_import_alias')->row_array();
    }

    public function add_alias($kolom_id, $alias_text)
    {
        $this->db->insert('mst_import_alias', array(
            'kolom_id'   => $kolom_id,
            'alias_text' => trim($alias_text),
        ));
        return $this->db->insert_id();
    }

    public function delete_alias($id)
    {
        $this->db->where('id', $id)->delete('mst_import_alias');
    }

    public function get_alias($id)
    {
        return $this->db->where('id', $id)->get('mst_import_alias')->row_array();
    }
}
