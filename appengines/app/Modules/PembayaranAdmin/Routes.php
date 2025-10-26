<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pembayaran_admin', ['namespace' => 'Modules\PembayaranAdmin\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'PembayaranAdmin::index');
    $subroutes->get('dataList', 'PembayaranAdmin::dataList');
    $subroutes->post('uploadBukti', 'PembayaranAdmin::uploadBukti');
    $subroutes->post('terimaVerifikasi', 'PembayaranAdmin::terimaVerifikasi');
    $subroutes->post('tolakVerifikasi', 'PembayaranAdmin::tolakVerifikasi');
});
