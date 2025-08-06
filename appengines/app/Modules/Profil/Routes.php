<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('profil', ['namespace' => 'Modules\Profil\Controllers'], function($subroutes){

    $subroutes->get('/', 'Profil::index');
    $subroutes->get('(:any)', 'Profil::$1');
    $subroutes->post('submit', 'Profil::submit');
    $subroutes->post('edit', 'Profil::edit');
    $subroutes->post('delete', 'Profil::delete');

});
