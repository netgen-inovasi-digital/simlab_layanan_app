<?php

if (!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('akun', ['namespace' => 'Modules\Akun\Controllers'], function($subroutes) {

    $subroutes->get('/', 'Akun::index');
    $subroutes->get('(:any)', 'Akun::$1');
    $subroutes->post('submit', 'Akun::submit');
    $subroutes->post('edit', 'Akun::edit');
    $subroutes->post('delete', 'Akun::delete');

});
