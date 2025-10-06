<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('penyelia', ['namespace' => 'Modules\Penyelia\Controllers'], function($subroutes) {
    $subroutes->get('/', 'Penyelia::index');
    $subroutes->get('layanan/(:any)', 'Penyelia::layanan/$1');
    $subroutes->get('layananKosong', 'Penyelia::layananKosong');
    $subroutes->get('deleteLayanan/(:any)', 'Penyelia::deleteLayanan/$1');

    // basic
    $subroutes->get('(:any)', 'Penyelia::$1');
    $subroutes->post('submit', 'Penyelia::submit');
    $subroutes->post('edit', 'Penyelia::edit');
    $subroutes->post('delete', 'Penyelia::delete');
    $subroutes->post('tambahLayananPenyelia', 'Penyelia::tambahLayananPenyelia');
});
