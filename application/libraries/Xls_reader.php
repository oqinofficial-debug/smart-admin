<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Reader untuk file .xls lama (format biner Excel 97-2003 / "BIFF8"),
 * ditulis pure-PHP tanpa dependency Composer/PhpSpreadsheet, konsisten
 * dengan gaya Xlsx_reader yang sudah ada di project ini.
 *
 * File .xls adalah "OLE Compound File" (kadang disebut CFB) yang di
 * dalamnya berisi stream bernama "Workbook" (atau "Book" untuk versi
 * sangat lama / BIFF5). Class ini melakukan 2 tahap:
 *   1) Parse struktur OLE/CFB untuk mengeluarkan isi stream "Workbook".
 *   2) Parse record-record BIFF8 di dalam stream itu untuk mengambil
 *      data sel per sheet.
 *
 * CATATAN KETERBATASAN (harap dibaca):
 *  - Fokus pada kasus umum: sheet berisi data tabel biasa (angka, teks,
 *    tanggal sebagai angka serial, hasil formula sederhana).
 *  - Tidak menangani semua kemungkinan format .xls yang eksotis (misal
 *    file BIFF5 murni / Excel 5.0-95, proteksi/enkripsi file, pivot table,
 *    macro). Kalau parsing gagal total di file tertentu, pesan error akan
 *    menyarankan untuk membuka file itu di Excel lalu "Save As" ke .xlsx,
 *    yang jauh lebih mudah & cepat dibaca (lihat Xlsx_reader).
 */
class Xls_reader implements File_reader_interface
{
    /** @var string isi mentah stream "Workbook"/"Book", di-cache per file */
    private $_stream_cache = array();

    public function list_sheets($filepath)
    {
        $stream = $this->_get_workbook_stream($filepath);
        $globals = $this->_parse_globals($stream);

        $sheets = array();
        foreach ($globals['boundsheets'] as $b) {
            $sheets[] = array('name' => $b['name']);
        }

        if (empty($sheets)) {
            throw new Exception('Tidak ada worksheet yang terbaca di file .xls ini.');
        }

        return $sheets;
    }

    public function read($filepath, $sheet = null, array $options = array())
    {
        $stream = $this->_get_workbook_stream($filepath);
        $globals = $this->_parse_globals($stream);

        $target = null;
        if ($sheet === null) {
            $target = isset($globals['boundsheets'][0]) ? $globals['boundsheets'][0] : null;
        } else {
            foreach ($globals['boundsheets'] as $b) {
                if ($b['name'] === $sheet) {
                    $target = $b;
                    break;
                }
            }
        }

        if ($target === null) {
            throw new Exception('Sheet "' . $sheet . '" tidak ditemukan di dalam file .xls ini.');
        }

        return $this->_parse_sheet_rows($stream, $target['offset'], $globals['shared_strings']);
    }

    // ------------------------------------------------------------------
    // TAHAP 1: OLE / Compound File Binary (CFB) reader
    // ------------------------------------------------------------------

