<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('kategoriLayanan', ['namespace' => 'Modules\kategoriLayanan\Controllers'], function ($subroutes) {
    
    $subroutes->get('/', 'kategoriLayanan::index');
    $subroutes->get('(:any)', 'kategoriLayanan::$1');
    $subroutes->post('submit', 'kategoriLayanan::submit');
    $subroutes->post('edit', 'kategoriLayanan::edit');
    $subroutes->post('delete', 'kategoriLayanan::delete');
});
