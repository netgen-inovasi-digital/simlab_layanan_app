<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('user', ['namespace' => 'Modules\User\Controllers'], function($subroutes){

    $subroutes->get('/', 'User::index');
    $subroutes->get('(:any)', 'User::$1');
    $subroutes->post('submit', 'User::submit');
    $subroutes->post('edit', 'User::edit');
    $subroutes->post('delete', 'User::delete');

});
