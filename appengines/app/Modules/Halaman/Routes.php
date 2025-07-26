<?php
if(!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('hal', ['namespace' => 'Modules\Halaman\Controllers'], function($subroutes){
    $subroutes->get('/', 'Halaman::index');

    // Route slug artikel (detail hal)
    $subroutes->get('(:segment)', 'Halaman::detail/$1');
});

