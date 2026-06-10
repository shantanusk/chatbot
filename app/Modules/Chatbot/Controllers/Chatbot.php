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
        $currentModel = config(OllamaConfig::class)->model;
        $currentPrompt = null;

        if ($conversation) {
            $conversationId = $conversation->id;
            $messages = $this->messageModel
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at', 'ASC')
                ->findAll();
            $currentModel = $conversation->model ?: $currentModel;
            $currentPrompt = $conversation->system_prompt;
        }

        $client = new OllamaClient();
        $availableModels = $client->listModels();

        helper('url');
        $sendUrl = site_url('chatbot/send');

        return view('Modules\Chatbot\Views\chat', [
            'messages'        => $messages,
            'conversationId'  => $conversationId,
            'sendUrl'         => parse_url($sendUrl, PHP_URL_PATH),
            'currentModel'    => $currentModel,
            'currentPrompt'   => $currentPrompt,
            'availableModels' => $availableModels,
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
        $model = $this->request->getPost('model');
        $systemPrompt = $this->request->getPost('system_prompt');
        $stream = $this->request->getPost('stream') === '1';

        if (! $conversationId) {
            $conversation = $this->conversationModel
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'DESC')
                ->first();

            if (! $conversation) {
                $data = ['session_id' => $sessionId, 'title' => mb_substr($message, 0, 50)];
                if ($model) $data['model'] = $model;
                if ($systemPrompt) $data['system_prompt'] = $systemPrompt;
                $conversationId = $this->conversationModel->insert($data);
            } else {
                $conversationId = $conversation->id;
                $update = [];
                if ($model) $update['model'] = $model;
                if ($systemPrompt) $update['system_prompt'] = $systemPrompt;
                if ($update) $this->conversationModel->update($conversationId, $update);
            }
        } else {
            $update = [];
            if ($model) $update['model'] = $model;
            if ($systemPrompt) $update['system_prompt'] = $systemPrompt;
            if ($update) $this->conversationModel->update($conversationId, $update);
        }

        $this->messageModel->save([
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'message'         => $message,
        ]);

        if ($stream) {
            return $this->streamResponse($conversationId, $model, $systemPrompt);
        }

        $history = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $botResponse = $this->generateAIResponse($history, $model, $systemPrompt);

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

    private function streamResponse(int $conversationId, ?string $model, ?string $systemPrompt)
    {
        $history = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $conv = $this->conversationModel->find($conversationId);
        $usedModel = $model ?: ($conv->model ?: config(OllamaConfig::class)->model);
        $usedPrompt = $systemPrompt ?: ($conv->system_prompt ?: config(OllamaConfig::class)->systemPrompt);

        $messages = [
            ['role' => 'system', 'content' => $usedPrompt],
        ];
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role === 'user' ? 'user' : 'assistant', 'content' => $msg->message];
        }

        $response = service('response');
        $response->setHeader('Content-Type', 'text/event-stream');
        $response->setHeader('Cache-Control', 'no-cache');
        $response->setHeader('X-Accel-Buffering', 'no');
        $response->sendHeaders();

        $fullResponse = '';
        $client = new OllamaClient();
        $client->chatStream($messages, function ($chunk, $done) use (&$fullResponse) {
            $fullResponse .= $chunk;
            echo "data: " . json_encode(['chunk' => $chunk, 'done' => $done]) . "\n\n";
            ob_flush();
            flush();
        });

        $this->messageModel->save([
            'conversation_id' => $conversationId,
            'role'            => 'bot',
            'message'         => $fullResponse,
        ]);

        echo "data: " . json_encode(['done' => true, 'conversation_id' => $conversationId]) . "\n\n";
        ob_flush();
        flush();
        exit;
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
                'id'            => $conv->id,
                'title'         => $conv->title,
                'messages'      => $msgCount,
                'model'         => $conv->model,
                'created_at'    => $conv->created_at,
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
        $model = $this->request->getPost('model');

        $data = ['session_id' => $sessionId, 'title' => 'New Conversation'];
        if ($model) $data['model'] = $model;

        $id = $this->conversationModel->insert($data);

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
            'conversation' => $conversation ? [
                'id'            => $conversation->id,
                'title'         => $conversation->title,
                'model'         => $conversation->model,
                'system_prompt' => $conversation->system_prompt,
            ] : null,
            'messages' => $data,
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

    public function listModels()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $client = new OllamaClient();
        return $this->response->setJSON([
            'models' => $client->listModels(),
        ]);
    }

    public function rename($id = null)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $title = $this->request->getPost('title');
        if (! $title || trim($title) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Title is required']);
        }

        $this->conversationModel->update($id, ['title' => $title]);

        return $this->response->setJSON(['success' => true]);
    }

    public function setPrompt($id = null)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $prompt = $this->request->getPost('system_prompt');
        $this->conversationModel->update($id, ['system_prompt' => $prompt]);

        return $this->response->setJSON(['success' => true]);
    }

    public function editMessage($id = null)
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $newText = $this->request->getPost('message');
        if (! $newText || trim($newText) === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Message is required']);
        }

        $message = $this->messageModel->find($id);
        if (! $message) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Message not found']);
        }

        $conversationId = $message->conversation_id;

        // Delete this message and all subsequent messages
        $this->messageModel
            ->where('conversation_id', $conversationId)
            ->where('id >=', $id)
            ->delete();

        // Insert the edited message
        $this->messageModel->save([
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'message'         => $newText,
        ]);

        // Re-generate AI response
        $history = $this->messageModel
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $conv = $this->conversationModel->find($conversationId);
        $model = $conv->model ?? null;

        $botResponse = $this->generateAIResponse($history, $model);

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

    public function exportConversation($id = null)
    {
        $conversation = $this->conversationModel->find($id);
        if (! $conversation) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Conversation not found']);
        }

        $messages = $this->messageModel
            ->where('conversation_id', $id)
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $format = $this->request->getGet('format') ?? 'txt';
        $title = $conversation->title ?: 'Conversation';

        if ($format === 'md') {
            $content = "# {$title}\n\n";
            foreach ($messages as $msg) {
                $role = ucfirst($msg->role);
                $time = date('M j, Y g:i A', strtotime($msg->created_at));
                $content .= "**{$role}** ({$time}):\n{$msg->message}\n\n";
            }
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . '.md';
            $mime = 'text/markdown';
        } else {
            $content = "=== {$title} ===\n\n";
            foreach ($messages as $msg) {
                $role = ucfirst($msg->role);
                $time = date('M j, Y g:i A', strtotime($msg->created_at));
                $content .= "[{$role} - {$time}]\n{$msg->message}\n\n";
            }
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $title) . '.txt';
            $mime = 'text/plain';
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($content);
    }

    public function upload()
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $file = $this->request->getFile('file');
        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid file']);
        }

        $ext = $file->getExtension();
        $allowed = ['txt', 'md', 'csv', 'json', 'xml', 'html', 'css', 'js', 'php', 'py', 'rb', 'go', 'rs', 'sql', 'sh', 'yaml', 'yml', 'toml', 'ini', 'cfg', 'log'];

        if (! in_array(strtolower($ext), $allowed)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'File type not allowed. Supported: ' . implode(', ', $allowed)]);
        }

        $content = file_get_contents($file->getTempName());
        $name = $file->getName();

        return $this->response->setJSON([
            'filename' => $name,
            'content'  => $content,
            'size'     => $file->getSize(),
        ]);
    }

    private function generateAIResponse(array $history, ?string $model = null, ?string $systemPrompt = null): string
    {
        $ollamaConfig = config(OllamaConfig::class);

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt ?: $ollamaConfig->systemPrompt],
        ];

        foreach ($history as $msg) {
            $role = $msg->role === 'user' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msg->message];
        }

        $client = new OllamaClient();
        return $client->chat($messages, $model);
    }
}
