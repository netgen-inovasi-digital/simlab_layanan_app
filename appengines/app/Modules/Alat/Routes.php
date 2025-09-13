<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('alat', ['namespace' => 'Modules\Alat\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Alat::index');
    $subroutes->get('(:any)', 'Alat::$1');
    $subroutes->post('submit', 'Alat::submit');
    $subroutes->post('edit', 'Alat::edit');
    $subroutes->post('delete', 'Alat::delete');
    $subroutes->post('upload', 'Alat::upload');
});