    private function _get_workbook_stream($filepath)
    {
        if (isset($this->_stream_cache[$filepath])) {
            return $this->_stream_cache[$filepath];
        }

        if (!is_file($filepath)) {
            throw new Exception('File tidak ditemukan: ' . $filepath);
        }

        $data = file_get_contents($filepath);
        if ($data === false || strlen($data) < 512) {
            throw new Exception('File .xls tidak valid atau kosong.');
        }

        $signature = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        if (substr($data, 0, 8) !== $signature) {
            throw new Exception('File bukan format .xls (OLE Compound File) yang valid.');
        }

        $sector_shift = $this->_read_u2($data, 30);
        $mini_sector_shift = $this->_read_u2($data, 32);
        $sector_size = 1 << $sector_shift;         // biasanya 512
        $mini_sector_size = 1 << $mini_sector_shift; // biasanya 64
        $num_fat_sectors = $this->_read_u4($data, 44);
        $dir_start_sector = $this->_read_u4($data, 48);
        $mini_cutoff = $this->_read_u4($data, 56);   // biasanya 4096
        $minifat_start = $this->_read_u4($data, 60);
        $num_minifat_sectors = $this->_read_u4($data, 64);
        $difat_start = $this->_read_u4($data, 68);
        $num_difat_sectors = $this->_read_u4($data, 72);

        // --- Kumpulkan DIFAT (lokasi sektor-sektor FAT) ---
        $difat = array();
        for ($i = 0; $i < 109; $i++) {
            $val = $this->_read_u4($data, 76 + ($i * 4));
            if ($val === 0xFFFFFFFE || $val === 0xFFFFFFFF) {
                break;
            }
            $difat[] = $val;
        }
        $sector = $difat_start;
        $safety = 0;
        while ($sector !== 0xFFFFFFFE && $sector !== 0xFFFFFFFF && $safety < 100000) {
            $sector_data = $this->_get_sector($data, $sector, $sector_size);
            $entries_per_sector = intdiv($sector_size, 4);
            for ($i = 0; $i < $entries_per_sector - 1; $i++) {
                $val = $this->_read_u4($sector_data, $i * 4);
                if ($val === 0xFFFFFFFE || $val === 0xFFFFFFFF) {
                    break 2;
                }
                $difat[] = $val;
            }
            $sector = $this->_read_u4($sector_data, ($entries_per_sector - 1) * 4);
            $safety++;
        }

        // --- Bangun tabel FAT penuh dari sektor-sektor yang ditunjuk DIFAT ---
        $fat = array();
        $entries_per_sector = intdiv($sector_size, 4);
        foreach ($difat as $fat_sector_id) {
            $sector_data = $this->_get_sector($data, $fat_sector_id, $sector_size);
            for ($i = 0; $i < $entries_per_sector; $i++) {
                $fat[] = $this->_read_u4($sector_data, $i * 4);
            }
        }

        // --- Susuri rantai sektor direktori ---
        $dir_bytes = $this->_read_chain($data, $fat, $dir_start_sector, $sector_size);

        // --- Cari entry "Workbook" atau "Book" di direktori ---
        $entries_count = intdiv(strlen($dir_bytes), 128);
        $root_start_sector = null;
        $root_stream_size = 0;
        $target_entry = null;

        for ($i = 0; $i < $entries_count; $i++) {
            $entry = substr($dir_bytes, $i * 128, 128);
            $name_len_bytes = $this->_read_u2($entry, 64);
            if ($name_len_bytes <= 0) {
                continue;
            }
            $raw_name = substr($entry, 0, max(0, $name_len_bytes - 2));
            $name = @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw_name);
            $object_type = ord($entry[66]);
            $start_sector = $this->_read_u4($entry, 116);
            $stream_size = $this->_read_u4($entry, 120); // 32-bit cukup untuk ukuran file .xls wajar

            if ($object_type === 5) { // root storage
                $root_start_sector = $start_sector;
                $root_stream_size = $stream_size;
            }
            if ($name === 'Workbook' || $name === 'Book') {
                $target_entry = array(
                    'start_sector' => $start_sector,
                    'size' => $stream_size,
                );
            }
        }

        if ($target_entry === null) {
            throw new Exception('Stream "Workbook" tidak ditemukan di dalam file .xls ini. File mungkin rusak, terenkripsi, atau format sangat lama (BIFF5) yang tidak didukung. Coba buka di Excel lalu simpan ulang sebagai .xlsx.');
        }

        // --- Stream kecil disimpan di "mini stream", stream besar di FAT biasa ---
        if ($target_entry['size'] < $mini_cutoff && $root_start_sector !== null) {
            $mini_fat_bytes = $this->_read_chain($data, $fat, $minifat_start, $sector_size);
            $mini_fat = array();
            $count = intdiv(strlen($mini_fat_bytes), 4);
            for ($i = 0; $i < $count; $i++) {
                $mini_fat[] = $this->_read_u4($mini_fat_bytes, $i * 4);
            }

            $root_stream_bytes = $this->_read_chain($data, $fat, $root_start_sector, $sector_size);
            $stream = $this->_read_mini_chain($root_stream_bytes, $mini_fat, $target_entry['start_sector'], $mini_sector_size);
        } else {
            $stream = $this->_read_chain($data, $fat, $target_entry['start_sector'], $sector_size);
        }

