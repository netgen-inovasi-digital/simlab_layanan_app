<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

// ROUTES UNTUK KERANJANG
$routes->group('keranjang', ['namespace' => 'Modules\Keranjang\Controllers'], function ($subroutes) {

    // DEFAULT ROUTES (Backward Compatibility - defaults to 'pengujian')

    // Halaman utama keranjang (default: pengujian)
    $subroutes->get('/', 'Keranjang::index');

    // API endpoints untuk keranjang (default: pengujian)
    $subroutes->get('datalist', 'Keranjang::keranjangDataList');
    $subroutes->get('dataListLayanan', 'Keranjang::keranjangDataListLayanan');
    $subroutes->get('kategoriList', 'Keranjang::kategoriList');
    $subroutes->post('submit', 'Keranjang::keranjangSubmit');
    $subroutes->get('delete/(:any)', 'Keranjang::keranjangDelete/$1');
    $subroutes->post('checkout', 'Keranjang::keranjangCheckout');

    // Endpoints untuk tracking dan detail (tetap di Keranjang controller)
    $subroutes->get('detailList/(:any)', 'Keranjang::detailList/$1');
    $subroutes->get('getTrackingData/(:any)', 'Keranjang::getTrackingData/$1');
    $subroutes->get('detail/(:any)', 'Keranjang::detail/$1');

    // Kuesioner (tetap di Keranjang controller)
    $subroutes->get('kuesioner/(:any)', 'Keranjang::kuesioner/$1');
    $subroutes->post('submit_kuesioner', 'Keranjang::submit_kuesioner');

    // Verifikasi user (shared untuk semua jenis layanan)
    $subroutes->get('checkVerified', 'Keranjang::checkVerified');

    // ================================================================
    // SEWA ALAT ROUTES (New Service Type)
    // ================================================================

    $subroutes->group('sewa', function ($sewaRoutes) {
        // Halaman utama keranjang sewa
        $sewaRoutes->get('/', 'KeranjangSewa::index');

        // API endpoints untuk sewa
        $sewaRoutes->get('datalist', 'KeranjangSewa::keranjangDataList');
        $sewaRoutes->get('dataListLayanan', 'KeranjangSewa::keranjangDataListLayanan');
        $sewaRoutes->get('kategoriList', 'KeranjangSewa::kategoriList');
        $sewaRoutes->post('submit', 'KeranjangSewa::keranjangSubmit');
        $sewaRoutes->get('delete/(:any)', 'KeranjangSewa::keranjangDelete/$1');
        $sewaRoutes->post('checkout', 'KeranjangSewa::keranjangCheckout');

        // Verifikasi user
        $sewaRoutes->get('checkVerified', 'KeranjangSewa::checkVerified');
    });

    // ================================================================
    // SEWA ALAT ROUTES (Alternative naming - keranjang_alat)
    // ================================================================
    // Note: Ini sama dengan sewa, tapi menggunakan path terpisah untuk clarity
    
});

// ROUTES UNTUK KERANJANG ALAT (Separate group untuk clarity)
$routes->group('keranjang_alat', ['namespace' => 'Modules\Keranjang\Controllers'], function ($subroutes) {
    // Halaman utama keranjang alat
    $subroutes->get('/', 'KeranjangAlat::index');

    // API endpoints untuk keranjang alat
    $subroutes->get('datalist', 'KeranjangAlat::keranjangDataList');
    $subroutes->get('dataListLayanan', 'KeranjangAlat::keranjangDataListLayanan');
    $subroutes->get('kategoriList', 'KeranjangAlat::kategoriList');
    $subroutes->post('submit', 'KeranjangAlat::keranjangSubmit');
    $subroutes->get('delete/(:any)', 'KeranjangAlat::keranjangDelete/$1');
    $subroutes->post('checkout', 'KeranjangAlat::keranjangCheckout');

    // Verifikasi user
    $subroutes->get('checkVerified', 'KeranjangAlat::checkVerified');
    
});

/*
 * USAGE EXAMPLES:
 * 
 * Pengujian (Default - Backward Compatible):
 * - GET  /keranjang                        → Keranjang::index()
 * - GET  /keranjang/dataListLayanan        → Keranjang::keranjangDataListLayanan()
 * - POST /keranjang/submit                 → Keranjang::keranjangSubmit()
 * 
 * Sewa Alat (New):
 * - GET  /keranjang/sewa                   → KeranjangSewa::index()
 * - GET  /keranjang/sewa/dataListLayanan   → KeranjangSewa::keranjangDataListLayanan()
 * - POST /keranjang/sewa/submit            → KeranjangSewa::keranjangSubmit()
 * 
 * Konsultasi (Future):
 * - GET  /keranjang/konsultasi             → KeranjangKonsultasi::index()
 * - GET  /keranjang/konsultasi/dataListLayanan → KeranjangKonsultasi::keranjangDataListLayanan()
 * - POST /keranjang/konsultasi/submit      → KeranjangKonsultasi::keranjangSubmit()
 */
