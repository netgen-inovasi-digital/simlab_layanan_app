<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pengelolaManajer', ['namespace' => 'Modules\pengelolaManajer\Controllers'], function($subroutes) {
    $subroutes->get('/', 'Manajerteknis::index');
    $subroutes->get('datalist', 'Manajerteknis::datalist');
    $subroutes->get('layanan/(:any)', 'Manajerteknis::layanan/$1');
    $subroutes->get('layananKosong', 'Manajerteknis::layananKosong');
    $subroutes->get('deleteLayanan/(:any)', 'Manajerteknis::deleteLayanan/$1');

    // basic
    $subroutes->get('(:any)', 'Manajerteknis::$1');
    $subroutes->post('submit', 'Manajerteknis::submit');
    $subroutes->post('edit', 'Manajerteknis::edit');
    $subroutes->post('delete', 'Manajerteknis::delete');
    $subroutes->post('tambahLayananManajerteknis', 'Manajerteknis::tambahLayananManajerteknis');
});
