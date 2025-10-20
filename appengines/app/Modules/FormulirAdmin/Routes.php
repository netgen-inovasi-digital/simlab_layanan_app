<?php

if (!isset($routes)) {
    $routes = \Config\Services::routes(true);
}

$routes->group('formuliradmin', ['namespace' => 'Modules\FormulirAdmin\Controllers'], function ($subroutes) {

$subroutes->get('/', 'FormulirAdmin::index');
$subroutes->get('datalist', 'FormulirAdmin::dataList');
$subroutes->get('detaillist/(:any)', 'FormulirAdmin::detailList/$1');
$subroutes->post('submit', 'FormulirAdmin::submit');
$subroutes->post('delete/(:any)', 'FormulirAdmin::delete/$1');
$subroutes->post('upload', 'FormulirAdmin::upload');
$subroutes->post('approve/(:any)', 'FormulirAdmin::approve/$1');

});
