<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * TAMBAHKAN 1 baris ini ke application/config/routes.php yang sudah ada,
 * persis di bawah baris $route['import/preview'] yang sudah ada, supaya
 * URL-nya konsisten dengan pola yang sudah dipakai project ini:
 *
 *   $route['import']                    = 'import/index';
 *   $route['import/preview']            = 'import/preview';
 *   $route['import/select-sheet']       = 'import/select_sheet_confirm';   // <-- BARU
 *   $route['import/process']            = 'import/process';
 *   ...
 *
 * File ini sendiri TIDAK dipakai/di-load oleh CodeIgniter -- ini cuma
 * catatan potongan kode yang perlu ditempel manual ke routes.php asli,
 * supaya tidak menimpa routes.php yang sudah ada.
 */
