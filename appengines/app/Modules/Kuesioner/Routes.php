<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('kuesioner', ['namespace' => 'Modules\Kuesioner\Controllers'], function ($subroutes) {
    /**
     * @var \CodeIgniter\Router\RouteCollection $subroutes
     */
    $subroutes->get('/', 'Kuesioner::index');
    $subroutes->get('datalist', 'Kuesioner::dataList');
    $subroutes->post('submit', 'Kuesioner::submit');
    $subroutes->get('edit/(:any)', 'Kuesioner::edit/$1');
    $subroutes->get('delete/(:any)', 'Kuesioner::delete/$1');
});