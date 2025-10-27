<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('keranjang', ['namespace' => 'Modules\Keranjang\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'Keranjang::index');
    $subroutes->get('datalist', 'Keranjang::dataList');
    $subroutes->get('dataListLayanan', 'Keranjang::dataListLayanan'); // Route baru untuk modal
    $subroutes->get('formtambah', 'Keranjang::formTambah');
    $subroutes->get('checkout', 'Keranjang::checkout');
    $subroutes->post('submit', 'Keranjang::submit');
    $subroutes->post('checkout', 'Keranjang::checkout');
    $subroutes->get('delete/(:num)', 'Keranjang::delete/$1');
});