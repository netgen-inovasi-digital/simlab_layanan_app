<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Website::index');

$routes->get('login', 'Auth::index');
$routes->post('login/auth', 'Auth::act');
$routes->get('logout', 'Auth::logout');
$routes->get('forgot', 'Auth::forgot');
$routes->post('forgot/auth', 'Auth::sendReset');
$routes->get('reset/(:any)', 'Auth::resetPassword/$1');
$routes->post('reset/auth', 'Auth::sendPassword');
$routes->get('password', 'Password::index');
$routes->post('password/submit', 'Password::submit');

/**
 * --------------------------------------------------------------------
 * HMVC Routing
 * --------------------------------------------------------------------
 */
foreach(glob(APPPATH . 'Modules/*', GLOB_ONLYDIR) as $item_dir) {
    $moduleName = basename($item_dir);

    if (file_exists($item_dir . '/Routes.php')) {
        if (in_array($moduleName, ['Categories', 'Dashboard', 'Hero', 'Konfigurasi', 'Layanan', 'Menu', 'Mitra', 'Navbar', 'Team', 'Landing',  'Otoritas', 'Pages', 'Pengumuman', 'Posts', 'Role', 'Sosmed', 'User'])) {
            // Beri filter auth hanya untuk module admin
            $routes->group('', ['filter' => 'auth'], static function($routes) use ($item_dir) {
                require_once $item_dir . '/Routes.php';
            });
        } else {
            // Public module, tidak diberi auth
            require_once $item_dir . '/Routes.php';
        }
    }
}

