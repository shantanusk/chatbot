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

        $conversationId = $this->request->getPost('conversation_id');
        if (! $conversationId) {
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
            'reply'           => $botResponse,
            'conversation_id' => $conversationId,
        ]);
    }

    public function listConversations()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $sessionId = $this->ensureSession();

        $conversations = $this->conversationModel
            ->where('session_id', $sessionId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        $data = [];
        foreach ($conversations as $conv) {
            $msgCount = $this->messageModel
                ->where('conversation_id', $conv->id)
                ->countAllResults();
            $data[] = [
                'id'         => $conv->id,
                'title'      => $conv->title,
                'messages'   => $msgCount,
                'created_at' => $conv->created_at,
            ];
        }

        return $this->response->setJSON($data);
    }

    public function newConversation()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $sessionId = $this->ensureSession();

        $id = $this->conversationModel->insert([
            'session_id' => $sessionId,
            'title'      => 'New Conversation',
        ]);

        return $this->response->setJSON([
            'id'    => $id,
            'title' => 'New Conversation',
        ]);
    }

    public function loadConversation($id = null)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $messages = $this->messageModel
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $conversation = $this->conversationModel->find($id);

        $data = [];
        foreach ($messages as $msg) {
            $data[] = [
                'id'         => $msg->id,
                'role'       => $msg->role,
                'message'    => $msg->message,
                'created_at' => $msg->created_at,
            ];
        }

        return $this->response->setJSON([
            'conversation' => $conversation ? ['id' => $conversation->id, 'title' => $conversation->title] : null,
            'messages'     => $data,
        ]);
    }

    public function deleteConversation($id = null)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $this->messageModel->where('conversation_id', $id)->delete();
        $this->conversationModel->delete($id);

        return $this->response->setJSON(['success' => true]);
    }

    public function status()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $client = new OllamaClient();
        return $this->response->setJSON([
            'available' => $client->isAvailable(),
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
