<?php
if(!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('website', ['namespace' => 'Modules\Landing\Controllers'], function($subroutes){
    $subroutes->get('/', 'Landing::index');
});

$routes->group('landing', ['namespace' => 'Modules\Landing\Controllers'], function($subroutes){
    $subroutes->get('/', 'Landing::index');
    $subroutes->get('(:any)', 'Landing::$1');
});
