<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('motif', ['namespace' => 'Modules\Motif\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Motif::index');
    $subroutes->get('(:any)', 'Motif::$1');
    $subroutes->post('submit', 'Motif::submit');
    $subroutes->post('edit', 'Motif::edit');
    $subroutes->post('delete', 'Motif::delete');
    $subroutes->post('upload', 'Motif::upload');
});
