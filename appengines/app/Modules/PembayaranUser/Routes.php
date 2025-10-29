<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pembayaran_user', ['namespace' => 'Modules\PembayaranUser\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'PembayaranUser::index');
    $subroutes->get('dataList', 'PembayaranUser::dataList');
    $subroutes->post('uploadBukti', 'PembayaranUser::uploadBukti');
    $subroutes->post('kirimBukti', 'PembayaranUser::kirimBukti');
});
