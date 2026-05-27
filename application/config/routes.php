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
$route['404_override'] = 'errors/page_missing';
$route['translate_uri_dashes'] = FALSE;

$route['login'] = 'auth/login';
$route['register'] = 'auth/register';
$route['logout'] = 'auth/logout';

$route['dashboard'] = 'dashboard/index';

$route['projects/create'] = 'projects/create';
$route['projects/delete_project'] = 'projects/delete_project';
$route['projects/editor/(:num)'] = 'projects/editor/$1';
$route['projects/(:any)'] = 'projects/show/$1';
$route['project-files/get-file-content'] = 'ProjectFiles/get_file_content';
$route['project-files/save-file'] = 'ProjectFiles/save_file';
$route['project-files/create-file'] = 'ProjectFiles/create_file';
$route['project-files/create-folder'] = 'ProjectFiles/create_folder';
$route['project-files/rename-file'] = 'ProjectFiles/rename_file';
$route['project-files/delete-file'] = 'ProjectFiles/delete_file';
$route['project-files/upload-file'] = 'ProjectFiles/upload_file';
$route['project-preview/(:num)'] = 'ProjectPreview/index/$1';
$route['project-preview/(:num)/(.*)'] = 'ProjectPreview/index/$1/$2';
$route['project-publish/(:num)'] = 'ProjectPublish/publish/$1';
$route['project-publish/savedraft'] = 'ProjectPublish/savedraft';

$route['profile'] = 'profile/index';
$route['profile/password'] = 'profile/password';

$route['site/(:any)/(:any)'] = 'site/view/$1/$2';
$route['site/(:any)/(:any)/(.*)'] = 'site/view/$1/$2/$3';
