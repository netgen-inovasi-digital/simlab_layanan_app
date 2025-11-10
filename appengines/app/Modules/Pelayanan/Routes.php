<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelayanan', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'Pelayanan::index');
    $subroutes->get('datalist', 'Pelayanan::dataList');
    $subroutes->get('detailList/(:any)', 'Pelayanan::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'Pelayanan::getTrackingData/$1');
    $subroutes->get('detail/(:any)', 'Pelayanan::detail/$1');
    $subroutes->get('checkVerified', 'Pelayanan::checkVerified');

    $subroutes->get('datalist2/(:any)', 'Pelayanan::dataList2/$1');
    $subroutes->get('kuesioner/(:any)', 'Pelayanan::kuesioner/$1');
    $subroutes->post('submit_kuesioner', 'Pelayanan::submit_kuesioner');
});
