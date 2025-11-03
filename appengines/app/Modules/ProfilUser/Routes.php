<?php

if (!isset($routes)) 
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('profiluser', ['namespace' => 'Modules\ProfilUser\Controllers'], function($subroutes) {
    $subroutes->get('/', 'ProfilUser::index');
    $subroutes->post('submit', 'ProfilUser::submit');
    $subroutes->post('edit', 'ProfilUser::edit');
    $subroutes->post('delete', 'ProfilUser::delete');
    $subroutes->get('(:any)', 'ProfilUser::$1');
});
