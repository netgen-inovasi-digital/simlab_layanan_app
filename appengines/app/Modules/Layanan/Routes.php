<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('layanan', ['namespace' => 'Modules\Layanan\Controllers'], function($subroutes){

    $subroutes->get('/', 'Layanan::index');
    $subroutes->get('(:any)', 'Layanan::$1');
    $subroutes->post('submit', 'Layanan::submit');
    $subroutes->post('edit', 'Layanan::edit');
    $subroutes->post('delete', 'Layanan::delete');
    $subroutes->post('updated', 'Layanan::updated');
    $subroutes->post('toggle', 'Layanan::toggle');


});
