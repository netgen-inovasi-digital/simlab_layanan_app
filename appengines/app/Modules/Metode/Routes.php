<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('metode', ['namespace' => 'Modules\Metode\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Metode::index');
    $subroutes->get('(:any)', 'Metode::$1');
    $subroutes->post('submit', 'Metode::submit');
    $subroutes->post('edit', 'Metode::edit');
    $subroutes->post('delete', 'Metode::delete');
});
