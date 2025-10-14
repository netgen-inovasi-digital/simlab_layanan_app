<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formulirmanajer', ['namespace' => 'Modules\FormulirManajer\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'FormulirManajer::index');
    $subroutes->get('datalist', 'FormulirManajer::datalist');
    $subroutes->get('detailList/(:any)', 'FormulirManajer::detailList/$1');
    $subroutes->get('detaillist/(:any)', 'FormulirManajer::detailList/$1');
    $subroutes->post('submit', 'FormulirManajer::submit');
    $subroutes->post('edit', 'FormulirManajer::formulirManajerEdit');
    $subroutes->post('upload', 'FormulirManajer::formulirManajerUpload');
    $subroutes->post('delete/(:any)', 'FormulirManajer::delete/$1');
    $subroutes->post('approve/(:any)', 'FormulirManajer::approve/$1');

    // ===== TAMBAHAN: endpoints untuk aksi pada baris detail =====
    $subroutes->post('approveDetail', 'FormulirManajer::approveDetail');
    $subroutes->post('rejectDetail',  'FormulirManajer::rejectDetail');
    // ===== END TAMBAHAN =====
});