        $stream = substr($stream, 0, $target_entry['size']);
        $this->_stream_cache[$filepath] = $stream;

        return $stream;
    }

    private function _get_sector($data, $sector_id, $sector_size)
    {
        $offset = 512 + ($sector_id * $sector_size); // 512 = ukuran header, selalu tetap
        return substr($data, $offset, $sector_size);
    }

    private function _read_chain($data, array $fat, $start_sector, $sector_size)
    {
        $out = '';
        $sector = $start_sector;
        $safety = 0;
        while ($sector !== 0xFFFFFFFE && $sector < count($fat) && $safety < 200000) {
            $out .= $this->_get_sector($data, $sector, $sector_size);
            $next = isset($fat[$sector]) ? $fat[$sector] : 0xFFFFFFFE;
            if ($next === $sector) {
                break; // proteksi anti infinite-loop kalau file korup
            }
            $sector = $next;
            $safety++;
        }
        return $out;
    }

    private function _read_mini_chain($root_stream_bytes, array $mini_fat, $start_sector, $mini_sector_size)
    {
        $out = '';
        $sector = $start_sector;
        $safety = 0;
        while ($sector !== 0xFFFFFFFE && $safety < 200000) {
            $offset = $sector * $mini_sector_size;
            $out .= substr($root_stream_bytes, $offset, $mini_sector_size);
            $next = isset($mini_fat[$sector]) ? $mini_fat[$sector] : 0xFFFFFFFE;
            if ($next === $sector) {
                break;
            }
            $sector = $next;
            $safety++;
        }
        return $out;
    }

    private function _read_u2($data, $offset)
    {
        return unpack('v', substr($data, $offset, 2))[1];
    }

    private function _read_u4($data, $offset)
    {
        return unpack('V', substr($data, $offset, 4))[1];
    }

    // ------------------------------------------------------------------
    // TAHAP 2: parser record BIFF8
    // ------------------------------------------------------------------

    /**
     * Parse "Globals substream" (bagian awal stream Workbook) untuk ambil
     * shared strings (SST) dan daftar sheet + posisi awal (offset) tiap sheet.
     */
    private function _parse_globals($stream)
    {
        $shared_strings = array();
        $boundsheets = array();

        $pos = 0;
        $len = strlen($stream);
        $pending_continue_buffer = null; // untuk record panjang (SST) yang dipecah CONTINUE

        while ($pos + 4 <= $len) {
            $type = $this->_read_u2($stream, $pos);
            $size = $this->_read_u2($stream, $pos + 2);
            $payload = substr($stream, $pos + 4, $size);
            $pos += 4 + $size;

            if ($type === 0x00FC) { // SST (Shared String Table)
                // gabungkan dulu dengan CONTINUE record berikutnya kalau ada,
                // karena daftar string bisa terpotong record kalau kepanjangan.
                list($full_payload, $pos) = $this->_absorb_continue($stream, $pos, $payload, $len);
                $shared_strings = $this->_parse_sst($full_payload);
            } elseif ($type === 0x0085) { // BOUNDSHEET
                $offset = $this->_read_u4($payload, 0);
                $name_len = ord($payload[6]);
                $flags = ord($payload[7]);
                if ($flags & 0x01) { // unicode (UTF-16LE)
                    $raw = substr($payload, 8, $name_len * 2);
                    $name = @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw);
                } else {
                    $name = substr($payload, 8, $name_len);
                }
                $boundsheets[] = array('name' => $name, 'offset' => $offset);
            } elseif ($type === 0x000A) { // EOF Globals substream
                break;
            }
        }

        return array('shared_strings' => $shared_strings, 'boundsheets' => $boundsheets);
    }

    /**
     * Kalau record SST kepanjangan, Excel memecahnya jadi record CONTINUE
     * (0x003C) berikutnya. Gabungkan semua sampai ketemu record lain.
     */
    private function _absorb_continue($stream, $pos, $payload, $len)
    {
        while ($pos + 4 <= $len) {
            $peek_type = $this->_read_u2($stream, $pos);
            if ($peek_type !== 0x003C) {
                break;
            }
            $peek_size = $this->_read_u2($stream, $pos + 2);
            $payload .= substr($stream, $pos + 4, $peek_size);
            $pos += 4 + $peek_size;
        }
        return array($payload, $pos);
    }

    private function _parse_sst($payload)
    {
        // header SST: total occurrences(4) + unique count(4)
        $unique_count = $this->_read_u4($payload, 4);
        $offset = 8;
        $strings = array();
        $len = strlen($payload);

        for ($i = 0; $i < $unique_count && $offset < $len; $i++) {
            if ($offset + 3 > $len) {
                break;
            }
            $char_count = $this->_read_u2($payload, $offset);
            $flags = ord($payload[$offset + 2]);
            $is_unicode = ($flags & 0x01) !== 0;
            $offset += 3;

            // rich-text / extended-string flags diabaikan isinya (jarang perlu untuk data tabel)
            $has_rich = ($flags & 0x08) !== 0;
            $has_ext = ($flags & 0x04) !== 0;
            $rich_count = 0;
            $ext_size = 0;
            if ($has_rich) {
                $rich_count = $this->_read_u2($payload, $offset);
                $offset += 2;
            }
            if ($has_ext) {
                $ext_size = $this->_read_u4($payload, $offset);
                $offset += 4;
            }

            $byte_len = $is_unicode ? $char_count * 2 : $char_count;
            $raw = substr($payload, $offset, $byte_len);
            $offset += $byte_len;

            $strings[] = $is_unicode
                ? (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw)
                : $raw;

            if ($has_rich) {
                $offset += $rich_count * 4;
            }
            if ($has_ext) {
                $offset += $ext_size;
            }
        }

        return $strings;
    }

    /**
     * Baca data sel mulai dari offset BOF sheet tertentu sampai ketemu EOF-nya.
     */
    private function _parse_sheet_rows($stream, $start_offset, array $shared_strings)
    {
        $rows_by_index = array();
        $pos = $start_offset;
        $len = strlen($stream);
        $last_formula_cell = null; // untuk tangkap record STRING setelah FORMULA

        while ($pos + 4 <= $len) {
            $type = $this->_read_u2($stream, $pos);
            $size = $this->_read_u2($stream, $pos + 2);
            $payload = substr($stream, $pos + 4, $size);
            $pos += 4 + $size;

            switch ($type) {
                case 0x0203: // NUMBER
                    $row = $this->_read_u2($payload, 0);
                    $col = $this->_read_u2($payload, 2);
                    $value = unpack('d', substr($payload, 6, 8))[1];
                    $rows_by_index[$row][$col] = $this->_num_to_str($value);
                    break;

                case 0x027E: // RK
                    $row = $this->_read_u2($payload, 0);
                    $col = $this->_read_u2($payload, 2);
                    $value = $this->_rk_to_number($this->_read_u4($payload, 6));
                    $rows_by_index[$row][$col] = $this->_num_to_str($value);
                    break;

                case 0x00BD: // MULRK (beberapa RK sekaligus untuk 1 baris)
                    $row = $this->_read_u2($payload, 0);
                    $first_col = $this->_read_u2($payload, 2);
                    $plen = strlen($payload);
                    $last_col = $this->_read_u2($payload, $plen - 2);
                    $p = 4;
                    $col = $first_col;
                    while ($col <= $last_col && $p + 6 <= $plen - 2) {
                        $rk = $this->_read_u4($payload, $p + 2);
                        $rows_by_index[$row][$col] = $this->_num_to_str($this->_rk_to_number($rk));
                        $p += 6;
                        $col++;
                    }
                    break;

                case 0x00FD: // LABELSST
                    $row = $this->_read_u2($payload, 0);
                    $col = $this->_read_u2($payload, 2);
                    $sst_index = $this->_read_u4($payload, 6);
                    $rows_by_index[$row][$col] = isset($shared_strings[$sst_index]) ? $shared_strings[$sst_index] : '';
                    break;

                case 0x0204: // LABEL (string literal langsung, bukan lewat SST)
                    $row = $this->_read_u2($payload, 0);
                    $col = $this->_read_u2($payload, 2);
                    $char_count = $this->_read_u2($payload, 6);
                    $flags = ord($payload[8]);
                    $raw = substr($payload, 9, ($flags & 0x01) ? $char_count * 2 : $char_count);
                    $rows_by_index[$row][$col] = ($flags & 0x01)
                        ? (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw)
                        : $raw;
                    break;

                case 0x0006: // FORMULA (ambil nilai cache hasilnya, bukan rumusnya)
                    $row = $this->_read_u2($payload, 0);
                    $col = $this->_read_u2($payload, 2);
                    $result_bytes = substr($payload, 6, 8);
                    if (substr($result_bytes, 6, 2) === "\xFF\xFF") {
                        $sub_type = ord($result_bytes[0]);
                        if ($sub_type === 1) { // boolean
                            $rows_by_index[$row][$col] = ord($result_bytes[2]) ? 'TRUE' : 'FALSE';
                        } elseif ($sub_type === 3) { // string kosong
                            $rows_by_index[$row][$col] = '';
                        } else {
                            // sub_type 0 = hasil string (nilainya menyusul di record STRING)
                            $last_formula_cell = array($row, $col);
                        }
                    } else {
                        $value = unpack('d', $result_bytes)[1];
                        $rows_by_index[$row][$col] = $this->_num_to_str($value);
                    }
                    break;

                case 0x0207: // STRING (hasil cache dari FORMULA sebelumnya)
                    if ($last_formula_cell !== null) {
                        $char_count = $this->_read_u2($payload, 0);
                        $flags = ord($payload[2]);
                        $raw = substr($payload, 3, ($flags & 0x01) ? $char_count * 2 : $char_count);
                        $value = ($flags & 0x01)
                            ? (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw)
                            : $raw;
                        list($row, $col) = $last_formula_cell;
                        $rows_by_index[$row][$col] = $value;
                        $last_formula_cell = null;
                    }
                    break;

                case 0x003C: // CONTINUE lepas (mis. lanjutan SST di luar globals) - abaikan aman
                    break;

                case 0x000A: // EOF sheet ini
                    break 2;

                default:
                    // record lain (formatting, style, dsb) tidak relevan untuk ekstraksi data tabel
                    break;
            }
        }

        // rapikan jadi array berurutan dengan key huruf kolom, konsisten dgn Xlsx_reader
        $rows = array();
        ksort($rows_by_index);
        foreach ($rows_by_index as $cols) {
            $row_data = array();
            ksort($cols);
            foreach ($cols as $col_index => $value) {
                $row_data[$this->_index_to_letter($col_index)] = trim((string) $value);
            }
            $rows[] = $row_data;
        }

        return $rows;
    }

    /**
     * Decode nilai 4-byte "RK" ala BIFF: bisa berupa integer 30-bit atau
     * IEEE754 double yang dipotong, dengan opsi dibagi 100.
     */
    private function _rk_to_number($rk_raw)
    {
        $is_int = ($rk_raw & 0x02) !== 0;
        $div100 = ($rk_raw & 0x01) !== 0;
        $masked = $rk_raw & 0xFFFFFFFC;

        if ($is_int) {
            $signed = unpack('l', pack('V', $masked))[1]; // reinterpret sbg signed 32-bit
            $value = $signed >> 2; // arithmetic shift, PHP int cukup besar untuk ini
        } else {
            $bytes = pack('V', 0) . pack('V', $masked); // taruh di 32-bit tertinggi double
            $value = unpack('d', $bytes)[1];
        }

        return $div100 ? $value / 100 : $value;
    }

    private function _num_to_str($value)
    {
        if ($value == (int) $value && abs($value) < PHP_INT_MAX) {
            return (string) (int) $value;
        }
        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
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

    /**
     * Konversi serial tanggal Excel (dipakai sama-sama dengan Xlsx_reader
     * lewat interface yang sama, supaya kode di model/controller tidak perlu
     * cek "ini file xls atau xlsx" saat memproses tanggal).
     */
    public function excel_value_to_date($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_numeric($value)) {
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
