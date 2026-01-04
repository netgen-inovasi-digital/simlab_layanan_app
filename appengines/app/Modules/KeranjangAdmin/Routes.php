<?php

if (!isset($routes)) {
  $routes = \Config\Services::routes(true);
}

// ROUTES UNTUK KERANJANG ADMIN
$routes->group('keranjangadmin', ['namespace' => 'Modules\KeranjangAdmin\Controllers'], function ($subroutes) {

  // DEFAULT ROUTES (Backward Compatibility - defaults to 'pengujian')

  // Halaman utama keranjang (default: pengujian)
  $subroutes->get('/', 'Keranjang::index');

  // API endpoints untuk keranjang (default: pengujian)
  $subroutes->get('datalist', 'Keranjang::keranjangDataList');
  $subroutes->get('dataListLayanan', 'Keranjang::keranjangDataListLayanan');
  $subroutes->get('kategoriList', 'Keranjang::kategoriList');
  $subroutes->post('submit', 'Keranjang::keranjangSubmit');
  $subroutes->match(['GET', 'POST'], 'delete/(:any)', 'Keranjang::keranjangDelete/$1');  // Support both GET and POST
  $subroutes->post('checkout', 'Keranjang::keranjangCheckout');
  $subroutes->post('setPelanggan', 'Keranjang::keranjangSetPelanggan'); // Admin feature
  $subroutes->get('getPelanggan', 'Keranjang::keranjangGetPelanggan'); // Get pelanggan from session

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
