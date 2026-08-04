<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kontrak yang sama untuk semua reader (Xlsx_reader, Xls_reader, Csv_reader),
 * supaya controller & model tidak perlu tahu format file yang sedang diproses.
 *
 * Setiap baris dikembalikan sebagai associative array dengan KEY berupa
 * huruf kolom ala Excel: 'A', 'B', 'C', ... 'Z', 'AA', dst.
 * Ini konsisten dengan Xlsx_reader yang sudah ada di project, supaya mapping
 * kolom lewat mst_import_alias tidak perlu berubah sama sekali.
 */
interface File_reader_interface
{
    /**
     * @param string $filepath path file di server
     * @return array daftar sheet, tiap item: array('name' => string).
     *               Untuk csv/txt selalu balikin 1 "sheet" semu (mis. 'Sheet1')
     *               supaya alur di controller seragam untuk semua format.
     */
    public function list_sheets($filepath);

    /**
     * @param string      $filepath
     * @param string|null $sheet    nama sheet yang mau dibaca (null = sheet pertama)
     * @param array       $options  opsi tambahan per-format, mis. ['delimiter' => ';']
     * @return array daftar baris, tiap baris = array('A' => ..., 'B' => ..., ...)
     */
    public function read($filepath, $sheet = null, array $options = array());
}
