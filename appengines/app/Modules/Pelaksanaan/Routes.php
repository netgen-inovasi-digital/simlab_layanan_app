<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelaksanaan', ['namespace' => 'Modules\Pelaksanaan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pelaksanaan::index');
    $subroutes->get('datalist', 'Pelaksanaan::dataList');
    $subroutes->get('detaillist/(:any)', 'Pelaksanaan::detailList/$1');
    $subroutes->post('upload', 'Pelaksanaan::upload');
    $subroutes->post('delete/(:any)', 'Pelaksanaan::delete/$1');
    $subroutes->post('proses/(:any)', 'Pelaksanaan::proses/$1');
    $subroutes->get('(:any)', 'Pelaksanaan::$1');
});
