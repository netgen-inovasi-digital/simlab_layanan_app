<?php

if(!isset($routes))
{ 
    $routes = \Config\Services::routes(true);
}

$routes->group('team', ['namespace' => 'Modules\Team\Controllers'], function($subroutes){

    $subroutes->get('/', 'Team::index');
    $subroutes->get('(:any)', 'Team::$1');
    $subroutes->post('submit', 'Team::submit');
    $subroutes->post('edit', 'Team::edit');
    $subroutes->post('delete', 'Team::delete');
    $subroutes->post('updated', 'Team::updated');
    $subroutes->post('toggle', 'Team::toggle');


});
