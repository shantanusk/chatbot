# Chatbot Glassmorphism UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Modernize the CI4 HMVC chatbot with glassmorphism UI, conversation sidebar, suggestion chips, markdown rendering, and rich interactions.

**Architecture:** Three-layer: backend PHP controller with new API endpoints, routes configuration, a self-contained single-view frontend with Tailwind CSS and vanilla JS.

**Tech Stack:** PHP 8.2, CodeIgniter 4.7, Tailwind CSS (CDN), Font Awesome (CDN), vanilla JavaScript

---

### Task 1: Add API Routes

**Files:**
- Modify: `app/Modules/Chatbot/Config/Routes.php`

- [ ] **Step 1: Add new routes for conversations API**

```php
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
```

---

### Task 2: Update Controller with New Endpoints

**Files:**
- Modify: `app/Modules/Chatbot/Controllers/Chatbot.php`

- [ ] **Step 1: Add new public methods to controller**

Full updated controller content:

```php
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
            'reply' => $botResponse,
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

    public function loadConversation($id)
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

    public function deleteConversation($id)
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
```

---

### Task 3: Rebuild Chat View with Glassmorphism UI

**Files:**
- Modify: `app/Modules/Chatbot/Views/chat.php`

- [ ] **Step 1: Write the complete glassmorphism chat view**

