<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pembayaran', ['namespace' => 'Modules\Pembayaran\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pembayaran::index');
    $subroutes->get('dataList', 'Pembayaran::dataList');
    $subroutes->post('verifikasi', 'Pembayaran::verifikasi');
});
