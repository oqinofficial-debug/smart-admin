<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Identitas aplikasi
|--------------------------------------------------------------------------
*/
if (!defined('APP_NAME'))       define('APP_NAME', 'Smart Admin');
if (!defined('APP_SHORT_NAME')) define('APP_SHORT_NAME', 'SmartAdmin');
if (!defined('APP_VERSION'))    define('APP_VERSION', '0.1.0');

/*
|--------------------------------------------------------------------------
| Level / Role user
|--------------------------------------------------------------------------
| 1 = Viewer  : hanya bisa lihat
| 2 = Inputer : bisa lihat + input data
| 3 = Master  : bisa lihat + input + edit + hapus
*/
if (!defined('ROLE_VIEWER'))  define('ROLE_VIEWER', 1);
if (!defined('ROLE_INPUTER')) define('ROLE_INPUTER', 2);
if (!defined('ROLE_MASTER'))  define('ROLE_MASTER', 3);

/*
|--------------------------------------------------------------------------
| Path upload (untuk modul import excel/csv/txt nanti)
|--------------------------------------------------------------------------
| Pastikan folder ini ada dan writable.
*/
if (!defined('UPLOAD_PATH'))      define('UPLOAD_PATH', FCPATH . 'uploads/');
if (!defined('UPLOAD_TEMP_PATH')) define('UPLOAD_TEMP_PATH', FCPATH . 'uploads/temp/');

/*
|--------------------------------------------------------------------------
| Sesi login
|--------------------------------------------------------------------------
*/
if (!defined('SESSION_LOGIN_KEY'))        define('SESSION_LOGIN_KEY', 'sa_logged_in');
if (!defined('SESSION_TIMEOUT_MINUTES'))  define('SESSION_TIMEOUT_MINUTES', 120); // auto logout kalau idle

/*
|--------------------------------------------------------------------------
| Wajib ada untuk CodeIgniter
|--------------------------------------------------------------------------
| File config CI3 harus mengandung $config array, walau kosong.
| Ini juga membuat file ini aman kalau ke-load lebih dari sekali.
*/
$config = array();
