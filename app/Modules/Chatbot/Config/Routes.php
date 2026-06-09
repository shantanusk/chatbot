<?php

if (! isset($routes)) {
    $routes = service('routes');
}

$routes->group('chatbot', ['namespace' => 'Modules\Chatbot\Controllers'], static function ($routes) {
    $routes->get('/', 'Chatbot::index');
    $routes->post('send', 'Chatbot::send');
});
