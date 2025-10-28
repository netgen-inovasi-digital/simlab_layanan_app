<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelayanan', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pelayanan::index');
    $subroutes->get('datalist', 'Pelayanan::dataList');
    $subroutes->get('detailList/(:any)', 'Pelayanan::detailList/$1');
    $subroutes->get('detail/(:any)', 'Pelayanan::detail/$1');
    $subroutes->get('checkVerified', 'Pelayanan::checkVerified');

    $subroutes->get('keranjang', 'Pelayanan::keranjang');                  
    $subroutes->get('keranjang/datalist', 'Pelayanan::keranjangDataList');   
    $subroutes->get('keranjang/dataListLayanan', 'Pelayanan::keranjangDataListLayanan');
    $subroutes->get('keranjang/form', 'Pelayanan::keranjangFormTambah');
    $subroutes->get('keranjang/kategoriList', 'Pelayanan::kategoriList');
    $subroutes->post('keranjang/submit', 'Pelayanan::keranjangSubmit');
    $subroutes->get('keranjang/delete/(:any)', 'Pelayanan::keranjangDelete/$1');
    $subroutes->post('keranjang/checkout', 'Pelayanan::keranjangCheckout');
});
