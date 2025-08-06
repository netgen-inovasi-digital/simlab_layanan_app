<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('posts', ['namespace' => 'Modules\Posts\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Posts::index');
    $subroutes->get('(:any)', 'Posts::$1');
    $subroutes->post('submit', 'Posts::submit');
    $subroutes->post('edit', 'Posts::edit');
    $subroutes->post('delete', 'Posts::delete');
    $subroutes->post('upload', 'Posts::upload');
});
