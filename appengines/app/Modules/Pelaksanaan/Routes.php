<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelaksanaan', ['namespace' => 'Modules\Pelaksanaan\Controllers'], function ($subroutes) {

    // Default halaman index
    $subroutes->get('/', 'Pelaksanaan::index');

    // Endpoint data untuk table (dipanggil dari JS)
    $subroutes->get('datalist', 'Pelaksanaan::dataList');

    // Detail list untuk modal detail (dipanggil dari JS: pelaksanaan/detaillist/{id})
    $subroutes->get('detaillist/(:any)', 'Pelaksanaan::detailList/$1');

    // Upload file LHU/LHUS (POST)
    $subroutes->post('upload', 'Pelaksanaan::upload');
    $subroutes->post('delete/(:any)', 'Pelaksanaan::delete/$1');
    $subroutes->post('proses/(:any)', 'Pelaksanaan::proses/$1');
    $subroutes->get('(:any)', 'Pelaksanaan::$1');
});
