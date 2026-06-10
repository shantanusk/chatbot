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

        #mainChat {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }

        #messages { scroll-behavior: smooth; }
        .msg-enter {
            animation: msgFadeIn 0.3s ease forwards;
            opacity: 0;
            transform: translateY(12px) scale(0.98);
        }
        @keyframes msgFadeIn {
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .typing-dot { animation: typingBounce 1.4s infinite both; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBounce {
            0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
            30% { transform: translateY(-6px); opacity: 1; }
        }

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

        textarea#messageInput {
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
            font-family: inherit;
        }
        textarea#messageInput:focus { border-color: rgba(255, 255, 255, 0.4); }
        textarea#messageInput::placeholder { color: rgba(255, 255, 255, 0.4); }
        .dark textarea#messageInput {
            background: rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.85);
        }
        .dark textarea#messageInput:focus { border-color: rgba(255, 255, 255, 0.2); }
        .dark textarea#messageInput::placeholder { color: rgba(255, 255, 255, 0.3); }

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

        .conv-active {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
        }
        .dark .conv-active {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }

        #sidebarToggle {
            display: none;
        }

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
    <div id="copyToast" class="copy-toast">Copied!</div>

    <div id="chatContainer" class="glass-strong">

        <div id="sidebar" class="glass hidden-sidebar">
            <div class="p-4 border-b border-white/10 dark:border-white/5 flex items-center justify-between">
                <h2 class="text-white/80 text-sm font-semibold tracking-wide uppercase">Conversations</h2>
                <button onclick="toggleSidebar()" class="text-white/50 hover:text-white/80 transition-colors text-lg">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="p-3">
                <button onclick="newConversation()" class="w-full glass-card text-white/80 rounded-xl py-2.5 px-4 text-sm font-medium flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas fa-plus"></i> New Chat
                </button>
            </div>

            <div id="conversationList" class="flex-1 overflow-y-auto px-2 pb-2 space-y-1"></div>

            <div class="p-3 border-t border-white/10 dark:border-white/5">
                <button onclick="toggleDarkMode()" class="w-full glass-card text-white/70 rounded-xl py-2 px-4 text-xs flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas" id="darkModeIcon">&#xf185;</i>
                    <span id="darkModeLabel">Dark Mode</span>
                </button>
            </div>
        </div>

        <div id="mainChat" class="flex flex-col flex-1 min-w-0 relative">
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
                <div class="flex items-center gap-1">
                    <button onclick="newConversation()" class="text-white/60 hover:text-white/90 transition-colors text-base p-1.5 hidden md:flex" title="New Chat">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button onclick="toggleDarkMode()" class="text-white/60 hover:text-white/90 transition-colors text-lg p-1 hidden md:flex">
                        <i class="fas" id="darkModeIconDesktop">&#xf185;</i>
                    </button>
                </div>
            </div>

            <div id="messagesContainer" class="flex-1 overflow-y-auto px-4 md:px-6 py-4 relative">
                <div id="messages" class="space-y-3">
                    <?php if (empty($messages)): ?>
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
                            <?php
                                $alignClass = $isUser ? 'justify-end' : 'justify-start';
                                $bubbleClass = $isUser ? 'bubble-user' : 'bubble-bot';
                                $timeAlign = $isUser ? 'text-right' : 'text-left';
                                $timeTitle = date('M j, Y g:i A', strtotime($msg->created_at));
                                $timeDisplay = date('g:i A', strtotime($msg->created_at));
                            ?>
                            <div class="flex items-end gap-2 <?= $alignClass ?> msg-enter">
                                <?php if (! $isUser): ?>
                                    <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10">
                                        <i class="fas fa-robot text-white/60 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="relative group max-w-[75%] md:max-w-[65%]">
                                    <div class="<?= $bubbleClass ?> px-4 py-2.5 text-sm leading-relaxed">
                                        <?php if ($isUser): ?>
                                            <?= esc($msg->message) ?>
                                        <?php else: ?>
                                            <div class="markdown-content"><?= esc($msg->message) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (! $isUser): ?>
                                        <button onclick="copyMessage(this)" class="absolute -bottom-5 right-0 text-white/30 hover:text-white/70 transition-colors text-xs opacity-0 group-hover:opacity-100">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    <?php endif; ?>
                                    <div class="text-[10px] text-white/30 mt-0.5 <?= $timeAlign ?>" title="<?= $timeTitle ?>">
                                        <?= $timeDisplay ?>
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

            <button id="scrollBottom" onclick="scrollToBottom()">
                <i class="fas fa-chevron-down"></i>
            </button>

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
        const sendUrl = <?= json_encode($sendUrl) ?>;
        const initialConversationId = <?= json_encode($conversationId) ?>;
        const baseUrl = sendUrl.replace('/send', '');

        let activeConversationId = initialConversationId;
        let isSending = false;
        let isDarkMode = localStorage.getItem('chatbot-dark-mode') === 'true';

        const messagesEl = document.getElementById('messages');
        const messagesContainer = document.getElementById('messagesContainer');
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

        if (isDarkMode) {
            document.body.classList.add('dark');
            updateDarkModeIcons();
        }

        // Render Markdown for existing server-rendered messages
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.markdown-content').forEach(function(el) {
                el.innerHTML = renderMarkdown(el.textContent);
            });
        });

        checkStatus();
        loadConversations();

        messagesContainer.addEventListener('scroll', function() {
            const diff = this.scrollHeight - this.scrollTop - this.clientHeight;
            scrollBottomBtn.classList.toggle('show', diff > 300);
        });

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

        function toggleSidebar() {
            sidebar.classList.toggle('hidden-sidebar');
            sidebar.classList.toggle('show-sidebar');
        }

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
            conversationList.innerHTML = convs.map(function(c) {
                var active = c.id === activeConversationId ? 'conv-active' : '';
                var title = (c.title && c.title !== 'New Conversation') ? c.title : 'Chat ' + c.id;
                return '<div class="glass-card rounded-xl px-3 py-2.5 cursor-pointer transition-all hover:bg-white/5 ' + active + '" onclick="switchConversation(' + c.id + ')">' +
                    '<div class="flex items-center justify-between">' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="text-white/80 text-sm truncate">' + escHtml(title) + '</div>' +
                    '<div class="text-white/30 text-xs">' + c.messages + ' messages</div>' +
                    '</div>' +
                    '<button onclick="event.stopPropagation(); deleteConversation(' + c.id + ')" class="text-white/20 hover:text-red-400 transition-colors ml-2 text-xs">' +
                    '<i class="fas fa-trash"></i>' +
                    '</button>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        async function newConversation() {
            try {
                var res = await fetch(baseUrl + '/new', {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await res.json();
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
                var res = await fetch(baseUrl + '/load/' + id, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await res.json();
                clearMessages();
                if (data.messages && data.messages.length > 0) {
                    hideWelcome();
                    data.messages.forEach(function(m) {
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

        async function sendMessage() {
            var message = messageInput.value.trim();
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
                var formData = new FormData();
                formData.append('message', message);
                if (activeConversationId) {
                    formData.append('conversation_id', activeConversationId);
                }

                var res = await fetch(sendUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                var data = await res.json();
                hideTyping();

                if (data.reply) {
                    if (!activeConversationId && data.conversation_id) {
                        activeConversationId = data.conversation_id;
                    }
                    appendMessage(data.reply, false);
                    loadConversations();
                }
            } catch (err) {
                hideTyping();
                appendMessage('Sorry, something went wrong. Please try again.', false);
            }

            isSending = false;
            sendBtn.disabled = false;
            scrollToBottom();
        }

        function appendMessage(text, isUser, createdAt) {
            var div = document.createElement('div');
            div.className = 'flex items-end gap-2 ' + (isUser ? 'justify-end' : 'justify-start') + ' msg-enter';

            if (!isUser) {
                var avatar = document.createElement('div');
                avatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
                avatar.innerHTML = '<i class="fas fa-robot text-white/60 text-xs"></i>';
                div.appendChild(avatar);
            }

            var bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'relative group max-w-[75%] md:max-w-[65%]';

            var bubble = document.createElement('div');
            bubble.className = isUser ? 'bubble-user px-4 py-2.5 text-sm leading-relaxed' : 'bubble-bot px-4 py-2.5 text-sm leading-relaxed';

            if (isUser) {
                bubble.textContent = text;
            } else {
                var md = document.createElement('div');
                md.className = 'markdown-content';
                md.innerHTML = renderMarkdown(escHtml(text));
                bubble.appendChild(md);

                var copyBtn = document.createElement('button');
                copyBtn.onclick = function() { copyMessage(this); };
                copyBtn.className = 'absolute -bottom-5 right-0 text-white/30 hover:text-white/70 transition-colors text-xs opacity-0 group-hover:opacity-100';
                copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                bubbleWrap.appendChild(copyBtn);
            }

            bubbleWrap.appendChild(bubble);

            var time = document.createElement('div');
            time.className = 'text-[10px] text-white/30 mt-0.5 ' + (isUser ? 'text-right' : 'text-left');
            var date = createdAt ? new Date(createdAt.replace(' ', 'T') + 'Z') : new Date();
            time.textContent = timeAgoFromDate(date);
            time.title = date.toLocaleString();
            bubbleWrap.appendChild(time);

            div.appendChild(bubbleWrap);

            if (isUser) {
                var userAvatar = document.createElement('div');
                userAvatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
                userAvatar.innerHTML = '<i class="fas fa-user text-white/60 text-xs"></i>';
                div.appendChild(userAvatar);
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
            var existing = document.getElementById('welcomeScreen');
            if (existing) { existing.style.display = ''; return; }
            var ws = document.createElement('div');
            ws.id = 'welcomeScreen';
            ws.className = 'flex flex-col items-center justify-center py-8 text-center';
            ws.innerHTML = '' +
                '<div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mb-4 border border-white/15">' +
                '<i class="fas fa-robot text-white/70 text-3xl"></i>' +
                '</div>' +
                '<h2 class="text-white/90 text-xl font-semibold mb-1">Hello! I\'m AI ChatBot</h2>' +
                '<p class="text-white/50 text-sm max-w-xs">Ask me anything — I\'m here to help!</p>' +
                '<div class="flex flex-wrap gap-2 justify-center mt-6 suggestions-row" style="max-width: 400px;">' +
                '<span class="chip" onclick="sendSuggestion(\'Tell me a joke\')">Tell me a joke</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Write a poem\')">Write a poem</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Explain quantum computing simply\')">Explain quantum computing</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Give me productivity tips\')">Productivity tips</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Help me write SQL\')">Help me write SQL</span>' +
                '<span class="chip" onclick="sendSuggestion(\'What is the meaning of life?\')">Meaning of life</span>' +
                '</div></div>';
            messagesEl.appendChild(ws);
            scrollToBottom();
        }

        function hideWelcome() {
            var ws = document.getElementById('welcomeScreen');
            if (ws) ws.style.display = 'none';
        }

        function sendSuggestion(text) {
            messageInput.value = text;
            sendMessage();
        }

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

        function scrollToBottom() {
            setTimeout(function() {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 50);
        }

        function copyMessage(btn) {
            var bubble = btn.closest('.relative');
            var text = bubble.querySelector('.markdown-content')?.textContent || '';
            navigator.clipboard.writeText(text.trim()).then(function() {
                copyToast.classList.add('show');
                setTimeout(function() { copyToast.classList.remove('show'); }, 1500);
            });
        }

        function renderMarkdown(text) {
            text = text.replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-3 mb-1">$1</h3>');
            text = text.replace(/^## (.+)$/gm, '<h2 class="text-lg font-semibold mt-3 mb-1">$1</h2>');
            text = text.replace(/^# (.+)$/gm, '<h1 class="text-xl font-semibold mt-3 mb-1">$1</h1>');

            text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

            text = text.replace(/\*\*\*(.+?)\*\*\*/g, '<strong><em>$1</em></strong>');
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.+?)\*/g, '<em>$1</em>');

            text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');

            text = text.replace(/^\s*[-*]\s+(.+)$/gm, '<li>$1</li>');
            text = text.replace(/(<li>.*<\/li>\n?)+/g, '<ul>$&</ul>');
            text = text.replace(/^\s*\d+\.\s+(.+)$/gm, '<li>$1</li>');

            text = text.replace(/\n\n/g, '</p><p class="mt-2">');
            text = text.replace(/\n/g, '<br>');

            if (!text.startsWith('<h') && !text.startsWith('<pre') && !text.startsWith('<ul') && !text.startsWith('<p')) {
                text = '<p>' + text + '</p>';
            }

            return text;
        }

        function escHtml(str) {
            var div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function timeAgo(dateStr) {
            var date = new Date(dateStr.replace(' ', 'T') + 'Z');
            return timeAgoFromDate(date);
        }

        function timeAgoFromDate(date) {
            var now = new Date();
            var diff = Math.floor((now - date) / 1000);
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return date.toLocaleDateString();
        }
    </script>
</body>
</html>
