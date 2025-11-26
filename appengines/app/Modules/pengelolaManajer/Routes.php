<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pengelolaManajer', ['namespace' => 'Modules\pengelolaManajer\Controllers'], function($subroutes) {
    $subroutes->get('/', 'pengelolaManajer::index');
    $subroutes->get('datalist', 'pengelolaManajer::datalist');
    $subroutes->get('layanan/(:any)', 'pengelolaManajer::layanan/$1');
    $subroutes->get('layananKosong', 'pengelolaManajer::layananKosong');
    $subroutes->get('deleteLayanan/(:any)', 'pengelolaManajer::deleteLayanan/$1');

    // basic
    $subroutes->get('(:any)', 'pengelolaManajer::$1');
    $subroutes->post('submit', 'pengelolaManajer::submit');
    $subroutes->post('edit', 'pengelolaManajer::edit');
    $subroutes->post('delete', 'pengelolaManajer::delete');
    $subroutes->post('tambahLayananManajerteknis', 'pengelolaManajer::tambahLayananManajerteknis');
});
