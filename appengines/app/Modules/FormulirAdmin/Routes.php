<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formuliradmin', ['namespace' => 'Modules\FormulirAdmin\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'FormulirAdmin::index');
    $subroutes->get('datalist', 'FormulirAdmin::datalist');
    $subroutes->get('detaillist/(:any)', 'FormulirAdmin::detaillist/$1');
    $subroutes->post('submit', 'FormulirAdmin::submit');
    $subroutes->post('delete/(:any)', 'FormulirAdmin::delete/$1');
    $subroutes->post('upload', 'FormulirAdmin::upload');
    $subroutes->post('approve/(:any)', 'FormulirAdmin::approve/$1');

    $subroutes->get('keranjangDataListLayanan', 'FormulirAdmin::keranjangDataListLayanan');
    $subroutes->get('keranjangDatalist', 'FormulirAdmin::keranjangDatalist');
    $subroutes->post('keranjangSubmit', 'FormulirAdmin::keranjangSubmit');
    $subroutes->post('keranjangDelete/(:any)', 'FormulirAdmin::keranjangDelete/$1');
    $subroutes->post('keranjangCheckout', 'FormulirAdmin::keranjangCheckout');

    $subroutes->post('keranjangSetPelanggan', 'FormulirAdmin::keranjangSetPelanggan');
    $subroutes->get('checkVerified', 'FormulirAdmin::checkVerified');
});
