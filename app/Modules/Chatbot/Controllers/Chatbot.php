<?php

namespace Modules\Chatbot\Controllers;

use CodeIgniter\Controller;
use Modules\Chatbot\Config\Ollama as OllamaConfig;
use Modules\Chatbot\Libraries\OllamaClient;
use Modules\Chatbot\Models\ConversationModel;
use Modules\Chatbot\Models\MessageModel;

class Chatbot extends Controller
{
    protected $conversationModel;
    protected $messageModel;

    public function __construct()
    {
        $this->conversationModel = new ConversationModel();
        $this->messageModel      = new MessageModel();
    }

    private function ensureSession(): string
    {
        $session = service('session');
        $sessionId = $session->get('chatbot_session_id');
        if (! $sessionId) {
            $sessionId = bin2hex(random_bytes(16));
            $session->set('chatbot_session_id', $sessionId);
        }
        return $sessionId;
    }

    public function index()
    {
        $sessionId = $this->ensureSession();

        $conversation = $this->conversationModel
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'DESC')
            ->first();

        $messages = [];
        $conversationId = null;

        if ($conversation) {
            $conversationId = $conversation->id;
            $messages = $this->messageModel
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at', 'ASC')
                ->findAll();
        }

        helper('url');
        $sendUrl = site_url('chatbot/send');

        return view('Modules\Chatbot\Views\chat', [
            'messages'       => $messages,
            'conversationId' => $conversationId,
            'sendUrl'        => parse_url($sendUrl, PHP_URL_PATH),
        ]);
    }

    public function send()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $message = $this->request->getPost('message');
        if (! $message || trim($message) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Message is required']);
        }

        $sessionId = $this->ensureSession();

        $conversation = $this->conversationModel
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'DESC')
            ->first();

        if (! $conversation) {
            $conversationId = $this->conversationModel->insert([
                'session_id' => $sessionId,
                'title'      => mb_substr($message, 0, 50),
            ]);
        } else {
            $conversationId = $conversation->id;
        }

        $this->messageModel->save([
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'message'         => $message,
        ]);

        $history = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $botResponse = $this->generateAIResponse($history);

        $this->messageModel->save([
            'conversation_id' => $conversationId,
            'role'            => 'bot',
            'message'         => $botResponse,
        ]);

        return $this->response->setJSON([
            'reply' => $botResponse,
        ]);
    }

    private function generateAIResponse(array $history): string
    {
        $ollamaConfig = config(OllamaConfig::class);

        $messages = [
            ['role' => 'system', 'content' => $ollamaConfig->systemPrompt],
        ];

        foreach ($history as $msg) {
            $role = $msg->role === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg->message];
        }

        $client = new OllamaClient();
        return $client->chat($messages);
    }
}
