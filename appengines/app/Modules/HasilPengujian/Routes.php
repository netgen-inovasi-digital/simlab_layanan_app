<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('hasilpengujian', ['namespace' => 'Modules\HasilPengujian\Controllers'], function ($subroutes) {

    // Default halaman index
    $subroutes->get('/', 'HasilPengujian::index');

    // Data list untuk datatable (dipanggil di JS: site_url("hasilpengujian/datalist"))
    $subroutes->get('datalist', 'HasilPengujian::dataList');
    $subroutes->post('datalist', 'HasilPengujian::dataList');

    // Detail list (dipanggil di JS: site_url("hasilpengujian/detaillist/") + id)
    $subroutes->get('detaillist/(:any)', 'HasilPengujian::detailList/$1');
    $subroutes->post('detaillist/(:any)', 'HasilPengujian::detailList/$1');

    // Untuk submit data LHUS (aksi "kirim" yang Anda sebut submit)
    $subroutes->post('submit', 'HasilPengujian::submit');

    // Untuk upload file LHUS (aksi upload saja)
    $subroutes->post('upload', 'HasilPengujian::upload');

    // Untuk edit data (form submit)
    $subroutes->post('edit', 'HasilPengujian::edit');

    // Untuk hapus data (dipanggil JS: site_url("hasilpengujian/delete/") + id)
    $subroutes->post('delete/(:any)', 'HasilPengujian::delete/$1');

    // Untuk approve (jika ada fitur approve terpisah, JS memanggil site_url("hasilpengujian/approve/") + id)
    $subroutes->post('approve/(:any)', 'HasilPengujian::approve/$1');

    // Untuk method dinamis fallback (jaga di akhir, jangan pindahkan sebelum route spesifik di atas)
    $subroutes->get('(:any)', 'HasilPengujian::$1');
});
