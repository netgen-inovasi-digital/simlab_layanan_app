<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('lab', ['namespace' => 'Modules\Lab\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Lab::index');

    $subroutes->get('datalist', 'Lab::dataList');
    $subroutes->post('submit', 'Lab::submit');
    $subroutes->get('edit/(:any)', 'Lab::edit/$1');
    $subroutes->get('delete/(:any)', 'Lab::delete/$1');
    $subroutes->get('getoptions', 'Lab::getoptions'); 

    $subroutes->post('update_diskon', 'Lab::update_diskon');
});
