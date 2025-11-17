<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('tinjaulhus', ['namespace' => 'Modules\TinjauLHUS\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'TinjauLHUS::index');
    $subroutes->get('datalist', 'TinjauLHUS::dataList');
    $subroutes->get('detaillist/(:any)', 'TinjauLHUS::detailList/$1');
    $subroutes->get('getSampleIdentity/(:any)', 'TinjauLHUS::getSampleIdentity/$1');
    $subroutes->post('proses/(:any)/(:any)', 'TinjauLHUS::proses/$1/$2');
    $subroutes->post('savedetketlhus', 'TinjauLHUS::saveDetKetLhus');
    $subroutes->post('prosesdetaillhus', 'TinjauLHUS::prosesDetailLhus');

});
