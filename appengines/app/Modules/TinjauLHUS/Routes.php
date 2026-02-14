<?php

if (!isset($routes)) {
  $routes = \Config\Services::routes(true);
}

$routes->group('tinjaulhus', ['namespace' => 'Modules\TinjauLHUS\Controllers'], function ($subroutes) {

  $subroutes->get('/', 'TinjauLHUS::index');
  $subroutes->get('datalist', 'TinjauLHUS::dataList');
  $subroutes->get('detaillist/(:any)', 'TinjauLHUS::detailList/$1');
  $subroutes->get('getSampleIdentity/(:any)', 'TinjauLHUS::getSampleIdentity/$1');
  $subroutes->get('getCatatanKajiUlang/(:any)', 'TinjauLHUS::getCatatanKajiUlang/$1');
  $subroutes->post('submitreview', 'TinjauLHUS::submitReview');

});
