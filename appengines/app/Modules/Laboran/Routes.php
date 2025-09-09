<?php

if (!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('laboran', ['namespace' => 'Modules\Laboran\Controllers'], function($subroutes) {

    $subroutes->get('/', 'Laboran::index');
    $subroutes->get('(:any)', 'Laboran::$1');
    $subroutes->post('submit', 'Laboran::submit');
    $subroutes->post('edit', 'Laboran::edit');
    $subroutes->post('delete', 'Laboran::delete');

});
