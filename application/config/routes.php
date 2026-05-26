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

$route['login'] = 'auth/login';
$route['register'] = 'auth/register';
$route['logout'] = 'auth/logout';

$route['dashboard'] = 'dashboard/index';

$route['projects/create'] = 'projects/create';
$route['projects/delete_project'] = 'projects/delete_project';
$route['projects/editor/(:num)'] = 'projects/editor/$1';
$route['projects/get_file_content'] = 'projects/get_file_content';
$route['projects/save_file'] = 'projects/save_file';
$route['projects/create_file'] = 'projects/create_file';
$route['projects/create_folder'] = 'projects/create_folder';
$route['projects/rename_file'] = 'projects/rename_file';
$route['projects/delete_file'] = 'projects/delete_file';
$route['projects/upload_file'] = 'projects/upload_file';
$route['projects/preview/(:num)'] = 'projects/preview/$1';
$route['projects/preview/(:num)/(.*)'] = 'projects/preview/$1/$2';
$route['projects/publish/(:num)'] = 'projects/publish/$1';
$route['projects/(:any)'] = 'projects/show/$1';

$route['editor/(:num)'] = 'projects/editor/$1';
$route['editor/(:num)/file'] = 'projects/get_file_content';
$route['editor/(:num)/save'] = 'projects/save_file';
$route['editor/(:num)/create-file'] = 'projects/create_file';
$route['editor/(:num)/create-folder'] = 'projects/create_folder';
$route['editor/(:num)/rename'] = 'projects/rename_file';
$route['editor/(:num)/delete'] = 'projects/delete_file';
$route['editor/(:num)/upload'] = 'projects/upload_file';

$route['publish/(:num)'] = 'projects/publish/$1';

$route['profile'] = 'profile/index';
$route['profile/password'] = 'profile/password';

$route['preview/(:num)'] = 'site/preview/$1';
$route['preview/(:num)/(.*)'] = 'site/preview/$1/$2';
