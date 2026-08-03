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
