<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('tagihan', ['namespace' => 'Modules\Tagihan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Tagihan::index');
    $subroutes->get('dataList', 'Tagihan::dataList');
    $subroutes->post('upload', 'Tagihan::upload');
    $subroutes->post('proses', 'Tagihan::proses');
    $subroutes->post('delete', 'Tagihan::delete');
});
