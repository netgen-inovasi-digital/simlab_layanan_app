<?php
if(!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('berita', ['namespace' => 'Modules\Berita\Controllers'], function($subroutes){
    $subroutes->get('/', 'Berita::index');

    // Route kategori (lebih spesifik)
    $subroutes->get('kategori/(:segment)', 'Berita::kategori/$1');

    // Route slug artikel (detail berita)
    $subroutes->get('(:segment)', 'Berita::detail/$1');
});

