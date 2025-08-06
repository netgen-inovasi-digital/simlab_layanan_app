<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('menu', ['namespace' => 'Modules\Menu\Controllers'], function($subroutes){

    $subroutes->get('/', 'Menu::index');
    $subroutes->get('(:any)', 'Menu::$1');
    $subroutes->post('submit', 'Menu::submit');
    $subroutes->post('edit', 'Menu::edit');
    $subroutes->post('delete', 'Menu::delete');
    $subroutes->post('updated', 'Menu::updated');

});
