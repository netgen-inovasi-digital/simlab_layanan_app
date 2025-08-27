<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('parameter', ['namespace' => 'Modules\Parameter\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Parameter::index');
    $subroutes->get('(:any)', 'Parameter::$1');
    $subroutes->post('submit', 'Parameter::submit');
    $subroutes->post('edit', 'Parameter::edit');
    $subroutes->post('delete', 'Parameter::delete');
});

