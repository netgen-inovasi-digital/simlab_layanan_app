<?php

if (!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('manajerteknis', ['namespace' => 'Modules\Manajerteknis\Controllers'], function($subroutes) {
    $subroutes->get('/', 'Manajerteknis::index');

    // spesifik
    $subroutes->get('layananKosong', 'Manajerteknis::layananKosong');
    $subroutes->get('layanan/(:any)', 'Manajerteknis::layanan/$1');
    $subroutes->get('deleteLayanan/(:any)', 'Manajerteknis::deleteLayanan/$1');

    // sisanya
    $subroutes->get('(:any)', 'Manajerteknis::$1');
    $subroutes->post('submit', 'Manajerteknis::submit');
    $subroutes->post('edit', 'Manajerteknis::edit');
    $subroutes->post('delete', 'Manajerteknis::delete');
});

