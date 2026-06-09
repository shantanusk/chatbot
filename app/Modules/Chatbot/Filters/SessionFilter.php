<?php

namespace Modules\Chatbot\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class SessionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = service('session');
        if (! $session->has('chatbot_session_id')) {
            $session->set('chatbot_session_id', bin2hex(random_bytes(16)));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
