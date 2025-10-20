<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelayanan', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pelayanan::index');
    $subroutes->get('checkVerified', 'Pelayanan::checkVerified');
    $subroutes->get('datalist', 'Pelayanan::dataList');
    $subroutes->get('detailList/(:any)', 'Pelayanan::detailList/$1');
    $subroutes->get('detail/(:any)', 'Pelayanan::detail/$1');
    $subroutes->get('tambah', 'Pelayanan::tambah');
    $subroutes->post('simpan', 'Pelayanan::simpan');
});
