<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('tinjaulhus', ['namespace' => 'Modules\TinjauLHUS\Controllers'], function ($subroutes) {

    $subroutes->get('/', 'TinjauLHUS::index');
    $subroutes->get('datalist', 'TinjauLHUS::dataList');
    $subroutes->get('detaillist/(:any)', 'TinjauLHUS::detailList/$1');

    // route untuk aksi parent (sudah benar)
    $subroutes->post('proses/(:any)/(:any)', 'TinjauLHUS::proses/$1/$2');

    // <-- perbaiki kedua route berikut: jangan duplikasi "tinjaulhus/"
    $subroutes->post('savedetketlhus', 'TinjauLHUS::saveDetKetLhus');
    $subroutes->post('prosesdetaillhus', 'TinjauLHUS::prosesDetailLhus');

});
