<?php

if (! isset($routes)) {
    $routes = service('routes');
}

$routes->group('chatbot', ['namespace' => 'Modules\Chatbot\Controllers', 'filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Chatbot::index');
    $routes->post('send', 'Chatbot::send');
    $routes->get('conversations', 'Chatbot::listConversations');
    $routes->post('new', 'Chatbot::newConversation');
    $routes->get('load/(:num)', 'Chatbot::loadConversation/$1');
    $routes->post('delete/(:num)', 'Chatbot::deleteConversation/$1');
    $routes->get('status', 'Chatbot::status');
    $routes->get('models', 'Chatbot::listModels');
    $routes->post('rename/(:num)', 'Chatbot::rename/$1');
    $routes->post('prompt/(:num)', 'Chatbot::setPrompt/$1');
    $routes->post('edit/(:num)', 'Chatbot::editMessage/$1');
    $routes->get('export/(:num)', 'Chatbot::exportConversation/$1');
    $routes->post('upload', 'Chatbot::upload');
});
