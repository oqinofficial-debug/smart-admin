<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Identitas aplikasi
|--------------------------------------------------------------------------
*/
define('APP_NAME', 'Smart Admin');
define('APP_SHORT_NAME', 'SmartAdmin');
define('APP_VERSION', '0.1.0');

/*
|--------------------------------------------------------------------------
| Level / Role user
|--------------------------------------------------------------------------
| 1 = Viewer  : hanya bisa lihat
| 2 = Inputer : bisa lihat + input data
| 3 = Master  : bisa lihat + input + edit + hapus
*/
define('ROLE_VIEWER', 1);
define('ROLE_INPUTER', 2);
define('ROLE_MASTER', 3);

/*
|--------------------------------------------------------------------------
| Path upload (untuk modul import excel/csv/txt nanti)
|--------------------------------------------------------------------------
| Pastikan folder ini ada dan writable.
*/
define('UPLOAD_PATH', FCPATH . 'uploads/');
define('UPLOAD_TEMP_PATH', FCPATH . 'uploads/temp/');

/*
|--------------------------------------------------------------------------
| Sesi login
|--------------------------------------------------------------------------
*/
define('SESSION_LOGIN_KEY', 'sa_logged_in');
define('SESSION_TIMEOUT_MINUTES', 120); // auto logout kalau idle, cocok utk lingkungan lawas
