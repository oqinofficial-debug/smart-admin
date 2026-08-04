<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Konversi nilai tanggal/waktu dari file import, terlepas dari format
 * sumbernya (xlsx/xls menyimpan tanggal sebagai angka serial; csv/txt
 * biasanya sudah berupa teks tanggal langsung). Dipusatkan di sini supaya
 * controller tidak perlu tahu/peduli reader mana yang sedang dipakai.
 */
class Value_converter
{
    /**
     * @return string|null format Y-m-d, atau null kalau tidak bisa di-parse
     */
    public function to_date($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        // angka murni -> kemungkinan besar serial number Excel (xlsx/xls)
        if (is_numeric($value) && (float) $value > 0 && (float) $value < 100000) {
            $unix_timestamp = ((int) $value - 25569) * 86400;
            return gmdate('Y-m-d', $unix_timestamp);
        }

        // teks tanggal (umum untuk csv/txt, atau kolom xlsx bertipe teks) --
        // coba beberapa format eksplisit dulu (lebih aman daripada strtotime
        // polos yang bisa salah tafsir dd/mm vs mm/dd), baru fallback strtotime.
        $explicit_formats = array('d/m/Y', 'd-m-Y', 'Y-m-d', 'Y/m/d', 'd/m/y', 'd-m-y');
        foreach ($explicit_formats as $format) {
            $parsed = DateTime::createFromFormat($format, trim($value));
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }

    /**
     * @return string|null format H:i:s
     */
    public function to_time($value)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        // pecahan hari ala Excel (mis. 0.5 = 12:00:00)
        if (is_numeric($value) && (float) $value >= 0 && (float) $value < 1) {
            $seconds = round((float) $value * 86400);
            return gmdate('H:i:s', $seconds);
        }

        // teks jam biasa: "08:00", "08:00:00", "8.00"
        $normalized = str_replace('.', ':', trim($value));
        if (preg_match('/^([01]?[0-9]|2[0-3]):([0-5][0-9])(:([0-5][0-9]))?$/', $normalized, $m)) {
            $h = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $s = isset($m[4]) ? $m[4] : '00';
            return "$h:$m[2]:$s";
        }

        return null;
    }

    /**
     * @param string $periode format YYYY-MM
     * @return bool
     */
    public function is_valid_periode($periode)
    {
        return (bool) preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $periode);
    }
}
