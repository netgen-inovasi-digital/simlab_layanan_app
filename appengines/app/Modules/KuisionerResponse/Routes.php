<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('response_kuisioner', ['namespace' => 'Modules\KuisionerResponse\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'KuisionerResponse::index');
    $subroutes->get('datalist', 'KuisionerResponse::dataList');
    $subroutes->get('detail/(:any)', 'KuisionerResponse::detail/$1');
});
