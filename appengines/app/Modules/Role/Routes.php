<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('role', ['namespace' => 'Modules\Role\Controllers'], function($subroutes){

    $subroutes->get('/', 'Role::index');
    $subroutes->get('(:any)', 'Role::$1');
    $subroutes->post('submit', 'Role::submit');
    $subroutes->post('edit', 'Role::edit');
    $subroutes->post('delete', 'Role::delete');

});
