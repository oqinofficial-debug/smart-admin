<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reader untuk file .xlsx (Office Open XML), TANPA dependency Composer /
 * PhpSpreadsheet -- xlsx sebenarnya cuma arsip ZIP berisi file-file XML.
 *
 * Perubahan dari versi sebelumnya:
 *  1) list_sheets() -- baca xl/workbook.xml + xl/_rels/workbook.xml.rels
 *     supaya bisa menampilkan pilihan sheet ke user (dulu selalu sheet1
 *     yang dibaca, hardcoded).
 *  2) Parsing baris pakai XMLReader (pull/streaming parser), BUKAN
 *     simplexml_load_string ke seluruh dokumen. Untuk sheet besar (puluhan
 *     ribu baris) ini jauh lebih hemat memori dan lebih cepat, karena tidak
 *     perlu membangun seluruh DOM di memori sekaligus.
 */
require_once APPPATH . 'libraries/File_reader_interface.php';

class Xlsx_reader implements File_reader_interface
{
    public function list_sheets($filepath)
    {
        $zip = $this->_open_zip($filepath);

        $wb_xml = $zip->getFromName('xl/workbook.xml');
        if ($wb_xml === false) {
            $zip->close();
            throw new Exception('File tidak valid: xl/workbook.xml tidak ditemukan di dalam arsip xlsx.');
        }
        $rels_xml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $zip->close();

        $wb = @simplexml_load_string($wb_xml);
        if ($wb === false) {
            throw new Exception('Gagal membaca struktur workbook (xl/workbook.xml rusak).');
        }

        // pemetaan r:id -> path file sheet fisik, lewat file .rels
        $rid_to_target = array();
        if ($rels_xml !== false) {
            $rels = @simplexml_load_string($rels_xml);
            if ($rels !== false) {
                foreach ($rels->Relationship as $rel) {
                    $rid_to_target[(string) $rel['Id']] = (string) $rel['Target'];
                }
            }
        }

        $sheets = array();
        $fallback_index = 1;
        $ns_r = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

        foreach ($wb->sheets->sheet as $sheet) {
            $name = (string) $sheet['name'];
            $r_attrs = $sheet->attributes($ns_r);
            $rid = isset($r_attrs['id']) ? (string) $r_attrs['id'] : '';

            if (isset($rid_to_target[$rid])) {
                $target = ltrim($rid_to_target[$rid], '/');
                $path = (strpos($target, 'worksheets/') === 0) ? 'xl/' . $target : 'xl/' . $target;
            } else {
                $path = 'xl/worksheets/sheet' . $fallback_index . '.xml';
            }

            $sheets[] = array('name' => $name, 'path' => $path);
            $fallback_index++;
        }

        if (empty($sheets)) {
            throw new Exception('Tidak ada worksheet yang terbaca di dalam file xlsx ini.');
        }

        return $sheets;
    }

