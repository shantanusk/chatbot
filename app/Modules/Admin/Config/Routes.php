<?php

if (! isset($routes)) {
    $routes = service('routes');
}

$routes->group('admin', ['namespace' => 'Modules\Admin\Controllers', 'filter' => 'adminAuth'], static function ($routes) {
    $routes->get('/', 'Admin::index');
    $routes->get('settings', 'Admin::settings');
    $routes->post('settings/save', 'Admin::save');
});
