<?php

if (! isset($routes)) {
    $routes = service('routes');
}

$routes->group('chatbot', ['namespace' => 'Modules\Chatbot\Controllers'], static function ($routes) {
    $routes->get('/', 'Chatbot::index');
    $routes->post('send', 'Chatbot::send');
    $routes->get('conversations', 'Chatbot::listConversations');
    $routes->post('new', 'Chatbot::newConversation');
    $routes->get('load/(:num)', 'Chatbot::loadConversation/$1');
    $routes->post('delete/(:num)', 'Chatbot::deleteConversation/$1');
    $routes->get('status', 'Chatbot::status');
});
