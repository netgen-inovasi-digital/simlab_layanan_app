<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('navbar', ['namespace' => 'Modules\Navbar\Controllers'], function($subroutes){

    $subroutes->get('/', 'Navbar::index');
    $subroutes->get('(:any)', 'Navbar::$1');
    $subroutes->post('submit', 'Navbar::submit');
    $subroutes->post('edit', 'Navbar::edit');
    $subroutes->post('delete', 'Navbar::delete');
    $subroutes->post('updated', 'Navbar::updated');
    $subroutes->post('toggle', 'Navbar::toggle');

});