Full view content:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI ChatBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        body.dark {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        /* Glass base */
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .dark .glass {
            background: rgba(0, 0, 0, 0.25);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .glass-strong {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .dark .glass-strong {
            background: rgba(0, 0, 0, 0.35);
            border-color: rgba(255, 255, 255, 0.06);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .dark .glass-card {
            background: rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Chat container */
        #chatContainer {
            width: 100%;
            max-width: 1100px;
            height: 85vh;
            max-height: 800px;
            display: flex;
            border-radius: 24px;
            overflow: hidden;
            position: relative;
        }

        /* Sidebar */
        #sidebar {
            width: 280px;
            min-width: 280px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.15);
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        .dark #sidebar {
            border-color: rgba(255, 255, 255, 0.06);
        }
        #sidebar.hidden-sidebar {
            transform: translateX(-100%);
            opacity: 0;
            position: absolute;
            z-index: 10;
            height: 100%;
        }
        #sidebar.show-sidebar {
            transform: translateX(0);
            opacity: 1;
            position: absolute;
            z-index: 10;
            height: 100%;
        }

        /* Main chat area */
        #mainChat {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }

        /* Messages */
        #messages { scroll-behavior: smooth; }
        .msg-enter {
            animation: msgFadeIn 0.3s ease forwards;
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }
        @keyframes msgFadeIn {
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Typing indicator */
        .typing-dot { animation: typingBounce 1.4s infinite both; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }

        /* Bubbles */
        .bubble-user {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 18px 18px 4px 18px;
        }
        .bubble-bot {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.9);
            border-radius: 18px 18px 18px 4px;
        }
        .dark .bubble-bot {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.85);
        }

        /* Suggestion chips */
        .chip {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.85);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            user-select: none;
        }
        .chip:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.15);
        }
        .dark .chip {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .dark .chip:hover {
            background: rgba(255, 255, 255, 0.12);
        }

        /* Scroll to bottom */
        #scrollBottom {
            position: absolute;
            bottom: 80px;
            right: 50%;
            transform: translateX(50%);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: white;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 5;
        }
        #scrollBottom:hover { background: rgba(255, 255, 255, 0.35); transform: translateX(50%) scale(1.1); }
        #scrollBottom.show { display: flex; }

        /* Textarea */
        #messageInput {
            resize: none;
            max-height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border-radius: 16px;
            padding: 12px 18px;
            font-size: 14px;
            outline: none;
            width: 100%;
            line-height: 1.4;
            transition: border-color 0.2s;
        }
        #messageInput:focus { border-color: rgba(255, 255, 255, 0.4); }
        #messageInput::placeholder { color: rgba(255, 255, 255, 0.4); }
        .dark #messageInput {
            background: rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.85);
        }
        .dark #messageInput:focus { border-color: rgba(255, 255, 255, 0.2); }
        .dark #messageInput::placeholder { color: rgba(255, 255, 255, 0.3); }

        /* Send button */
        #sendBtn {
            width: 48px;
            height: 48px;
            min-width: 48px;
            border-radius: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        #sendBtn:hover { transform: scale(1.05); box-shadow: 0 0 20px rgba(102, 126, 234, 0.4); }
        #sendBtn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }

        /* Copy toast */
        .copy-toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(12px);
            color: white;
            padding: 8px 20px;
            border-radius: 12px;
            font-size: 13px;
            transition: transform 0.3s ease;
            z-index: 100;
            pointer-events: none;
        }
        .copy-toast.show { transform: translateX(-50%) translateY(0); }

        /* Markdown styles */
        .bubble-bot strong { font-weight: 600; }
        .bubble-bot code {
            background: rgba(0, 0, 0, 0.3);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 13px;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
        }
        .bubble-bot pre {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px 16px;
            border-radius: 12px;
            margin: 8px 0;
            overflow-x: auto;
        }
        .bubble-bot pre code {
            background: none;
            padding: 0;
            border-radius: 0;
        }
        .bubble-bot ul, .bubble-bot ol { padding-left: 20px; margin: 6px 0; }
        .bubble-bot li { margin: 3px 0; }
        .bubble-bot a { color: #a78bfa; text-decoration: underline; }
        .bubble-bot p { margin: 4px 0; }

        /* Active conversation in sidebar */
        .conv-active {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3) !important;
        }
        .dark .conv-active {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        /* Sidebar toggle button */
        #sidebarToggle {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 0; align-items: stretch; }
            #chatContainer {
                height: 100vh;
                max-height: none;
                border-radius: 0;
            }
            #sidebar {
                position: absolute;
                z-index: 20;
                height: 100%;
                left: 0;
                top: 0;
                transform: translateX(-100%);
                width: 280px;
                border-radius: 0 16px 16px 0;
            }
            #sidebar.show-sidebar {
                transform: translateX(0);
                box-shadow: 0 0 40px rgba(0,0,0,0.3);
            }
            #sidebarToggle {
                display: flex !important;
            }
            .suggestions-row {
                overflow-x: auto;
                flex-wrap: nowrap;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <!-- Copy Toast -->
    <div id="copyToast" class="copy-toast">Copied!</div>

    <!-- Chat Container -->
    <div id="chatContainer" class="glass-strong">

        <!-- Sidebar -->
        <div id="sidebar" class="glass hidden-sidebar">
            <!-- Sidebar Header -->
            <div class="p-4 border-b border-white/10 dark:border-white/5 flex items-center justify-between">
                <h2 class="text-white/80 text-sm font-semibold tracking-wide uppercase">Conversations</h2>
                <button onclick="toggleSidebar()" class="text-white/50 hover:text-white/80 transition-colors text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- New Chat Button -->
            <div class="p-3">
                <button onclick="newConversation()" class="w-full glass-card text-white/80 rounded-xl py-2.5 px-4 text-sm font-medium flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas fa-plus"></i> New Chat
                </button>
            </div>

            <!-- Conversation List -->
            <div id="conversationList" class="flex-1 overflow-y-auto px-2 pb-2 space-y-1">
                <!-- Populated by JS -->
            </div>

            <!-- Sidebar Footer -->
            <div class="p-3 border-t border-white/10 dark:border-white/5">
                <button onclick="toggleDarkMode()" class="w-full glass-card text-white/70 rounded-xl py-2 px-4 text-xs flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas" id="darkModeIcon">&#xf185;</i>
                    <span id="darkModeLabel">Dark Mode</span>
                </button>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div id="mainChat" class="flex flex-col flex-1 min-w-0 relative">

            <!-- Header -->
            <div class="glass px-4 py-3 flex items-center gap-3 border-b border-white/10 dark:border-white/5" style="border-radius: 23px 23px 0 0;">
                <button id="sidebarToggle" onclick="toggleSidebar()" class="text-white/70 hover:text-white transition-colors text-lg mr-1">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="w-10 h-10 rounded-full bg-white/15 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-robot text-white/80 text-lg"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-white font-semibold text-sm">AI ChatBot</h1>
                    <div class="flex items-center gap-1.5">
                        <span id="statusDot" class="w-2 h-2 rounded-full bg-gray-400 inline-block"></span>
                        <span id="statusText" class="text-white/50 text-xs">Checking...</span>
                    </div>
                </div>
                <button onclick="toggleDarkMode()" class="text-white/60 hover:text-white/90 transition-colors text-lg p-1 hidden md:flex">
                    <i class="fas" id="darkModeIconDesktop">&#xf185;</i>
                </button>
            </div>

            <!-- Messages Area -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto px-4 md:px-6 py-4 relative">
                <div id="messages" class="space-y-3">
                    <?php if (empty($messages)): ?>
                        <!-- Welcome Screen -->
                        <div id="welcomeScreen" class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mb-4 border border-white/15">
                                <i class="fas fa-robot text-white/70 text-3xl"></i>
                            </div>
                            <h2 class="text-white/90 text-xl font-semibold mb-1">Hello! I'm AI ChatBot</h2>
                            <p class="text-white/50 text-sm max-w-xs">Ask me anything — I'm here to help!</p>
                            <div class="flex flex-wrap gap-2 justify-center mt-6 suggestions-row" style="max-width: 400px;">
                                <span class="chip" onclick="sendSuggestion('Tell me a joke')">Tell me a joke</span>
                                <span class="chip" onclick="sendSuggestion('Write a poem')">Write a poem</span>
                                <span class="chip" onclick="sendSuggestion('Explain quantum computing simply')">Explain quantum computing</span>
                                <span class="chip" onclick="sendSuggestion('Give me productivity tips')">Productivity tips</span>
                                <span class="chip" onclick="sendSuggestion('Help me write SQL')">Help me write SQL</span>
                                <span class="chip" onclick="sendSuggestion('What is the meaning of life?')">Meaning of life</span>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <?php $isUser = $msg->role === 'user'; ?>
                            <div class="flex items-end gap-2 <?= $isUser ? 'justify-end' : 'justify-start' ?> msg-enter">
                                <?php if (! $isUser): ?>
                                    <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10">
                                        <i class="fas fa-robot text-white/60 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="relative group max-w-[75%] md:max-w-[65%]">
                                    <div class="<?= $isUser ? 'bubble-user' : 'bubble-bot' ?> px-4 py-2.5 text-sm leading-relaxed">
                                        <?= $isUser ? esc($msg->message) : '<div class="markdown-content">' . esc($msg->message) . '</div>' ?>
                                    </div>
                                    <?php if (! $isUser): ?>
                                        <button onclick="copyMessage(this)" class="absolute -bottom-5 right-0 text-white/30 hover:text-white/70 transition-colors text-xs opacity-0 group-hover:opacity-100">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    <?php endif; ?>
                                    <div class="text-[10px] text-white/30 mt-0.5 <?= $isUser ? 'text-right' : 'text-left' ?>" title="<?= date('M j, Y g:i A', strtotime($msg->created_at)) ?>">
                                        <?= timeAgo($msg->created_at) ?>
                                    </div>
                                </div>
                                <?php if ($isUser): ?>
                                    <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10">
                                        <i class="fas fa-user text-white/60 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Typing Indicator -->
                <div id="typingIndicator" class="hidden mt-3">
                    <div class="flex items-end gap-2">
                        <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10">
                            <i class="fas fa-robot text-white/60 text-xs"></i>
                        </div>
                        <div class="bubble-bot px-4 py-3">
                            <div class="flex items-center gap-1.5">
                                <span class="typing-dot w-2 h-2 rounded-full bg-white/50 inline-block"></span>
                                <span class="typing-dot w-2 h-2 rounded-full bg-white/50 inline-block"></span>
                                <span class="typing-dot w-2 h-2 rounded-full bg-white/50 inline-block"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scroll to Bottom -->
            <button id="scrollBottom" onclick="scrollToBottom()">
                <i class="fas fa-chevron-down"></i>
            </button>

            <!-- Input Area -->
            <div class="glass px-4 py-3 border-t border-white/10 dark:border-white/5" style="border-radius: 0 0 23px 23px;">
                <div class="flex gap-2 items-end">
                    <div class="flex-1 relative">
                        <textarea id="messageInput" rows="1" placeholder="Type your message..." oninput="autoResize(this)" onkeydown="handleKeyDown(event)"></textarea>
                    </div>
                    <button id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="flex gap-2 mt-2 overflow-x-auto suggestions-row" style="-webkit-overflow-scrolling: touch;">
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Explain this')">Explain this</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Summarize')">Summarize</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Translate to Spanish')">Translate</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Write code')">Write code</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const sendUrl = <?= json_encode($sendUrl) ?>;
        const currentConversationId = <?= json_encode($conversationId) ?>;
        const baseUrl = sendUrl.replace('/send', '');

        // State
        let activeConversationId = currentConversationId;
        let isSending = false;
        let isDarkMode = localStorage.getItem('chatbot-dark-mode') === 'true';

        // DOM refs
        const messagesEl = document.getElementById('messages');
        const messagesContainer = document.getElementById('messagesContainer');
        const chatContainer = document.getElementById('chatContainer');
        const sidebar = document.getElementById('sidebar');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const typingIndicator = document.getElementById('typingIndicator');
        const scrollBottomBtn = document.getElementById('scrollBottom');
        const conversationList = document.getElementById('conversationList');
        const welcomeScreen = document.getElementById('welcomeScreen');
        const statusDot = document.getElementById('statusDot');
        const statusText = document.getElementById('statusText');
        const copyToast = document.getElementById('copyToast');

        // Dark mode initialization
        if (isDarkMode) {
            document.body.classList.add('dark');
            updateDarkModeIcons();
        }

        // Init
        checkStatus();
        if (activeConversationId) {
            loadConversations();
        } else {
            loadConversations();
        }

        // Scroll listener for scroll-to-bottom button
        messagesContainer.addEventListener('scroll', function() {
            const threshold = 300;
            const diff = this.scrollHeight - this.scrollTop - this.clientHeight;
            scrollBottomBtn.classList.toggle('show', diff > threshold);
        });

        // --- Dark Mode ---
        function toggleDarkMode() {
            isDarkMode = !isDarkMode;
            document.body.classList.toggle('dark', isDarkMode);
            localStorage.setItem('chatbot-dark-mode', isDarkMode);
            updateDarkModeIcons();
        }

        function updateDarkModeIcons() {
            const icon = isDarkMode ? 'fa-sun' : 'fa-moon';
            const label = isDarkMode ? 'Light Mode' : 'Dark Mode';
            document.getElementById('darkModeIcon').className = 'fas ' + icon;
            document.getElementById('darkModeIconDesktop').className = 'fas ' + icon;
            document.getElementById('darkModeLabel').textContent = label;
        }

        // --- Sidebar ---
        function toggleSidebar() {
            sidebar.classList.toggle('hidden-sidebar');
            sidebar.classList.toggle('show-sidebar');
        }

        // --- Status ---
        async function checkStatus() {
            try {
                const res = await fetch(baseUrl + '/status', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                if (data.available) {
                    statusDot.className = 'w-2 h-2 rounded-full bg-green-400 inline-block';
                    statusText.textContent = 'Online';
                } else {
                    statusDot.className = 'w-2 h-2 rounded-full bg-red-400 inline-block';
                    statusText.textContent = 'Offline';
                }
            } catch {
                statusDot.className = 'w-2 h-2 rounded-full bg-red-400 inline-block';
                statusText.textContent = 'Offline';
            }
        }

        // --- Conversations ---
        async function loadConversations() {
            try {
                const res = await fetch(baseUrl + '/conversations', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                renderConversations(data);
            } catch {}
        }

        function renderConversations(convs) {
            if (!conversationList) return;
            if (!convs || convs.length === 0) {
                conversationList.innerHTML = '<div class="text-white/30 text-xs text-center py-8">No conversations yet</div>';
                return;
            }
            conversationList.innerHTML = convs.map(c => {
                const active = c.id === activeConversationId ? 'conv-active' : '';
                const title = c.title && c.title !== 'New Conversation' ? c.title : 'Chat ' + c.id;
                return `
                    <div class="glass-card rounded-xl px-3 py-2.5 cursor-pointer transition-all hover:bg-white/5 ${active}" onclick="switchConversation(${c.id})">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <div class="text-white/80 text-sm truncate">${escHtml(title)}</div>
                                <div class="text-white/30 text-xs">${c.messages} messages</div>
                            </div>
                            <button onclick="event.stopPropagation(); deleteConversation(${c.id})" class="text-white/20 hover:text-red-400 transition-colors ml-2 text-xs">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        async function newConversation() {
            try {
                const res = await fetch(baseUrl + '/new', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                activeConversationId = data.id;
                clearMessages();
                showWelcome();
                loadConversations();
                if (window.innerWidth <= 768) toggleSidebar();
            } catch {}
        }

        async function switchConversation(id) {
            if (id === activeConversationId) return;
            activeConversationId = id;
            try {
                const res = await fetch(baseUrl + '/load/' + id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                clearMessages();
                if (data.messages && data.messages.length > 0) {
                    hideWelcome();
                    data.messages.forEach(m => {
                        appendMessage(m.message, m.role === 'user', m.created_at);
                    });
                } else {
                    showWelcome();
                }
                loadConversations();
                scrollToBottom();
                if (window.innerWidth <= 768) toggleSidebar();
            } catch {}
        }

        async function deleteConversation(id) {
            if (!confirm('Delete this conversation?')) return;
            try {
                await fetch(baseUrl + '/delete/' + id, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (activeConversationId === id) {
                    activeConversationId = null;
                    clearMessages();
                    showWelcome();
                }
                loadConversations();
            } catch {}
        }

        // --- Messages ---
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || isSending) return;

            isSending = true;
            sendBtn.disabled = true;

            hideWelcome();
            appendMessage(message, true);
            messageInput.value = '';
            autoResize(messageInput);
            showTyping();
            scrollToBottom();

            try {
                const formData = new FormData();
                formData.append('message', message);
                if (activeConversationId) {
                    formData.append('conversation_id', activeConversationId);
                }

                const res = await fetch(sendUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await res.json();
                hideTyping();

                if (data.reply) {
                    // Update activeConversationId if it was just created
                    if (!activeConversationId && data.conversation_id) {
                        activeConversationId = data.conversation_id;
                    }
                    appendMessage(data.reply, false);
                    loadConversations();
                }
            } catch {
                hideTyping();
                appendMessage('Sorry, something went wrong. Please try again.', false);
            }

            isSending = false;
            sendBtn.disabled = false;
            scrollToBottom();
        }

        function appendMessage(text, isUser, createdAt) {
            const div = document.createElement('div');
            div.className = 'flex items-end gap-2 ' + (isUser ? 'justify-end' : 'justify-start') + ' msg-enter';

            if (!isUser) {
                const avatar = document.createElement('div');
                avatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
                avatar.innerHTML = '<i class="fas fa-robot text-white/60 text-xs"></i>';
                div.appendChild(avatar);
            }

            const bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'relative group max-w-[75%] md:max-w-[65%]';

            const bubble = document.createElement('div');
            bubble.className = isUser ? 'bubble-user px-4 py-2.5 text-sm leading-relaxed' : 'bubble-bot px-4 py-2.5 text-sm leading-relaxed';

            if (isUser) {
                bubble.textContent = text;
            } else {
                const md = document.createElement('div');
                md.className = 'markdown-content';
                md.innerHTML = renderMarkdown(escHtml(text));
                bubble.appendChild(md);

                const copyBtn = document.createElement('button');
                copyBtn.onclick = function() { copyMessage(this); };
                copyBtn.className = 'absolute -bottom-5 right-0 text-white/30 hover:text-white/70 transition-colors text-xs opacity-0 group-hover:opacity-100';
                copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                bubbleWrap.appendChild(copyBtn);
            }

            bubbleWrap.appendChild(bubble);

            const time = document.createElement('div');
            time.className = 'text-[10px] text-white/30 mt-0.5 ' + (isUser ? 'text-right' : 'text-left');
            const date = createdAt ? new Date(createdAt.replace(' ', 'T') + 'Z') : new Date();
            time.textContent = timeAgoFromDate(date);
            time.title = date.toLocaleString();
            bubbleWrap.appendChild(time);

            div.appendChild(bubbleWrap);

            if (isUser) {
                const avatar = document.createElement('div');
                avatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
                avatar.innerHTML = '<i class="fas fa-user text-white/60 text-xs"></i>';
                div.appendChild(avatar);
            }

            messagesEl.appendChild(div);
            scrollToBottom();
        }

        function showTyping() {
            typingIndicator.classList.remove('hidden');
            scrollToBottom();
        }

        function hideTyping() {
            typingIndicator.classList.add('hidden');
        }

        function clearMessages() {
            messagesEl.innerHTML = '';
        }

        function showWelcome() {
            if (!welcomeScreen) {
                const ws = document.createElement('div');
                ws.id = 'welcomeScreen';
                ws.className = 'flex flex-col items-center justify-center py-8 text-center';
                ws.innerHTML = `
                    <div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mb-4 border border-white/15">
                        <i class="fas fa-robot text-white/70 text-3xl"></i>
                    </div>
                    <h2 class="text-white/90 text-xl font-semibold mb-1">Hello! I'm AI ChatBot</h2>
                    <p class="text-white/50 text-sm max-w-xs">Ask me anything — I'm here to help!</p>
                    <div class="flex flex-wrap gap-2 justify-center mt-6 suggestions-row" style="max-width: 400px;">
                        <span class="chip" onclick="sendSuggestion('Tell me a joke')">Tell me a joke</span>
                        <span class="chip" onclick="sendSuggestion('Write a poem')">Write a poem</span>
                        <span class="chip" onclick="sendSuggestion('Explain quantum computing simply')">Explain quantum computing</span>
                        <span class="chip" onclick="sendSuggestion('Give me productivity tips')">Productivity tips</span>
                        <span class="chip" onclick="sendSuggestion('Help me write SQL')">Help me write SQL</span>
                        <span class="chip" onclick="sendSuggestion('What is the meaning of life?')">Meaning of life</span>
                    </div>
                `;
                messagesEl.appendChild(ws);
            }
            // Re-insert if removed
            if (!document.getElementById('welcomeScreen')) {
                // recreate
            }
            scrollToBottom();
        }

        function hideWelcome() {
            const ws = document.getElementById('welcomeScreen');
            if (ws) ws.remove();
        }

        // --- Suggestion Chips ---
        function sendSuggestion(text) {
            messageInput.value = text;
            sendMessage();
        }

        // --- Input Handling ---
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        function handleKeyDown(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        }

        // --- Scroll ---
        function scrollToBottom() {
            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 50);
        }

        // --- Copy ---
        function copyMessage(btn) {
            const bubble = btn.closest('.relative');
            const text = bubble.querySelector('.markdown-content')?.textContent || '';
            navigator.clipboard.writeText(text.trim()).then(() => {
                copyToast.classList.add('show');
                setTimeout(() => copyToast.classList.remove('show'), 1500);
            });
        }

        // --- Markdown ---
        function renderMarkdown(text) {
            // Headers
            text = text.replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-3 mb-1">$1</h3>');
            text = text.replace(/^## (.+)$/gm, '<h2 class="text-lg font-semibold mt-3 mb-1">$1</h2>');
            text = text.replace(/^# (.+)$/gm, '<h1 class="text-xl font-semibold mt-3 mb-1">$1</h1>');

            // Code blocks
            text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

            // Inline code
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Bold and italic
            text = text.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');

            // Links
            text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

            // Unordered lists
            text = text.replace(/^\s*[-*]\s+(.+)$/gm, '<li>$1</li>');
            text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');

            // Ordered lists
            text = text.replace(/^\s*\d+\.\s+(.+)$/gm, '<li>$1</li>');

            // Line breaks
            text = text.replace(/\n\n/g, '</p><p class="mt-2">');
            text = text.replace(/\n/g, '<br>');

            // Wrap in paragraph if not starting with a block element
            if (!text.startsWith('<h') && !text.startsWith('<pre') && !text.startsWith('<ul') && !text.startsWith('<p')) {
                text = '<p>' + text + '</p>';
            }

            return text;
        }

        // --- Helpers ---
        function escHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function timeAgo(dateStr) {
            const date = new Date(dateStr.replace(' ', 'T') + 'Z');
            return timeAgoFromDate(date);
        }

        function timeAgoFromDate(date) {
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return date.toLocaleDateString();
        }
    </script>
</body>
</html>
```

---

### Task 4: Verification

- [ ] **Step 1: Check PHP syntax**
```bash
php -l app/Modules/Chatbot/Controllers/Chatbot.php
php -l app/Modules/Chatbot/Config/Routes.php
```

- [ ] **Step 2: Check for any syntax errors in the view**
```bash
php -r "highlight_string(file_get_contents('app/Modules/Chatbot/Views/chat.php'));"
```

- [ ] **Step 3: Verify routes work via spark**
```bash
php spark routes
```

- [ ] **Step 4: Commit**
```bash
git add .
git commit -m "feat: glassmorphism UI with sidebar, suggestions, markdown, dark mode"
```
