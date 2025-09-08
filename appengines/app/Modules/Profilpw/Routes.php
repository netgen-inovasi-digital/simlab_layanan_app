<?php

if (!isset($routes)) 
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('profilpw', ['namespace' => 'Modules\Profilpw\Controllers'], function($subroutes) {

    $subroutes->get('/', 'Profilpw::index');
    $subroutes->get('(:any)', 'Profilpw::$1');
    $subroutes->post('submit', 'Profilpw::submit');
    $subroutes->post('edit', 'Profilpw::edit');
    $subroutes->post('delete', 'Profilpw::delete');

});
