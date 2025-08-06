<?php
if(!isset($routes)) { 
    $routes = \Config\Services::routes(true);
}

$routes->group('cart', ['namespace' => 'Modules\Cart\Controllers'], function($subroutes){
    // Route untuk Cart
    $subroutes->get('/', 'Cart::index');
    $subroutes->get('getProducts', 'Cart::getProducts');
});

