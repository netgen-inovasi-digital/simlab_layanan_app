<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('hasilpengujian', ['namespace' => 'Modules\HasilPengujian\Controllers'], function ($subroutes) {

    // Default halaman index
    $subroutes->get('/', 'HasilPengujian::index');

    // Untuk method dinamis seperti detail atau lainnya
    $subroutes->get('(:any)', 'HasilPengujian::$1');

    // Untuk submit data LHUS
    $subroutes->post('submit', 'HasilPengujian::submit');

    // Untuk edit data
    $subroutes->post('edit', 'HasilPengujian::edit');

    // Untuk hapus data
    $subroutes->post('delete', 'HasilPengujian::delete');

    // Untuk upload file LHUS
    $subroutes->post('upload', 'HasilPengujian::upload');
});
