<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Xlsx_reader
 *
 * Pembaca file .xlsx yang RINGAN, tanpa dependency Composer/PhpSpreadsheet.
 * Cukup pakai ekstensi bawaan PHP: ZipArchive + SimpleXML.
 *
 * File .xlsx sebenarnya adalah file ZIP berisi beberapa XML:
 *   - xl/worksheets/sheet1.xml   -> isi sel per baris/kolom (sheet pertama)
 *   - xl/sharedStrings.xml       -> tabel string (sel bertipe teks menunjuk
 *                                   ke index di tabel ini, bukan simpan
 *                                   teks langsung, untuk hemat ukuran file)
 *
 * Cukup untuk kebutuhan import sederhana: baca sheet pertama menjadi
 * array 2 dimensi (baris x kolom), semua nilai dikembalikan sebagai
 * string mentah (pemanggil yang cast ke tanggal/angka sesuai kebutuhan).
 *
 * Hanya mendukung file .xlsx (Excel 2007+). File .xls (format lama/biner)
 * TIDAK didukung -- minta user simpan-ulang sebagai .xlsx, atau upload csv.
 */
class Xlsx_reader
{
    /**
     * Baca sheet pertama dari file .xlsx menjadi array baris.
     * Setiap baris adalah array asosiatif kolom-huruf => nilai string,
     * mis. ['A' => 'Tanggal', 'B' => 'NIK', ...], supaya kolom kosong
     * di tengah tetap punya "posisi" yang benar.
     *
     * @param  string $filepath path absolut ke file .xlsx
     * @return array
     * @throws Exception kalau file tidak valid / bukan xlsx
     */
    public function read($filepath)
    {
        if (!is_file($filepath)) {
            throw new Exception('File tidak ditemukan.');
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception('Ekstensi PHP "zip" tidak aktif di server ini, tidak bisa membaca file .xlsx.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath) !== TRUE) {
            throw new Exception('File bukan .xlsx yang valid (gagal dibuka sebagai ZIP).');
        }

        $shared_strings = $this->_read_shared_strings($zip);

        $sheet_path = $this->_find_first_sheet_path($zip);
        if (!$sheet_path) {
            $zip->close();
            throw new Exception('Tidak menemukan worksheet di dalam file.');
        }

        $sheet_xml = $zip->getFromName($sheet_path);
        $zip->close();

        if ($sheet_xml === FALSE) {
            throw new Exception('Gagal membaca isi worksheet.');
        }

        return $this->_parse_sheet_xml($sheet_xml, $shared_strings);
    }

    /**
     * Sheet1.xml bisa berada di path yang berbeda-beda tergantung aplikasi
     * yang membuat file (Excel, LibreOffice, Google Sheets export, dst).
     * Cara paling aman: baca xl/workbook.xml + _rels untuk urutan sheet
     * yang sebenarnya. Untuk kesederhanaan (hanya butuh sheet pertama),
     * kita coba path standar dulu, baru fallback cari file worksheet apa
     * saja di dalam arsip.
     */
    private function _find_first_sheet_path(ZipArchive $zip)
    {
        if ($zip->locateName('xl/worksheets/sheet1.xml') !== FALSE) {
            return 'xl/worksheets/sheet1.xml';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                return $name;
            }
        }

        return null;
    }

    private function _read_shared_strings(ZipArchive $zip)
    {
        $xml_content = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml_content === FALSE) {
            return array(); // wajar kalau file tidak punya sharedStrings (semua sel angka/formula)
        }

        $xml = simplexml_load_string($xml_content);
        if ($xml === FALSE) {
            return array();
        }

        $strings = array();
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                // teks sederhana <si><t>...</t></si>
                $strings[] = (string) $si->t;
            } else {
                // teks dengan formatting campuran <si><r><t>...</t></r>...</si>
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $strings[] = $text;
            }
        }

        return $strings;
    }

    private function _parse_sheet_xml($xml_content, array $shared_strings)
    {
        $xml = simplexml_load_string($xml_content);
        if ($xml === FALSE) {
            throw new Exception('Format worksheet tidak bisa dibaca (XML rusak).');
        }

        $rows = array();

        foreach ($xml->sheetData->row as $row) {
            $row_data = array();

            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];         // contoh: "C5"
                $col_letter = preg_replace('/[0-9]/', '', $ref);
                $type = (string) $cell['t'];        // 's' = shared string, 'str' = formula string, '' = angka

                $value = '';
                if (isset($cell->v)) {
                    $raw = (string) $cell->v;
                    if ($type === 's') {
                        $value = isset($shared_strings[(int) $raw]) ? $shared_strings[(int) $raw] : '';
                    } elseif ($type === 'str' || $type === 'inlineStr') {
                        $value = $raw;
                    } else {
                        $value = $raw; // angka / tanggal (serial number Excel) / boolean
                    }
                } elseif (isset($cell->is->t)) {
                    // inline string <c t="inlineStr"><is><t>...</t></is></c>
                    $value = (string) $cell->is->t;
                }

                if ($col_letter !== '') {
                    $row_data[$col_letter] = trim($value);
                }
            }

            if (!empty($row_data)) {
                $rows[] = $row_data;
            }
        }

        return $rows;
    }

    /**
     * Konversi serial number tanggal Excel (mis. 45678) ke format Y-m-d.
     * Excel menyimpan tanggal sebagai jumlah hari sejak 1899-12-30.
     * Kalau nilainya sudah berupa teks tanggal biasa (dd/mm/yyyy dst),
     * dikembalikan apa adanya lewat strtotime.
     */
    public function excel_value_to_date($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $unix_timestamp = ((int) $value - 25569) * 86400; // 25569 = hari antara 1899-12-30 dan 1970-01-01
            return gmdate('Y-m-d', $unix_timestamp);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * Konversi serial number waktu Excel (pecahan hari, mis. 0.5 = 12:00)
     * atau teks jam biasa (HH:MM / HH:MM:SS) ke format H:i:s.
     */
    public function excel_value_to_time($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $fraction = (float) $value - floor((float) $value);
            $total_seconds = (int) round($fraction * 86400);
            return gmdate('H:i:s', $total_seconds);
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value)) {
            return strlen($value) === 5 ? $value . ':00' : $value;
        }

        return null;
    }
}
