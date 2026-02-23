<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Website::index');
// login route user
$routes->get('login', 'Auth::index');
$routes->post('login/auth', 'Auth::act');
//Login routes admin
$routes->post('login/admin', 'Auth::actAdmin');

// register route user
$routes->get('register', 'Auth::register');
$routes->post('register/auth', 'Auth::actRegister');

// forgot route
$routes->get('forgot', 'Auth::forgot');
$routes->post('forgot/auth', 'Auth::sendReset');

// reset route
$routes->get('reset/(:any)', 'Auth::resetPassword/$1');
$routes->post('reset/auth', 'Auth::sendPassword');

$routes->get('logout', 'Auth::logout');
$routes->get('password', 'Password::index');
$routes->post('password/submit', 'Password::submit');

// Captcha verification route
$routes->post('captcha/verify', 'Captcha::verify');

/**
 * --------------------------------------------------------------------
 * HMVC Routing
 * --------------------------------------------------------------------
 */
foreach (glob(APPPATH . 'Modules/*', GLOB_ONLYDIR) as $item_dir) {
    $moduleName = basename($item_dir);

    if (file_exists($item_dir . '/Routes.php')) {
        if (in_array($moduleName, ['Dashboard', 'Hero', 'Pelayanan', 'Konfigurasi', 'Layanan', 'Menu', 'Mitra', 'Navbar', 'Team', 'Landing',  'Otoritas', 'Pages', 'Pengumuman', 'Role', 'Sosmed', 'User', 'Order', 'Produk', 'Rekening', 'Motif', 'Ukuran', 'Warna', 'Tagihan', 'Pembayaran', 'Riwayat_Pembayaran', 'Pelaksanaan', 'Parameter', 'Metode'])) {
            // Beri filter auth hanya untuk module admin
            $routes->group('', ['filter' => 'auth'], static function ($routes) use ($item_dir) {
                require_once $item_dir . '/Routes.php';
            });
        } else {
            // Public module, tidak diberi auth
            require_once $item_dir . '/Routes.php';
        }
    }
}
