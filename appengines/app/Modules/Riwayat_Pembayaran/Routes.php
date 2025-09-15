<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('riwayat_pembayaran', ['namespace' => 'Modules\Riwayat_Pembayaran\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'Riwayat_Pembayaran::index');
    $subroutes->get('datalist', 'Riwayat_Pembayaran::dataList');
    $subroutes->post('filter', 'Riwayat_Pembayaran::filterByDate');
    $subroutes->get('(:any)', 'Riwayat_Pembayaran::$1');
    $subroutes->post('submit', 'Riwayat_Pembayaran::submit');
    $subroutes->post('edit', 'Riwayat_Pembayaran::edit');
    $subroutes->post('delete', 'Riwayat_Pembayaran::delete');
});
