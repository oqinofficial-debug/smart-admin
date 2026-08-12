<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'auth';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

$route['auth']          = 'auth/index';
$route['auth/login']    = 'auth/login';
$route['auth/logout']   = 'auth/logout';

$route['dashboard']     = 'dashboard/index';

// nanti setelah User.php dibuat:
// $route['user']        = 'user/index';

// Import Data Laporan Produksi (Excel) + manajemen alias kolom
$route['import']                     = 'import/index';
$route['import/preview']             = 'import/preview';
$route['import/select-sheet']        = 'import/select_sheet_confirm';
$route['import/process']             = 'import/process';
$route['import/alias']               = 'import_alias/index';
$route['import/alias/edit/(:num)']   = 'import_alias/edit/$1';
$route['import/alias/add']           = 'import_alias/add_alias';
$route['import/alias/delete/(:num)'] = 'import_alias/delete_alias/$1';
$route['import/alias/sheet/add']           = 'import_alias/add_sheet_alias';
$route['import/alias/sheet/delete/(:num)'] = 'import_alias/delete_sheet_alias/$1';

// Production Monitoring Report
$route['monitoring-produksi']                       = 'monitoring_produksi/index';
$route['monitoring-produksi/detail/(:num)/(:any)']   = 'monitoring_produksi/detail/$1/$2';
$route['monitoring-produksi/search-wip']             = 'monitoring_produksi/search_wip';
$route['monitoring-produksi/search-raw']             = 'monitoring_produksi/search_raw';
$route['monitoring-produksi/search-fg']              = 'monitoring_produksi/search_fg';
$route['monitoring-produksi/pemakaian/add']          = 'monitoring_produksi/pemakaian_add';
$route['monitoring-produksi/pemakaian/delete/(:num)'] = 'monitoring_produksi/pemakaian_delete/$1';
$route['monitoring-produksi/realisasi/update']       = 'monitoring_produksi/realisasi_update';
$route['monitoring-produksi/status-output/set']      = 'monitoring_produksi/status_output_set';

// Delivery -- cantolan stok FG
$route['delivery/fg/search']            = 'delivery/fg_search';
$route['delivery/fg/list/(:num)']       = 'delivery/fg_list/$1';
$route['delivery/fg/add']               = 'delivery/fg_add';
$route['delivery/fg/delete/(:num)']     = 'delivery/fg_delete/$1';