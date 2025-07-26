<?php
if(!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('home', ['namespace' => 'Modules\Dashboard\Controllers'], function($subroutes){
    $subroutes->get('/', 'Dashboard::index');
});

$routes->group('dashboard', ['namespace' => 'Modules\Dashboard\Controllers'], function($subroutes){
    $subroutes->get('/', 'Dashboard::index');
    $subroutes->get('(:any)', 'Dashboard::$1');
});
