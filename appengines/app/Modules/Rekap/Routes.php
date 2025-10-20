<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('rekap', ['namespace' => 'Modules\Rekap\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'Rekap::index');
    $subroutes->get('datalist', 'Rekap::dataList');
    $subroutes->get('download', 'Rekap::download');
});
