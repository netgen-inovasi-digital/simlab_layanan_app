<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formuliradmin', ['namespace' => 'Modules\FormulirAdmin\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'FormulirAdmin::index', ['as' => 'formuliradmin.index']);
    $subroutes->get('datalist', 'FormulirAdmin::datalist', ['as' => 'formuliradmin.datalist']);
    $subroutes->get('detaillist/(:any)', 'FormulirAdmin::detaillist/$1', ['as' => 'formuliradmin.detaillist']);
    $subroutes->post('submit', 'FormulirAdmin::submit', ['as' => 'formuliradmin.submit']);
    $subroutes->post('delete/(:any)', 'FormulirAdmin::delete/$1', ['as' => 'formuliradmin.delete']);
    $subroutes->post('upload', 'FormulirAdmin::upload', ['as' => 'formuliradmin.upload']);
    $subroutes->post('approve/(:any)', 'FormulirAdmin::approve/$1', ['as' => 'formuliradmin.approve']);
   
    $subroutes->get('keranjang/delete/(:any)', 'FormulirAdmin::keranjangDelete/$1');
    $subroutes->get('keranjangDataListLayanan', 'FormulirAdmin::keranjangDataListLayanan', ['as' => 'formuliradmin.keranjangDataListLayanan']);
    $subroutes->get('keranjangDatalist', 'FormulirAdmin::keranjangDatalist', ['as' => 'formuliradmin.keranjangDatalist']);
    $subroutes->post('keranjangSubmit', 'FormulirAdmin::keranjangSubmit', ['as' => 'formuliradmin.keranjangSubmit']);
    $subroutes->post('keranjangDelete/(:any)', 'FormulirAdmin::keranjangDelete/$1', ['as' => 'formuliradmin.keranjangDelete']);
    $subroutes->post('keranjangCheckout', 'FormulirAdmin::keranjangCheckout', ['as' => 'formuliradmin.keranjangCheckout']);
    $subroutes->post('keranjangSetPelanggan', 'FormulirAdmin::keranjangSetPelanggan', ['as' => 'formuliradmin.keranjangSetPelanggan']);
    $subroutes->get('checkVerified', 'FormulirAdmin::checkVerified', ['as' => 'formuliradmin.checkVerified']);
    $subroutes->get('kategoriList', 'FormulirAdmin::kategoriList', ['as' => 'formuliradmin.kategoriList']);
});

// Group routing untuk Rapat JAS (khusus kode_jenis = 'D')
$routes->group('formuliradminrapatjas', ['namespace' => 'Modules\FormulirAdmin\Controllers'], function ($subroutes) {
    $subroutes->get('/', 'FormulirAdminRapatJas::index', ['as' => 'formuliradminrapatjas.index']);
    $subroutes->get('datalist', 'FormulirAdminRapatJas::datalist', ['as' => 'formuliradminrapatjas.datalist']);
    $subroutes->get('detaillist/(:any)', 'FormulirAdminRapatJas::detaillist/$1', ['as' => 'formuliradminrapatjas.detaillist']);
    $subroutes->post('submit', 'FormulirAdminRapatJas::submit', ['as' => 'formuliradminrapatjas.submit']);
    $subroutes->post('delete/(:any)', 'FormulirAdminRapatJas::delete/$1', ['as' => 'formuliradminrapatjas.delete']);
    $subroutes->post('approve/(:any)', 'FormulirAdminRapatJas::approve/$1', ['as' => 'formuliradminrapatjas.approve']);
});
