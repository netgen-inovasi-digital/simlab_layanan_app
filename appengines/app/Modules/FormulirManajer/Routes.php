<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formulirmanajer', ['namespace' => 'Modules\FormulirManajer\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'FormulirManajer::index');
    $subroutes->get('datalist', 'FormulirManajer::datalist');
    $subroutes->get('detailList/(:any)', 'FormulirManajer::detailList/$1');

    $subroutes->post('kirim', 'FormulirManajer::kirim');
    $subroutes->post('approveDetail', 'FormulirManajer::approveDetail');
    $subroutes->post('rejectDetail',  'FormulirManajer::rejectDetail');
});
