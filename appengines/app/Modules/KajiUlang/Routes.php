<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('kajiulang', ['namespace' => 'Modules\KajiUlang\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'KajiUlang::index');
    $subroutes->get('datalist', 'KajiUlang::datalist');
    $subroutes->get('detailList/(:any)', 'KajiUlang::detailList/$1');
    $subroutes->get('getSampleIdentity/(:any)', 'KajiUlang::getSampleIdentity/$1');

    $subroutes->post('kirim', 'KajiUlang::kirim');
    $subroutes->post('approveDetail', 'KajiUlang::approveDetail');
    $subroutes->post('rejectDetail',  'KajiUlang::rejectDetail');
    $subroutes->post('savekomentar', 'KajiUlang::saveKomentar');
});
