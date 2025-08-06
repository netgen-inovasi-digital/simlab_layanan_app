<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('hero', ['namespace' => 'Modules\Hero\Controllers'], function($subroutes){

    $subroutes->get('/', 'Hero::index');
    $subroutes->get('(:any)', 'Hero::$1');
    $subroutes->post('submit', 'Hero::submit');
    $subroutes->post('edit', 'Hero::edit');
    $subroutes->post('delete', 'Hero::delete');
    $subroutes->post('updated', 'Hero::updated');
    $subroutes->post('toggle', 'Hero::toggle');


});
