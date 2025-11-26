<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('fileumum', ['namespace' => 'Modules\FileUmum\Controllers'], function($subroutes) {

    $subroutes->get('/', 'FileUmum::index');
    $subroutes->get('(:any)', 'FileUmum::$1');
    $subroutes->post('submit', 'FileUmum::submit');
    $subroutes->post('edit', 'FileUmum::edit');
    $subroutes->post('datalist', 'FileUmum::dataList');

});