    public function read($filepath, $sheet = null, array $options = array())
    {
        $sheets = $this->list_sheets($filepath);

        $target = null;
        if ($sheet === null) {
            $target = $sheets[0];
        } else {
            foreach ($sheets as $s) {
                if ($s['name'] === $sheet) {
                    $target = $s;
                    break;
                }
            }
            if ($target === null) {
                throw new Exception('Sheet "' . $sheet . '" tidak ditemukan di dalam file.');
            }
        }

        $zip = $this->_open_zip($filepath);
        $shared_strings = $this->_read_shared_strings($zip);

        $sheet_path = $target['path'];
        if ($zip->locateName($sheet_path) === false) {
            // fallback: cari file worksheet apa saja kalau path dari rels meleset
            $sheet_path = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet[0-9]+\.xml$#', $name)) {
                    $sheet_path = $name;
                    break;
                }
            }
        }

        if ($sheet_path === null) {
            $zip->close();
            throw new Exception('File worksheet untuk sheet "' . $target['name'] . '" tidak ditemukan di dalam arsip.');
        }

        $sheet_xml = $zip->getFromName($sheet_path);
        $zip->close();

        if ($sheet_xml === false) {
            throw new Exception('Gagal membaca isi worksheet "' . $target['name'] . '".');
        }

        // XMLReader butuh path file (bukan string di memori) untuk benar-benar
        // streaming, jadi ditulis dulu ke temp file.
        $tmp_path = tempnam(sys_get_temp_dir(), 'xlsx_sheet_');
        file_put_contents($tmp_path, $sheet_xml);
        unset($sheet_xml);

        try {
            $rows = $this->_stream_parse_sheet($tmp_path, $shared_strings);
        } finally {
            @unlink($tmp_path);
        }

        return $rows;
    }

    private function _open_zip($filepath)
    {
        if (!is_file($filepath)) {
            throw new Exception('File tidak ditemukan: ' . $filepath);
        }
        if (!class_exists('ZipArchive')) {
            throw new Exception('Ekstensi PHP "zip" tidak aktif di server, tidak bisa membaca file xlsx.');
        }
        $zip = new ZipArchive();
        if ($zip->open($filepath) !== true) {
            throw new Exception('File bukan .xlsx yang valid (gagal dibuka sebagai arsip ZIP).');
        }
        return $zip;
    }

    private function _read_shared_strings($zip)
    {
        $xml_content = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml_content === false) {
            return array();
        }
        $xml = @simplexml_load_string($xml_content);
        if ($xml === false) {
            return array();
        }

        $strings = array();
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } else {
                // rich text (banyak <r><t>...</t></r>), gabungkan semua potongannya
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $strings[] = $text;
            }
        }
        return $strings;
    }

    /**
     * Baca sheetN.xml baris demi baris pakai XMLReader (streaming), bukan
     * load seluruh dokumen ke DOM sekaligus seperti simplexml. Tiap elemen
     * <row> diambil outer XML-nya lalu diparse kecil-kecilan (murah, karena
     * cuma 1 baris) -- kombinasi ini jauh lebih hemat memori untuk sheet
     * dengan puluhan ribu baris dibanding load semuanya lewat SimpleXML.
     */
    private function _stream_parse_sheet($xml_path, array $shared_strings)
    {
        $reader = new XMLReader();
        if (!$reader->open($xml_path)) {
            throw new Exception('Gagal membuka worksheet untuk dibaca (streaming).');
        }

        $rows = array();

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'row') {
                continue;
            }

            $row_xml = $reader->readOuterXML();
            $row_node = @simplexml_load_string($row_xml);
            if ($row_node === false) {
                continue;
            }

            $row_data = array();
            foreach ($row_node->c as $cell) {
                $ref = (string) $cell['r'];               // contoh: "C5"
                $col_letter = preg_replace('/[0-9]/', '', $ref);
                if ($col_letter === '') {
                    continue;
                }

                $type = (string) $cell['t'];
                $value = '';

                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    if ($type === 's') {
                        // index ke shared strings
                        $value = isset($shared_strings[(int) $raw]) ? $shared_strings[(int) $raw] : '';
                    } else {
                        // angka, tanggal (serial number), atau string biasa (t="str")
                        $value = $raw;
                    }
                } elseif (isset($cell->is->t)) {
                    // inline string
                    $value = (string) $cell->is->t;
                }

                $row_data[$col_letter] = trim($value);
            }

            if (!empty($row_data)) {
                $rows[] = $row_data;
            }
        }

        $reader->close();
        return $rows;
    }

    /**
     * Konversi nilai serial tanggal Excel (angka) ke format Y-m-d.
     * Excel menghitung hari sejak 1899-12-30 (termasuk bug tahun kabisat 1900).
     */
    public function excel_value_to_date($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_numeric($value)) {
            // sudah berupa teks tanggal (misal dari kolom bertipe teks), coba parse langsung
            $timestamp = strtotime($value);
            return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
        }
        $unix_timestamp = ((int) $value - 25569) * 86400;
        return gmdate('Y-m-d', $unix_timestamp);
    }

    public function excel_value_to_time($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_numeric($value)) {
            return $value;
        }
        $fraction = (float) $value - floor((float) $value);
        $seconds = round($fraction * 86400);
        return gmdate('H:i:s', $seconds);
    }
}
