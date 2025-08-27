<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('persentase', ['namespace' => 'Modules\Persentase\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Persentase::index');
    $subroutes->get('datalist', 'Persentase::dataList'); 
    $subroutes->get('(:any)', 'Persentase::$1');
    $subroutes->post('submit', 'Persentase::submit');
    $subroutes->post('edit', 'Persentase::edit');
    $subroutes->post('delete', 'Persentase::delete');
});
