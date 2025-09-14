<?php

if (!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('admin', ['namespace' => 'Modules\Admin\Controllers'], function($subroutes) {

    $subroutes->get('/', 'Admin::index');
    $subroutes->get('(:any)', 'Admin::$1');
    $subroutes->post('submit', 'Admin::submit');
    $subroutes->post('edit', 'Admin::edit');
    $subroutes->post('delete', 'Admin::delete');

});
