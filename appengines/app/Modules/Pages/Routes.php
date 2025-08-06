<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pages', ['namespace' => 'Modules\Pages\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pages::index');
    $subroutes->get('(:any)', 'Pages::$1');
    $subroutes->post('submit', 'Pages::submit');
    $subroutes->post('edit', 'Pages::edit');
    $subroutes->post('delete', 'Pages::delete');
    $subroutes->post('upload', 'Pages::upload');
});
