<?php

if (!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('akun', ['namespace' => 'Modules\Akun\Controllers'], function($subroutes) {

    $subroutes->get('/', 'Akun::index');
    $subroutes->get('datalist', 'Akun::dataList');  
    $subroutes->post('submit', 'Akun::submit');
    $subroutes->get('edit/(:any)', 'Akun::edit/$1'); // bawa id terenkripsi
    $subroutes->get('delete/(:any)', 'Akun::delete/$1'); // bawa id terenkripsi

});
