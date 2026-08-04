<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pilih reader yang tepat berdasarkan format file, dan verifikasi isi file
 * benar-benar cocok dengan format yang diklaim (jangan cuma percaya
 * ekstensi -- user bisa saja rename file apa saja jadi .xlsx).
 */
class File_reader_factory
{
    const SUPPORTED_EXTENSIONS = array('xlsx', 'xls', 'csv', 'txt');

    /**
     * @param string $extension 'xlsx' | 'xls' | 'csv' | 'txt'
     * @return File_reader_interface
     */
    public static function make($extension)
    {
        $extension = strtolower(ltrim((string) $extension, '.'));
        switch ($extension) {
            case 'xlsx':
                return new Xlsx_reader();
            case 'xls':
                return new Xls_reader();
            case 'csv':
            case 'txt':
                return new Csv_reader();
            default:
                throw new Exception(
                    'Format file ".' . $extension . '" tidak didukung. ' .
                    'Format yang didukung: ' . implode(', ', array_map(function ($e) { return '.' . $e; }, self::SUPPORTED_EXTENSIONS))
                );
        }
    }

    /**
     * Deteksi format asli file dari isi byte pertamanya (magic bytes),
     * dipakai untuk memverifikasi klaim ekstensi upload.
     *
     * @return string 'xlsx' | 'xls' | 'text' (csv/txt tidak bisa dibedakan dari isinya)
     */
    public static function detect_real_format($filepath)
    {
        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            throw new Exception('Gagal membuka file untuk verifikasi format: ' . $filepath);
        }
        $head = fread($handle, 8);
        fclose($handle);

        if (substr($head, 0, 2) === 'PK') {
            return 'xlsx'; // ZIP signature (xlsx/xlsm/docx dll, tapi kita hanya expect xlsx di sini)
        }
        if (substr($head, 0, 8) === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
            return 'xls'; // OLE/Compound File signature
        }
        return 'text'; // dianggap teks biasa: csv/txt
    }

    /**
     * Cek apakah ekstensi yang diklaim user masuk akal dibanding isi file
     * sebenarnya. Melempar Exception dengan pesan jelas kalau tidak cocok.
     */
    public static function assert_extension_matches_content($filepath, $claimed_extension)
    {
        $claimed_extension = strtolower(ltrim((string) $claimed_extension, '.'));
        $real = self::detect_real_format($filepath);

        $ok = ($real === 'xlsx' && $claimed_extension === 'xlsx')
            || ($real === 'xls' && $claimed_extension === 'xls')
            || ($real === 'text' && in_array($claimed_extension, array('csv', 'txt'), true));

        if (!$ok) {
            throw new Exception(
                'Isi file tidak sesuai dengan ekstensi ".' . $claimed_extension . '". ' .
                'Pastikan file benar-benar hasil export/save-as dari Excel dengan format tersebut, ' .
                'bukan sekadar file yang di-rename ekstensinya.'
            );
        }
    }
}
