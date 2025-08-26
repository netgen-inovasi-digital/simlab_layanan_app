<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('jenis', ['namespace' => 'Modules\Jenis\Controllers'], function ($subroutes) {
    
    $subroutes->get('/', 'Jenis::index');
    $subroutes->get('(:any)', 'Jenis::$1');
    $subroutes->post('submit', 'Jenis::submit');
    $subroutes->post('edit', 'Jenis::edit');
    $subroutes->post('delete', 'Jenis::delete');
});
