<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('layananLab', ['namespace' => 'Modules\layananLab\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'layananLab::index');

    $subroutes->get('datalist', 'layananLab::dataList');
    $subroutes->post('submit', 'layananLab::submit');
    $subroutes->get('edit/(:any)', 'layananLab::edit/$1');
    $subroutes->get('delete/(:any)', 'layananLab::delete/$1');
    $subroutes->get('getTim/(:any)', 'layananLab::getTim/$1');
    $subroutes->get('getoptions', 'layananLab::getoptions'); 
    $subroutes->post('update_diskon', 'layananLab::update_diskon');
});
