<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('tinjaulhus', ['namespace' => 'Modules\TinjauLHUS\Controllers'], function ($subroutes) {

    // 🔹 Halaman utama
    $subroutes->get('/', 'TinjauLHUS::index');

    // 🔹 Load data untuk datatable (GET)
    $subroutes->get('datalist', 'TinjauLHUS::dataList');

    // 🔹 Detail item layanan (GET dengan parameter ID terenkripsi)
    $subroutes->get('detaillist/(:any)', 'TinjauLHUS::detailList/$1');

    // 🔹 Proses LHUS (diterima / ditolak) → wajib POST
    // Format URL: tinjaulhus/proses/{id terenkripsi}/{aksi}
    // Contoh: tinjaulhus/proses/a1b2c3d4/terima
    $subroutes->post('proses/(:any)/(:any)', 'TinjauLHUS::proses/$1/$2');
});
