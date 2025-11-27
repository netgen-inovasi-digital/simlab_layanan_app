<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('hasilpengujian', ['namespace' => 'Modules\HasilPengujian\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'HasilPengujian::index');
    $subroutes->get('datalist', 'HasilPengujian::dataList');
    $subroutes->post('datalist', 'HasilPengujian::dataList');
    $subroutes->get('detailList/(:any)', 'HasilPengujian::detailList/$1');
    $subroutes->post('detailList/(:any)', 'HasilPengujian::detailList/$1');
    $subroutes->get('getSampleIdentity/(:any)', 'HasilPengujian::getSampleIdentity/$1');
    $subroutes->get('getCatatanKajiUlang/(:any)', 'HasilPengujian::getCatatanKajiUlang/$1');
    $subroutes->post('submit', 'HasilPengujian::submit');
    $subroutes->post('upload', 'HasilPengujian::upload');
    $subroutes->post('approve/(:any)', 'HasilPengujian::approve/$1');
    $subroutes->get('(:any)', 'HasilPengujian::$1');
});
