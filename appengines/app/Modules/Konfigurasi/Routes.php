<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('konfigurasi', ['namespace' => 'Modules\Konfigurasi\Controllers'], function($subroutes){

    $subroutes->get('/', 'Konfigurasi::index');
    $subroutes->get('(:any)', 'Konfigurasi::$1');
    $subroutes->post('submit', 'Konfigurasi::submit');
    $subroutes->post('edit', 'Konfigurasi::edit');
    $subroutes->post('delete', 'Konfigurasi::delete');

});
