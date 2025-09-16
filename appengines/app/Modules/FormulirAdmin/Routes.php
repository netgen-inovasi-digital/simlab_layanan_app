<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formuliradmin', ['namespace' => 'Modules\FormulirAdmin\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'FormulirAdmin::index');
    $subroutes->get('datalist', 'FormulirAdmin::dataList');   // ✅ route eksplisit untuk datalist
    $subroutes->post('submit', 'FormulirAdmin::submit');
    $subroutes->post('edit', 'FormulirAdmin::edit');
    $subroutes->post('delete', 'FormulirAdmin::delete');
    $subroutes->post('upload', 'FormulirAdmin::upload');

    // approve status (ubah 1 -> 2)
    $subroutes->post('approve/(:any)', 'FormulirAdmin::approve/$1');
});
