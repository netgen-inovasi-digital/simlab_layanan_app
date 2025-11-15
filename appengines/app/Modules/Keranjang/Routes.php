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
    $subroutes->get('metodeList', 'Keranjang::getMetodeList');
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
    // RAPAT JAS ROUTES (kode_jenis = 'D')
    // ================================================================

    $subroutes->group('rapatjas', function ($rapatjasRoutes) {
        // Halaman utama keranjang rapat jas
        $rapatjasRoutes->get('/', 'KeranjangRapatJas::index');

        // API endpoints untuk rapat jas
        $rapatjasRoutes->get('datalist', 'KeranjangRapatJas::keranjangDataList');
        $rapatjasRoutes->get('dataListLayanan', 'KeranjangRapatJas::keranjangDataListLayanan');
        $rapatjasRoutes->get('kategoriList', 'KeranjangRapatJas::kategoriList');
        $rapatjasRoutes->post('submit', 'KeranjangRapatJas::keranjangSubmit');
        $rapatjasRoutes->get('delete/(:any)', 'KeranjangRapatJas::keranjangDelete/$1');
        $rapatjasRoutes->post('checkout', 'KeranjangRapatJas::keranjangCheckout');

        // Verifikasi user
        $rapatjasRoutes->get('checkVerified', 'KeranjangRapatJas::checkVerified');
    });

    // ================================================================
    // RAPAT JAS ROUTES (kode_jenis = 'D')
    // ================================================================

    $subroutes->group('rapatjas', function ($rapatjasRoutes) {
        // Halaman utama keranjang rapat jas
        $rapatjasRoutes->get('/', 'KeranjangRapatJas::index');

        // API endpoints untuk rapat jas
        $rapatjasRoutes->get('datalist', 'KeranjangRapatJas::keranjangDataList');
        $rapatjasRoutes->get('dataListLayanan', 'KeranjangRapatJas::keranjangDataListLayanan');
        $rapatjasRoutes->get('kategoriList', 'KeranjangRapatJas::kategoriList');
        $rapatjasRoutes->post('submit', 'KeranjangRapatJas::keranjangSubmit');
        $rapatjasRoutes->get('delete/(:any)', 'KeranjangRapatJas::keranjangDelete/$1');
        $rapatjasRoutes->post('checkout', 'KeranjangRapatJas::keranjangCheckout');

        // Verifikasi user
        $rapatjasRoutes->get('checkVerified', 'KeranjangRapatJas::checkVerified');
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

// ROUTES UNTUK KERANJANG LAB (Separate group untuk sewa ruangan lab)
$routes->group('keranjang_lab', ['namespace' => 'Modules\Keranjang\Controllers'], function ($subroutes) {
    // Halaman utama keranjang lab
    $subroutes->get('/', 'KeranjangLab::index');

    // API endpoints untuk keranjang lab
    $subroutes->get('datalist', 'KeranjangLab::keranjangDataList');
    $subroutes->get('dataListLayanan', 'KeranjangLab::keranjangDataListLayanan');
    $subroutes->get('kategoriList', 'KeranjangLab::kategoriList');
    $subroutes->post('submit', 'KeranjangLab::keranjangSubmit');
    $subroutes->get('delete/(:any)', 'KeranjangLab::keranjangDelete/$1');
    $subroutes->post('checkout', 'KeranjangLab::keranjangCheckout');

    // Verifikasi user
    $subroutes->get('checkVerified', 'KeranjangLab::checkVerified');

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
 * Rapat JAS (New - Filter kode_jenis = 'D'):
 * - GET  /keranjang/rapatjas               → KeranjangRapatJas::index()
 * - GET  /keranjang/rapatjas/dataListLayanan → KeranjangRapatJas::keranjangDataListLayanan()
 * - POST /keranjang/rapatjas/submit        → KeranjangRapatJas::keranjangSubmit()
 * 
 * Konsultasi (Future):
 * - GET  /keranjang/konsultasi             → KeranjangKonsultasi::index()
 * - GET  /keranjang/konsultasi/dataListLayanan → KeranjangKonsultasi::keranjangDataListLayanan()
 * - POST /keranjang/konsultasi/submit      → KeranjangKonsultasi::keranjangSubmit()
 */
