<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('layout', ['namespace' => 'Modules\Layout\Controllers'], function($subroutes){

    $subroutes->get('/', 'Layout::index');
    $subroutes->get('(:any)', 'Layout::$1');
    $subroutes->post('submit', 'Layout::submit');
    $subroutes->post('edit', 'Layout::edit');
    $subroutes->post('delete', 'Layout::delete');
    $subroutes->post('updated', 'Layout::updated');
    $subroutes->post('toggle', 'Layout::toggle');


});
