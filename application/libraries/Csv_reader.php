<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reader untuk file .csv dan .txt (dianggap sama: teks berpemisah/delimited).
 *
 * Fitur:
 *  - Auto-detect delimiter (koma, titik-koma, tab, pipe) dari baris pertama,
 *    karena Excel Indonesia sering export CSV pakai ";" bukan ",".
 *  - Bisa dipaksa pakai delimiter tertentu lewat $options['delimiter']
 *    (berguna untuk .txt yang user tahu persis formatnya, mis. tab).
 *  - Buang BOM UTF-8 di awal file kalau ada.
 *  - Convert ke UTF-8 kalau filenya masih Windows-1252 / Latin-1
 *    (umum untuk file yang disimpan dari Excel versi lama).
 *
 * Streaming: baca baris demi baris pakai fgetcsv(), bukan load seluruh file
 * ke memori sekaligus -- penting untuk file besar.
 */
class Csv_reader implements File_reader_interface
{
    public function list_sheets($filepath)
    {
        // csv/txt tidak punya konsep sheet, tapi dibalikin 1 item semu supaya
        // alur di controller (cek "lebih dari 1 sheet? tampilkan pilihan")
        // tetap seragam untuk semua format.
        return array(
            array('name' => 'Sheet1'),
        );
    }

    public function read($filepath, $sheet = null, array $options = array())
    {
        if (!is_file($filepath)) {
            throw new Exception('File tidak ditemukan: ' . $filepath);
        }

        $delimiter = isset($options['delimiter']) && $options['delimiter'] !== ''
            ? $options['delimiter']
            : $this->_detect_delimiter($filepath);

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new Exception('Gagal membuka file untuk dibaca: ' . $filepath);
        }

        // buang BOM UTF-8 (EF BB BF) di 3 byte pertama kalau ada
        $first_bytes = fread($handle, 3);
        if ($first_bytes !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows = array();
        while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
            // lewati baris yang benar-benar kosong (fgetcsv balikin [null] untuk baris kosong)
            if (count($cols) === 1 && (trim((string) $cols[0]) === '' || $cols[0] === null)) {
                continue;
            }

            $row = array();
            foreach ($cols as $i => $val) {
                $col_letter = $this->_index_to_letter($i);
                $row[$col_letter] = $this->_to_utf8(trim((string) $val));
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * Sniff delimiter dari baris pertama file: hitung kemunculan tiap
     * kandidat delimiter, pilih yang paling sering muncul.
     */
    private function _detect_delimiter($filepath)
    {
        $first_line = '';
        $handle = fopen($filepath, 'r');
        if ($handle) {
            $first_line = (string) fgets($handle);
            fclose($handle);
        }

        $candidates = array(',', ';', "\t", '|');
        $best = ',';
        $best_count = 0;
        foreach ($candidates as $delimiter) {
            $count = substr_count($first_line, $delimiter);
            if ($count > $best_count) {
                $best_count = $count;
                $best = $delimiter;
            }
        }
        return $best;
    }

    private function _to_utf8($value)
    {
        if ($value === '') {
            return $value;
        }
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
            if ($converted !== false) {
                return $converted;
            }
        }
        return $value;
    }

    private function _index_to_letter($index)
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int) (($index - $mod) / 26);
        }
        return $letter;
    }
}
