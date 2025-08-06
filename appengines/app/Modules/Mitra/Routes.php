<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('mitra', ['namespace' => 'Modules\Mitra\Controllers'], function($subroutes){

    $subroutes->get('/', 'Mitra::index');
    $subroutes->get('(:any)', 'Mitra::$1');
    $subroutes->post('submit', 'Mitra::submit');
    $subroutes->post('edit', 'Mitra::edit');
    $subroutes->post('delete', 'Mitra::delete');
    $subroutes->post('updated', 'Mitra::updated');
    $subroutes->post('toggle', 'Mitra::toggle');


});
