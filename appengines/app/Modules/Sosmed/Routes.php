<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('sosmed', ['namespace' => 'Modules\Sosmed\Controllers'], function($subroutes){

    $subroutes->get('/', 'Sosmed::index');
    $subroutes->get('(:any)', 'Sosmed::$1');
    $subroutes->post('submit', 'Sosmed::submit');
    $subroutes->post('edit', 'Sosmed::edit');
    $subroutes->post('delete', 'Sosmed::delete');
    $subroutes->post('updated', 'Sosmed::updated');
    $subroutes->post('toggle', 'Sosmed::toggle');

});
