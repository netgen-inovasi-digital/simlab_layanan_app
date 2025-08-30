<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('persentase', ['namespace' => 'Modules\Persentase\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Persentase::index');
    $subroutes->get('datalist', 'Persentase::dataList'); 
    $subroutes->post('submit', 'Persentase::submit');
    $subroutes->get('edit/(:any)', 'Persentase::edit/$1');
    $subroutes->get('delete/(:any)', 'Persentase::delete/$1');
});
