<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelaksanaan', ['namespace' => 'Modules\Pelaksanaan\Controllers'], function ($subroutes) {

    // Default halaman index
    $subroutes->get('/', 'Pelaksanaan::index');

    // Untuk method dinamis seperti detailList, dataList, dll
    $subroutes->get('(:any)', 'Pelaksanaan::$1');

    // Hapus data (pakai ID terenkripsi)
    $subroutes->post('delete/(:any)', 'Pelaksanaan::delete/$1');

    // Proses data (ubah status jadi LHU disetujui)
    $subroutes->post('proses/(:any)', 'Pelaksanaan::proses/$1');
});
