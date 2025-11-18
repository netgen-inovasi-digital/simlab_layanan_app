<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pengelolaPenyelia', ['namespace' => 'Modules\pengelolaPenyelia\Controllers'], function($subroutes) {
    $subroutes->get('/', 'pengelolaPenyelia::index');
    $subroutes->get('datalist', 'pengelolaPenyelia::datalist');
    $subroutes->get('layanan/(:any)', 'pengelolaPenyelia::layanan/$1');
    $subroutes->get('layananKosong', 'pengelolaPenyelia::layananKosong');
    $subroutes->get('deleteLayanan/(:any)', 'pengelolaPenyelia::deleteLayanan/$1');

    // basic
    $subroutes->get('(:any)', 'pengelolaPenyelia::$1');
    $subroutes->post('submit', 'pengelolaPenyelia::submit');
    $subroutes->post('edit', 'pengelolaPenyelia::edit');
    $subroutes->post('delete', 'pengelolaPenyelia::delete');
    $subroutes->post('tambahLayananPenyelia', 'pengelolaPenyelia::tambahLayananPenyelia');
});
