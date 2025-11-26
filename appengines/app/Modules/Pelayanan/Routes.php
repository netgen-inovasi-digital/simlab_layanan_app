<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelayanan', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pelayanan::index');
    $subroutes->get('datalist', 'Pelayanan::dataList');
    $subroutes->get('detailList/(:any)', 'Pelayanan::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'Pelayanan::getTrackingData/$1');
    $subroutes->get('getSampleIdentity/(:num)', 'Pelayanan::getSampleIdentity/$1');
    $subroutes->get('detail/(:any)', 'Pelayanan::detail/$1');
    $subroutes->get('checkVerified', 'Pelayanan::checkVerified');

    $subroutes->get('datalist2/(:any)', 'Pelayanan::dataList2/$1');
    $subroutes->get('kuesioner/(:any)', 'Pelayanan::kuesioner/$1');
    $subroutes->post('submit_kuesioner', 'Pelayanan::submit_kuesioner');
});

// ================================================================
// PELAYANAN RAPAT JAS ROUTES (kode_jenis = 'D')
// ================================================================
$routes->group('pelayananrapatjas', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'PelayananRapatJas::index');
    $subroutes->get('datalist', 'PelayananRapatJas::dataList');
    $subroutes->get('detailList/(:any)', 'PelayananRapatJas::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'PelayananRapatJas::getTrackingData/$1');
    $subroutes->get('detail/(:any)', 'PelayananRapatJas::detail/$1');
    $subroutes->get('checkVerified', 'PelayananRapatJas::checkVerified');

    $subroutes->get('datalist2/(:any)', 'PelayananRapatJas::dataList2/$1');
    $subroutes->get('kuesioner/(:any)', 'PelayananRapatJas::kuesioner/$1');
    $subroutes->post('submit_kuesioner', 'PelayananRapatJas::submit_kuesioner');
});

// Routes untuk Pelayanan Sewa Alat
$routes->group('pelayanan_alat', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'PelayananAlat::index');
    $subroutes->get('datalist', 'PelayananAlat::dataList');
    $subroutes->get('detailList/(:any)', 'PelayananAlat::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'PelayananAlat::getTrackingData/$1');
    $subroutes->get('checkVerified', 'PelayananAlat::checkVerified');
});

// Routes untuk Pelayanan Sewa Ruangan Lab
$routes->group('pelayanan_lab', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'PelayananLab::index');
    $subroutes->get('datalist', 'PelayananLab::dataList');
    $subroutes->get('detailList/(:any)', 'PelayananLab::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'PelayananLab::getTrackingData/$1');
    $subroutes->get('checkVerified', 'PelayananLab::checkVerified');
});
