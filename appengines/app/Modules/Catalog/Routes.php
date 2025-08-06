<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('catalog', ['namespace' => 'Modules\Catalog\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Catalog::index');
    $subroutes->get('(:any)', 'Catalog::$1');
    $subroutes->post('submit', 'Catalog::submit');
    $subroutes->post('edit', 'Catalog::edit');
    $subroutes->post('delete', 'Catalog::delete');
    $subroutes->post('upload', 'Catalog::upload');
});
