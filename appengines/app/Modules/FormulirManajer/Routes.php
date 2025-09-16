<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formulirmanajer', ['namespace' => 'Modules\FormulirManajer\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'FormulirManajer::index');
    $subroutes->get('datalist', 'FormulirManajer::formulirManajerDataList');
    $subroutes->get('detaillist/(:any)', 'FormulirManajer::formulirManajerDetailList/$1'); 

    $subroutes->post('submit', 'FormulirManajer::formulirManajerSubmit');
    $subroutes->post('edit', 'FormulirManajer::formulirManajerEdit');
    $subroutes->post('delete/(:any)', 'FormulirManajer::formulirManajerDelete/$1');
    $subroutes->post('upload', 'FormulirManajer::formulirManajerUpload');
    $subroutes->post('approve/(:any)', 'FormulirManajer::formulirManajerApprove/$1');
});

