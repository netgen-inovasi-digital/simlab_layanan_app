<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('pelayanan', ['namespace' => 'Modules\Pelayanan\Controllers'], function ($subroutes) {

    // halaman utama (list pelayanan)
    $subroutes->get('/', 'Pelayanan::index');
    
    // ambil data list pelayanan (untuk datatable/ajax)
    $subroutes->get('datalist', 'Pelayanan::dataList');
    
    // detail halaman pelayanan
    $subroutes->get('detail/(:any)', 'Pelayanan::detail/$1');

    // detail list ajax (isi modal detail layanan)
    $subroutes->get('detaillist/(:any)', 'Pelayanan::detailList/$1');

    // nanti untuk tambah pesanan baru
    $subroutes->get('tambah', 'Pelayanan::tambah');
    $subroutes->post('simpan', 'Pelayanan::simpan');
});
