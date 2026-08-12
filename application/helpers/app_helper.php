<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('format_tanggal_indo')) {
    /**
     * Format tanggal ke Bahasa Indonesia, contoh: 08 Juli 2026
     * @param string $date format Y-m-d atau Y-m-d H:i:s
     */
    function format_tanggal_indo($date, $with_time = FALSE)
    {
        if (empty($date)) return '-';

        $bulan = array(
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        );

        $timestamp = strtotime($date);
        $hasil = date('d', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

        if ($with_time) {
            $hasil .= ' ' . date('H:i', $timestamp);
        }

        return $hasil;
    }
}

if (!function_exists('format_rupiah')) {
    /**
     * Format angka ke Rupiah, contoh: Rp 1.500.000
     */
    function format_rupiah($angka, $with_prefix = TRUE)
    {
        $hasil = number_format((float) $angka, 0, ',', '.');
        return $with_prefix ? 'Rp ' . $hasil : $hasil;
    }
}

if (!function_exists('role_label')) {
    /**
     * Ubah level (1/2/3) jadi label yang enak dibaca
     */
    function role_label($level)
    {
        switch ((int) $level) {
            case ROLE_MASTER:  return 'Master';
            case ROLE_INPUTER: return 'Inputer';
            case ROLE_VIEWER:
            default:           return 'Viewer';
        }
    }
}

if (!function_exists('role_badge_class')) {
    /**
     * Class CSS untuk badge role (dipakai di view, tanpa dependency framework berat)
     */
    function role_badge_class($level)
    {
        switch ((int) $level) {
            case ROLE_MASTER:  return 'badge badge-master';
            case ROLE_INPUTER: return 'badge badge-inputer';
            case ROLE_VIEWER:
            default:           return 'badge badge-viewer';
        }
    }
}

if (!function_exists('parse_bulk_paste')) {
    /**
     * Parse teks hasil copy-paste (biasanya dari Excel/Sheets) jadi array
     * baris, tiap baris berupa array kolom mentah (belum divalidasi).
     * Delimiter kolom dideteksi otomatis dari SELURUH teks (bukan per
     * baris) supaya konsisten walau ada baris yang cuma 1 kolom:
     *   - TAB kalau ada (bawaan copy-paste dari Excel/Google Sheets)
     *   - kalau tidak ada TAB, coba ';'
     *   - kalau tidak ada juga, pakai ','
     * Baris kosong (setelah trim) dilewati. Dipakai fitur "Tambah Massal"
     * di MasterData, Karyawan, dan Jf.
     */
    function parse_bulk_paste($text)
    {
        $text = str_replace(array("\r\n", "\r"), "\n", (string) $text);
        $lines = explode("\n", $text);

        if (strpos($text, "\t") !== false) {
            $delim = "\t";
        } elseif (strpos($text, ';') !== false) {
            $delim = ';';
        } else {
            $delim = ',';
        }

        $rows = array();
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = array_map('trim', explode($delim, $line));
        }
        return $rows;
    }
}

if (!function_exists('parse_flexible_bool')) {
    /**
     * Konversi teks bebas (kolom "Aktif" hasil copy-paste) jadi boolean.
     * Kosong -> $default. Dikenali TRUE: 1, y, ya, yes, true, aktif, active
     * (case-insensitive). Selain itu (termasuk 0/tidak/nonaktif/no) -> FALSE.
     */
    function parse_flexible_bool($raw, $default = true)
    {
        $raw = strtolower(trim((string) $raw));
        if ($raw === '') {
            return $default;
        }
        return in_array($raw, array('1', 'y', 'ya', 'yes', 'true', 'aktif', 'active'), true);
    }
}

if (!function_exists('normalize_bool')) {
    /**
     * Normalisasi boolean dari PostgreSQL ('t'/'f' string) ke boolean PHP asli.
     * Driver CI3 + PostgreSQL mengembalikan 't'/'f' untuk kolom boolean, bukan
     * true/false PHP asli — selalu pakai ini kalau menyentuh kolom boolean
     * (is_active, is_public, is_primary, can_view, can_input, dst).
     *
     * Dipakai Department_model. Sebaiknya juga dipakai ulang di Menu_model
     * untuk menggantikan Menu_model::_bool() supaya tidak duplikat logic.
     */
    function normalize_bool($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        return $value === 't' || $value === true || $value === '1' || $value === 1;
    }
}
