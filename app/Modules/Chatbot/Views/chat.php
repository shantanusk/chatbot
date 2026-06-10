<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI ChatBot</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
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
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .dark .glass {
            background: rgba(0, 0, 0, 0.25);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .glass-strong {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .dark .glass-strong {
            background: rgba(0, 0, 0, 0.35);
            border-color: rgba(255, 255, 255, 0.06);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
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
        .dark #sidebar { border-color: rgba(255, 255, 255, 0.06); }
        #sidebar.hidden-sidebar { transform: translateX(-100%); opacity: 0; position: absolute; z-index: 10; height: 100%; }
        #sidebar.show-sidebar { transform: translateX(0); opacity: 1; position: absolute; z-index: 10; height: 100%; }
        #mainChat { flex: 1; display: flex; flex-direction: column; min-width: 0; }
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
        .dark ::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }
        #messages { scroll-behavior: smooth; }
        .msg-enter { animation: msgFadeIn 0.3s ease forwards; opacity: 0; transform: translateY(12px) scale(0.98); }
        @keyframes msgFadeIn { to { opacity: 1; transform: translateY(0) scale(1); } }
        .typing-dot { animation: typingBounce 1.4s infinite both; }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typingBounce { 0%, 60%, 100% { transform: translateY(0); opacity: 0.4; } 30% { transform: translateY(-6px); opacity: 1; } }
        .bubble-user { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 18px 18px 4px 18px; }
        .bubble-bot { background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.2); color: rgba(255, 255, 255, 0.9); border-radius: 18px 18px 18px 4px; }
        .dark .bubble-bot { background: rgba(0, 0, 0, 0.3); border-color: rgba(255, 255, 255, 0.06); color: rgba(255, 255, 255, 0.85); }
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
        .chip:hover { background: rgba(255, 255, 255, 0.25); transform: scale(1.05); box-shadow: 0 0 20px rgba(255, 255, 255, 0.15); }
        .dark .chip { background: rgba(255, 255, 255, 0.06); border-color: rgba(255, 255, 255, 0.08); }
        .dark .chip:hover { background: rgba(255, 255, 255, 0.12); }
        #scrollBottom {
            position: absolute; bottom: 80px; right: 50%; transform: translateX(50%);
            width: 40px; height: 40px; border-radius: 50%;
            background: rgba(255, 255, 255, 0.2); backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25); color: white; cursor: pointer;
            display: none; align-items: center; justify-content: center;
            transition: all 0.2s ease; z-index: 5;
        }
        #scrollBottom:hover { background: rgba(255, 255, 255, 0.35); transform: translateX(50%) scale(1.1); }
        #scrollBottom.show { display: flex; }
        textarea#messageInput {
            resize: none; max-height: 120px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9); border-radius: 16px;
            padding: 12px 18px; font-size: 14px; outline: none;
            width: 100%; line-height: 1.4; transition: border-color 0.2s; font-family: inherit;
        }
        textarea#messageInput:focus { border-color: rgba(255, 255, 255, 0.4); }
        textarea#messageInput::placeholder { color: rgba(255, 255, 255, 0.4); }
        .dark textarea#messageInput { background: rgba(0, 0, 0, 0.2); border-color: rgba(255, 255, 255, 0.08); color: rgba(255, 255, 255, 0.85); }
        .dark textarea#messageInput:focus { border-color: rgba(255, 255, 255, 0.2); }
        .dark textarea#messageInput::placeholder { color: rgba(255, 255, 255, 0.3); }
        #sendBtn { width: 48px; height: 48px; min-width: 48px; border-radius: 16px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
        #sendBtn:hover { transform: scale(1.05); box-shadow: 0 0 20px rgba(102, 126, 234, 0.4); }
        #sendBtn:disabled { opacity: 0.4; cursor: not-allowed; transform: none; box-shadow: none; }
        .copy-toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(100px); background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(12px); color: white; padding: 8px 20px; border-radius: 12px; font-size: 13px; transition: transform 0.3s ease; z-index: 100; pointer-events: none; }
        .copy-toast.show { transform: translateX(-50%) translateY(0); }
        .bubble-bot strong { font-weight: 600; }
        .bubble-bot code:not(pre code) { background: rgba(0, 0, 0, 0.3); padding: 2px 6px; border-radius: 4px; font-size: 13px; font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace; }
        .bubble-bot pre { background: rgba(0, 0, 0, 0.3); padding: 12px 16px; border-radius: 12px; margin: 8px 0; overflow-x: auto; }
        .bubble-bot pre code { background: none; padding: 0; border-radius: 0; }
        .bubble-bot ul, .bubble-bot ol { padding-left: 20px; margin: 6px 0; }
        .bubble-bot li { margin: 3px 0; }
        .bubble-bot a { color: #a78bfa; text-decoration: underline; }
        .bubble-bot p { margin: 4px 0; }
        .conv-active { background: rgba(255, 255, 255, 0.15) !important; border-color: rgba(255, 255, 255, 0.3) !important; }
        .dark .conv-active { background: rgba(255, 255, 255, 0.08) !important; border-color: rgba(255, 255, 255, 0.15) !important; }
        #sidebarToggle { display: none; }
        @media (max-width: 768px) {
            body { padding: 0; align-items: stretch; }
            #chatContainer { height: 100vh; max-height: none; border-radius: 0; }
            #sidebar { position: absolute; z-index: 20; height: 100%; left: 0; top: 0; transform: translateX(-100%); width: 280px; border-radius: 0 16px 16px 0; }
            #sidebar.show-sidebar { transform: translateX(0); box-shadow: 0 0 40px rgba(0,0,0,0.3); }
            #sidebarToggle { display: flex !important; }
            .suggestions-row { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
        }
        #micBtn { transition: all 0.2s ease; }
        #micBtn:hover { background: rgba(255, 255, 255, 0.2) !important; color: rgba(255, 255, 255, 0.9) !important; }
        #micBtn.recording { background: rgba(239, 68, 68, 0.3) !important; border-color: rgba(239, 68, 68, 0.5) !important; color: #ef4444 !important; animation: pulse-mic 1s ease infinite; }
        @keyframes pulse-mic { 0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 50% { box-shadow: 0 0 0 12px rgba(239, 68, 68, 0); } }
        .speaker-btn { transition: all 0.2s ease; }
        .speaker-btn:hover { color: rgba(255, 255, 255, 0.9) !important; }
        .speaker-btn.speaking { color: #a78bfa !important; animation: pulse-speak 0.8s ease infinite; }
        @keyframes pulse-speak { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .model-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.85);
            border-radius: 10px;
            padding: 4px 10px;
            font-size: 12px;
            outline: none;
            cursor: pointer;
            max-width: 140px;
        }
        .model-select option { background: #1a1a2e; color: white; }
        .dark .model-select { background: rgba(0, 0, 0, 0.2); border-color: rgba(255, 255, 255, 0.08); }
        .settings-panel { display: none; }
        .settings-panel.open { display: flex; }
        .edit-input { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; padding: 4px 8px; color: white; font-size: 13px; outline: none; width: 100%; }
        .edit-input:focus { border-color: rgba(255,255,255,0.4); }
        /* Blinking cursor for streaming */
        .stream-cursor::after { content: '|'; animation: blink-cursor 0.7s infinite; }
        @keyframes blink-cursor { 0%, 50% { opacity: 1; } 51%, 100% { opacity: 0; } }
        /* File upload button */
        #uploadBtn { transition: all 0.2s ease; }
        #uploadBtn:hover { background: rgba(255, 255, 255, 0.2) !important; color: rgba(255, 255, 255, 0.9) !important; }
        /* Toast */
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(100px); background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(12px); color: white; padding: 8px 20px; border-radius: 12px; font-size: 13px; transition: transform 0.3s ease; z-index: 100; pointer-events: none; }
        .toast.show { transform: translateX(-50%) translateY(0); }
    </style>
</head>
<body>
    <div id="toast" class="toast"></div>

    <div id="chatContainer" class="glass-strong">

        <!-- Sidebar -->
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
            <div class="p-3 border-t border-white/10 dark:border-white/5 flex flex-col gap-2">
                <button onclick="toggleSettings()" class="w-full glass-card text-white/70 rounded-xl py-2 px-4 text-xs flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas fa-sliders-h"></i> <span>Settings</span>
                </button>
                <button onclick="toggleDarkMode()" class="w-full glass-card text-white/70 rounded-xl py-2 px-4 text-xs flex items-center justify-center gap-2 hover:bg-white/10 transition-all">
                    <i class="fas" id="darkModeIcon">&#xf185;</i> <span id="darkModeLabel">Dark Mode</span>
                </button>
            </div>
        </div>

        <!-- Main Chat -->
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
                <select id="modelSelect" class="model-select" onchange="changeModel(this.value)" title="Switch AI model">
                    <option value="">Loading...</option>
                </select>
                <button onclick="toggleSettings()" class="text-white/60 hover:text-white/90 transition-colors text-base p-1 hidden md:flex" title="Settings">
                    <i class="fas fa-sliders-h"></i>
                </button>
                <button onclick="toggleDarkMode()" class="text-white/60 hover:text-white/90 transition-colors text-lg p-1 hidden md:flex">
                    <i class="fas" id="darkModeIconDesktop">&#xf185;</i>
                </button>
            </div>

            <!-- Settings Panel -->
            <div id="settingsPanel" class="settings-panel glass px-4 py-3 border-b border-white/10 dark:border-white/5 flex-col gap-3">
                <div>
                    <label class="text-white/60 text-xs font-medium block mb-1">System Prompt</label>
                    <textarea id="systemPromptInput" rows="2" class="edit-input" placeholder="Custom instructions for the AI..." style="resize:vertical;min-height:50px;"><?= esc($currentPrompt ?? '') ?></textarea>
                </div>
                <div class="flex gap-2">
                    <button onclick="savePrompt()" class="glass-card text-white/80 rounded-lg py-1.5 px-4 text-xs font-medium hover:bg-white/10 transition-all">Save Prompt</button>
                    <button onclick="exportChat()" class="glass-card text-white/80 rounded-lg py-1.5 px-4 text-xs font-medium hover:bg-white/10 transition-all flex items-center gap-1.5">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="messagesContainer" class="flex-1 overflow-y-auto px-4 md:px-6 py-4 relative">
                <div id="messages" class="space-y-3">
                    <?php if (empty($messages)): ?>
                        <div id="welcomeScreen" class="flex flex-col items-center justify-center py-8 text-center">
                            <div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mb-4 border border-white/15">
                                <i class="fas fa-robot text-white/70 text-3xl"></i>
                            </div>
                            <h2 class="text-white/90 text-xl font-semibold mb-1">Hello! I'm AI ChatBot</h2>
                            <p class="text-white/50 text-sm max-w-xs">Ask me anything — I'm here to help!</p>
                            <div class="flex flex-wrap gap-2 justify-center mt-6 suggestions-row" style="max-width:400px;">
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
                            <div class="flex items-end gap-2 <?= $alignClass ?> msg-enter" data-msg-id="<?= $msg->id ?>">
                                <?php if (! $isUser): ?>
                                    <div class="w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10">
                                        <i class="fas fa-robot text-white/60 text-xs"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="relative group max-w-[75%] md:max-w-[65%]">
                                    <div class="<?= $bubbleClass ?> px-4 py-2.5 text-sm leading-relaxed">
                                        <?php if ($isUser): ?>
                                            <span class="msg-text"><?= esc($msg->message) ?></span>
                                            <div class="edit-msg-wrap hidden mt-2">
                                                <textarea class="edit-input edit-msg-text" rows="2"><?= esc($msg->message) ?></textarea>
                                                <div class="flex gap-1 mt-1">
                                                    <button onclick="saveEdit(this)" class="text-white/70 text-xs hover:text-white transition-colors">Save</button>
                                                    <button onclick="cancelEdit(this)" class="text-white/40 text-xs hover:text-white/70 transition-colors">Cancel</button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="markdown-content"><?= esc($msg->message) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex gap-1.5 absolute -bottom-5 right-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <?php if ($isUser): ?>
                                            <button onclick="editMessage(this)" class="text-white/30 hover:text-white/70 transition-colors text-xs">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="speakMessage(this)" class="speaker-btn text-white/30 hover:text-white/70 transition-colors text-xs">
                                                <i class="fas fa-volume-up"></i>
                                            </button>
                                            <button onclick="copyMessage(this)" class="text-white/30 hover:text-white/70 transition-colors text-xs">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-[10px] text-white/30 mt-0.5 <?= $timeAlign ?>" title="<?= $timeTitle ?>"><?= $timeDisplay ?></div>
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

            <button id="scrollBottom" onclick="scrollToBottom()"><i class="fas fa-chevron-down"></i></button>

            <!-- Input Area -->
            <div class="glass px-4 py-3 border-t border-white/10 dark:border-white/5" style="border-radius: 0 0 23px 23px;">
                <div class="flex gap-2 items-end">
                    <button id="uploadBtn" onclick="document.getElementById('fileInput').click()" style="width:40px;height:40px;min-width:40px;border-radius:12px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" title="Upload file">
                        <i class="fas fa-paperclip text-sm"></i>
                    </button>
                    <input type="file" id="fileInput" class="hidden" onchange="handleFileUpload(this)" accept=".txt,.md,.csv,.json,.xml,.html,.css,.js,.php,.py,.rb,.go,.rs,.sql,.sh,.yaml,.yml,.toml,.ini,.cfg,.log">
                    <div class="flex-1 relative">
                        <textarea id="messageInput" rows="1" placeholder="Type your message... (Ctrl+Enter for new line)" oninput="autoResize(this)" onkeydown="handleKeyDown(event)"></textarea>
                    </div>
                    <button id="micBtn" onclick="toggleRecording()" style="width:48px;height:48px;min-width:48px;border-radius:16px;border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;" title="Voice input">
                        <i class="fas fa-microphone"></i>
                    </button>
                    <button id="sendBtn" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
                <div class="flex items-center gap-2 mt-2 overflow-x-auto suggestions-row" style="-webkit-overflow-scrolling:touch;">
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Explain this')">Explain this</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Summarize')">Summarize</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Translate to Spanish')">Translate</span>
                    <span class="chip flex-shrink-0" onclick="sendSuggestion('Write code')">Write code</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php
            $modelNames = [];
            foreach ($availableModels as $m) {
                $modelNames[] = $m['name'];
            }
        ?>
        var sendUrl = <?= json_encode($sendUrl) ?>;
        var initialConversationId = <?= json_encode($conversationId) ?>;
        var baseUrl = sendUrl.replace('/send', '');
        var availableModels = <?= json_encode($modelNames) ?>;
        var currentModel = <?= json_encode($currentModel) ?>;

        var activeConversationId = initialConversationId;
        var isSending = false;
        var isDarkMode = localStorage.getItem('chatbot-dark-mode') === 'true';
        var useStreaming = true;

        var messagesEl = document.getElementById('messages');
        var messagesContainer = document.getElementById('messagesContainer');
        var sidebar = document.getElementById('sidebar');
        var messageInput = document.getElementById('messageInput');
        var sendBtn = document.getElementById('sendBtn');
        var typingIndicator = document.getElementById('typingIndicator');
        var scrollBottomBtn = document.getElementById('scrollBottom');
        var conversationList = document.getElementById('conversationList');
        var welcomeScreen = document.getElementById('welcomeScreen');
        var statusDot = document.getElementById('statusDot');
        var statusText = document.getElementById('statusText');
        var toast = document.getElementById('toast');
        var modelSelect = document.getElementById('modelSelect');

        if (isDarkMode) {
            document.body.classList.add('dark');
            updateDarkModeIcons();
        }

        // Init
        populateModels();
        applyMarkdownToExisting();
        checkStatus();
        loadConversations();

        messagesContainer.addEventListener('scroll', function() {
            var diff = this.scrollHeight - this.scrollTop - this.clientHeight;
            scrollBottomBtn.classList.toggle('show', diff > 300);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+K: New conversation
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                newConversation();
            }
            // Ctrl+L: Focus input
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                messageInput.focus();
            }
            // Escape: Close sidebar / stop speaking
            if (e.key === 'Escape') {
                if (sidebar.classList.contains('show-sidebar')) {
                    toggleSidebar();
                }
                window.speechSynthesis.cancel();
                document.querySelectorAll('.speaker-btn.speaking').forEach(function(el) {
                    el.classList.remove('speaking');
                });
            }
            // Ctrl+Shift+C: Copy last bot message
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
                e.preventDefault();
                var botMessages = document.querySelectorAll('.bubble-bot .markdown-content');
                if (botMessages.length > 0) {
                    var last = botMessages[botMessages.length - 1];
                    navigator.clipboard.writeText(last.textContent.trim()).then(function() {
                        showToast('Copied last response');
                    });
                }
            }
        });

        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 2000);
        }

        function populateModels() {
            if (availableModels.length === 0) {
                modelSelect.innerHTML = '<option value="">No models</option>';
                return;
            }
            modelSelect.innerHTML = availableModels.map(function(m) {
                var sel = m === currentModel ? 'selected' : '';
                return '<option value="' + m + '" ' + sel + '>' + m + '</option>';
            }).join('');
        }

        function changeModel(val) {
            currentModel = val;
            if (activeConversationId) {
                // Save model preference to conversation
                var formData = new FormData();
                formData.append('model', val);
                fetch(baseUrl + '/send', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(function(){});
            }
            showToast('Model: ' + val);
        }

        function applyMarkdownToExisting() {
            document.querySelectorAll('.markdown-content').forEach(function(el) {
                el.innerHTML = renderMarkdown(el.textContent);
            });
            if (typeof hljs !== 'undefined') {
                document.querySelectorAll('.bubble-bot pre code').forEach(function(block) {
                    hljs.highlightElement(block);
                });
            }
        }

        function toggleDarkMode() {
            isDarkMode = !isDarkMode;
            document.body.classList.toggle('dark', isDarkMode);
            localStorage.setItem('chatbot-dark-mode', isDarkMode);
            updateDarkModeIcons();
        }

        function updateDarkModeIcons() {
            var icon = isDarkMode ? 'fa-sun' : 'fa-moon';
            var label = isDarkMode ? 'Light Mode' : 'Dark Mode';
            document.getElementById('darkModeIcon').className = 'fas ' + icon;
            document.getElementById('darkModeIconDesktop').className = 'fas ' + icon;
            document.getElementById('darkModeLabel').textContent = label;
        }

        function toggleSidebar() {
            sidebar.classList.toggle('hidden-sidebar');
            sidebar.classList.toggle('show-sidebar');
        }

        // Settings
        function toggleSettings() {
            var panel = document.getElementById('settingsPanel');
            panel.classList.toggle('open');
        }

        function savePrompt() {
            var prompt = document.getElementById('systemPromptInput').value;
            if (!activeConversationId) {
                showToast('Start a conversation first');
                return;
            }
            var formData = new FormData();
            formData.append('system_prompt', prompt);
            fetch(baseUrl + '/prompt/' + activeConversationId, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) showToast('System prompt saved');
            });
        }

        async function checkStatus() {
            try {
                var res = await fetch(baseUrl + '/status', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await res.json();
                statusDot.className = 'w-2 h-2 rounded-full ' + (data.available ? 'bg-green-400' : 'bg-red-400') + ' inline-block';
                statusText.textContent = data.available ? 'Online' : 'Offline';
            } catch {
                statusDot.className = 'w-2 h-2 rounded-full bg-red-400 inline-block';
                statusText.textContent = 'Offline';
            }
        }

        // Conversations
        async function loadConversations() {
            try {
                var res = await fetch(baseUrl + '/conversations', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await res.json();
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
                var title = (c.title && c.title !== 'New Conversation') ? escHtml(c.title) : 'Chat ' + c.id;
                return '<div class="glass-card rounded-xl px-3 py-2.5 cursor-pointer transition-all hover:bg-white/5 ' + active + '" onclick="switchConversation(' + c.id + ')">' +
                    '<div class="flex items-center justify-between">' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="text-white/80 text-sm truncate">' + title + '</div>' +
                    '<div class="text-white/30 text-xs">' + (c.model ? c.model + ' · ' : '') + c.messages + ' messages</div>' +
                    '</div>' +
                    '<div class="flex gap-1 flex-shrink-0">' +
                    '<button onclick="event.stopPropagation(); renameConversation(' + c.id + ')" class="text-white/20 hover:text-white/60 transition-colors text-xs"><i class="fas fa-pen"></i></button>' +
                    '<button onclick="event.stopPropagation(); exportConversation(' + c.id + ')" class="text-white/20 hover:text-white/60 transition-colors text-xs"><i class="fas fa-download"></i></button>' +
                    '<button onclick="event.stopPropagation(); deleteConversation(' + c.id + ')" class="text-white/20 hover:text-red-400 transition-colors text-xs"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function renameConversation(id) {
            var newTitle = prompt('Rename conversation:');
            if (!newTitle || !newTitle.trim()) return;
            var formData = new FormData();
            formData.append('title', newTitle.trim());
            fetch(baseUrl + '/rename/' + id, {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(d) {
                if (d.success) loadConversations();
            });
        }

        function exportConversation(id) {
            window.open(baseUrl + '/export/' + id + '?format=md', '_blank');
        }

        async function newConversation() {
            try {
                var formData = new FormData();
                if (currentModel) formData.append('model', currentModel);
                var res = await fetch(baseUrl + '/new', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await res.json();
                activeConversationId = data.id;
                clearMessages();
                showWelcome();
                loadConversations();
                if (window.innerWidth <= 768) toggleSidebar();
                messageInput.focus();
            } catch {}
        }

        async function switchConversation(id) {
            if (id === activeConversationId) return;
            activeConversationId = id;
            try {
                var res = await fetch(baseUrl + '/load/' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                var data = await res.json();
                clearMessages();
                if (data.messages && data.messages.length > 0) {
                    hideWelcome();
                    data.messages.forEach(function(m) {
                        appendMessage(m.message, m.role === 'user', m.created_at, m.id);
                    });
                    applyMarkdownToExisting();
                } else {
                    showWelcome();
                }
                // Update model selector
                if (data.conversation && data.conversation.model) {
                    currentModel = data.conversation.model;
                    if (modelSelect) modelSelect.value = currentModel;
                }
                // Update system prompt
                if (data.conversation && data.conversation.system_prompt) {
                    document.getElementById('systemPromptInput').value = data.conversation.system_prompt;
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
                    method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (activeConversationId === id) {
                    activeConversationId = null;
                    clearMessages();
                    showWelcome();
                }
                loadConversations();
            } catch {}
        }

        // Messages
        async function sendMessage() {
            var message = messageInput.value.trim();
            if (!message || isSending) return;

            isSending = true;
            sendBtn.disabled = true;

            hideWelcome();
            var tempMsgId = Date.now();
            appendMessage(message, true, null, tempMsgId);
            messageInput.value = '';
            autoResize(messageInput);

            if (useStreaming) {
                showTyping();
                scrollToBottom();
                await sendStreaming(message);
            } else {
                showTyping();
                scrollToBottom();
                await sendNormal(message);
            }

            isSending = false;
            sendBtn.disabled = false;
            scrollToBottom();
            loadConversations();
        }

        async function sendNormal(message) {
            try {
                var formData = new FormData();
                formData.append('message', message);
                formData.append('stream', '0');
                if (activeConversationId) formData.append('conversation_id', activeConversationId);
                if (currentModel) formData.append('model', currentModel);
                var prompt = document.getElementById('systemPromptInput').value;
                if (prompt) formData.append('system_prompt', prompt);

                var res = await fetch(sendUrl, {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await res.json();
                hideTyping();

                if (data.reply) {
                    if (!activeConversationId && data.conversation_id) activeConversationId = data.conversation_id;
                    appendMessage(data.reply, false);
                }
            } catch {
                hideTyping();
                appendMessage('Sorry, something went wrong.', false);
            }
        }

        async function sendStreaming(message) {
            var botMsgDiv = null;
            var botBubble = null;

            try {
                var formData = new FormData();
                formData.append('message', message);
                formData.append('stream', '1');
                if (activeConversationId) formData.append('conversation_id', activeConversationId);
                if (currentModel) formData.append('model', currentModel);
                var prompt = document.getElementById('systemPromptInput').value;
                if (prompt) formData.append('system_prompt', prompt);

                var res = await fetch(sendUrl, {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                hideTyping();

                var reader = res.body.getReader();
                var decoder = new TextDecoder();
                var fullText = '';
                var isFirstChunk = true;

                while (true) {
                    var result = await reader.read();
                    if (result.done) break;

                    var text = decoder.decode(result.value, { stream: true });
                    var lines = text.split('\n');

                    for (var i = 0; i < lines.length; i++) {
                        var line = lines[i].trim();
                        if (!line || !line.startsWith('data: ')) continue;
                        try {
                            var data = JSON.parse(line.substring(6));
                            if (data.chunk) {
                                fullText += data.chunk;
                                if (isFirstChunk) {
                                    botMsgDiv = appendStreamingMessage(fullText);
                                    botBubble = botMsgDiv.querySelector('.bubble-bot .markdown-content');
                                    isFirstChunk = false;
                                } else if (botBubble) {
                                    botBubble.innerHTML = renderMarkdown(escHtml(fullText));
                                }
                                scrollToBottom();
                            }
                            if (data.done && data.conversation_id) {
                                activeConversationId = data.conversation_id;
                                // Re-render with highlight.js
                                if (botBubble) {
                                    botBubble.innerHTML = renderMarkdown(escHtml(fullText));
                                    botBubble.closest('.bubble-bot')?.classList.remove('stream-cursor');
                                    botBubble.querySelectorAll('pre code').forEach(function(b) {
                                        if (typeof hljs !== 'undefined') hljs.highlightElement(b);
                                    });
                                }
                            }
                        } catch(e) {}
                    }
                }
            } catch {
                hideTyping();
                if (!botMsgDiv) appendMessage('Sorry, something went wrong.', false);
            }
        }

        function appendMessage(text, isUser, createdAt, msgId) {
            var div = document.createElement('div');
            div.className = 'flex items-end gap-2 ' + (isUser ? 'justify-end' : 'justify-start') + ' msg-enter';
            if (msgId) div.dataset.msgId = msgId;

            if (!isUser) {
                var avatar = document.createElement('div');
                avatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
                avatar.innerHTML = '<i class="fas fa-robot text-white/60 text-xs"></i>';
                div.appendChild(avatar);
            }

            var bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'relative group max-w-[75%] md:max-w-[65%]';

            var bubble = document.createElement('div');
            bubble.className = (isUser ? 'bubble-user' : 'bubble-bot') + ' px-4 py-2.5 text-sm leading-relaxed';

            if (isUser) {
                bubble.innerHTML = '<span class="msg-text">' + escHtml(text) + '</span>' +
                    '<div class="edit-msg-wrap hidden mt-2">' +
                    '<textarea class="edit-input edit-msg-text" rows="2">' + escHtml(text) + '</textarea>' +
                    '<div class="flex gap-1 mt-1">' +
                    '<button onclick="saveEdit(this)" class="text-white/70 text-xs hover:text-white transition-colors">Save</button>' +
                    '<button onclick="cancelEdit(this)" class="text-white/40 text-xs hover:text-white/70 transition-colors">Cancel</button>' +
                    '</div></div>';
            } else {
                var md = document.createElement('div');
                md.className = 'markdown-content';
                md.innerHTML = renderMarkdown(escHtml(text));
                bubble.appendChild(md);
            }

            bubbleWrap.appendChild(bubble);

            var actionRow = document.createElement('div');
            actionRow.className = 'flex gap-1.5 absolute -bottom-5 right-0 opacity-0 group-hover:opacity-100 transition-opacity';

            if (isUser) {
                var editBtn = document.createElement('button');
                editBtn.onclick = function() { editMessage(this); };
                editBtn.className = 'text-white/30 hover:text-white/70 transition-colors text-xs';
                editBtn.innerHTML = '<i class="fas fa-pen"></i>';
                actionRow.appendChild(editBtn);
            } else {
                var speakBtn = document.createElement('button');
                speakBtn.onclick = function() { speakMessage(this); };
                speakBtn.className = 'speaker-btn text-white/30 hover:text-white/70 transition-colors text-xs';
                speakBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
                actionRow.appendChild(speakBtn);

                var copyBtn = document.createElement('button');
                copyBtn.onclick = function() { copyMessage(this); };
                copyBtn.className = 'text-white/30 hover:text-white/70 transition-colors text-xs';
                copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
                actionRow.appendChild(copyBtn);
            }
            bubbleWrap.appendChild(actionRow);

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

        function appendStreamingMessage(text) {
            var div = document.createElement('div');
            div.className = 'flex items-end gap-2 justify-start msg-enter';

            var avatar = document.createElement('div');
            avatar.className = 'w-7 h-7 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0 border border-white/10';
            avatar.innerHTML = '<i class="fas fa-robot text-white/60 text-xs"></i>';
            div.appendChild(avatar);

            var bubbleWrap = document.createElement('div');
            bubbleWrap.className = 'relative group max-w-[75%] md:max-w-[65%]';

            var bubble = document.createElement('div');
            bubble.className = 'bubble-bot px-4 py-2.5 text-sm leading-relaxed stream-cursor';

            var md = document.createElement('div');
            md.className = 'markdown-content';
            md.innerHTML = renderMarkdown(escHtml(text));
            bubble.appendChild(md);

            bubbleWrap.appendChild(bubble);
            div.appendChild(bubbleWrap);
            messagesEl.appendChild(div);
            scrollToBottom();
            return div;
        }

        function showTyping() { typingIndicator.classList.remove('hidden'); scrollToBottom(); }
        function hideTyping() { typingIndicator.classList.add('hidden'); }
        function clearMessages() { messagesEl.innerHTML = ''; }

        function showWelcome() {
            var existing = document.getElementById('welcomeScreen');
            if (existing) { existing.style.display = ''; return; }
            var ws = document.createElement('div');
            ws.id = 'welcomeScreen';
            ws.className = 'flex flex-col items-center justify-center py-8 text-center';
            ws.innerHTML = '<div class="w-20 h-20 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mb-4 border border-white/15"><i class="fas fa-robot text-white/70 text-3xl"></i></div>' +
                '<h2 class="text-white/90 text-xl font-semibold mb-1">Hello! I\'m AI ChatBot</h2>' +
                '<p class="text-white/50 text-sm max-w-xs">Ask me anything — I\'m here to help!</p>' +
                '<div class="flex flex-wrap gap-2 justify-center mt-6 suggestions-row" style="max-width:400px;">' +
                '<span class="chip" onclick="sendSuggestion(\'Tell me a joke\')">Tell me a joke</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Write a poem\')">Write a poem</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Explain quantum computing simply\')">Explain quantum computing</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Give me productivity tips\')">Productivity tips</span>' +
                '<span class="chip" onclick="sendSuggestion(\'Help me write SQL\')">Help me write SQL</span>' +
                '<span class="chip" onclick="sendSuggestion(\'What is the meaning of life?\')">Meaning of life</span>' +
                '</div>';
            messagesEl.appendChild(ws);
            scrollToBottom();
        }

        function hideWelcome() { var ws = document.getElementById('welcomeScreen'); if (ws) ws.style.display = 'none'; }

        function sendSuggestion(text) { messageInput.value = text; sendMessage(); }

        function autoResize(textarea) { textarea.style.height = 'auto'; textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px'; }

        function handleKeyDown(e) {
            if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) { e.preventDefault(); sendMessage(); }
        }

        function scrollToBottom() { setTimeout(function() { messagesContainer.scrollTop = messagesContainer.scrollHeight; }, 50); }

        function copyMessage(btn) {
            var bubbleWrap = btn.closest('.relative');
            var text = bubbleWrap ? (bubbleWrap.querySelector('.markdown-content')?.textContent || '') : '';
            navigator.clipboard.writeText(text.trim()).then(function() { showToast('Copied!'); });
        }

        // Message editing
        function editMessage(btn) {
            var bubbleWrap = btn.closest('.relative');
            var msgText = bubbleWrap.querySelector('.msg-text');
            var editWrap = bubbleWrap.querySelector('.edit-msg-wrap');
            if (msgText) msgText.classList.add('hidden');
            if (editWrap) editWrap.classList.remove('hidden');
        }

        function cancelEdit(btn) {
            var bubbleWrap = btn.closest('.relative');
            var msgText = bubbleWrap.querySelector('.msg-text');
            var editWrap = bubbleWrap.querySelector('.edit-msg-wrap');
            if (msgText) msgText.classList.remove('hidden');
            if (editWrap) editWrap.classList.add('hidden');
        }

        function saveEdit(btn) {
            var bubbleWrap = btn.closest('.relative');
            var msgDiv = bubbleWrap.closest('[data-msg-id]');
            if (!msgDiv) return;
            var msgId = msgDiv.dataset.msgId;
            var textarea = bubbleWrap.querySelector('.edit-msg-text');
            var newText = textarea.value.trim();
            if (!newText) return;

            var formData = new FormData();
            formData.append('message', newText);

            fetch(baseUrl + '/edit/' + msgId, {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(data) {
                if (data.reply) {
                    // Remove all messages after the edited one
                    var next = msgDiv.nextElementSibling;
                    while (next) {
                        var toRemove = next;
                        next = next.nextElementSibling;
                        toRemove.remove();
                    }
                    // Update this message
                    var msgText = bubbleWrap.querySelector('.msg-text');
                    if (msgText) msgText.textContent = newText;
                    var editWrap = bubbleWrap.querySelector('.edit-msg-wrap');
                    if (editWrap) editWrap.classList.add('hidden');
                    if (msgText) msgText.classList.remove('hidden');
                    // Append new bot response
                    appendMessage(data.reply, false);
                    applyMarkdownToExisting();
                }
            });
        }

        // File upload
        function handleFileUpload(input) {
            var file = input.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('file', file);

            showToast('Uploading...');

            fetch(baseUrl + '/upload', {
                method: 'POST', body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function(r) { return r.json(); }).then(function(data) {
                var content = '```' + (file.name.split('.').pop() || '') + '\n' + data.content + '\n```';
                var msg = 'Here is the file **' + data.filename + '** (' + data.size + ' bytes):\n\n' + content;
                messageInput.value = msg;
                autoResize(messageInput);
                showToast('File loaded: ' + data.filename);
            }).catch(function() {
                showToast('Upload failed');
            });

            input.value = '';
        }

        // Export
        function exportChat() {
            if (activeConversationId) {
                window.open(baseUrl + '/export/' + activeConversationId + '?format=md', '_blank');
            } else {
                showToast('No active conversation');
            }
        }

        // Markdown
        function renderMarkdown(text) {
            text = text.replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold mt-3 mb-1">$1</h3>');
            text = text.replace(/^## (.+)$/gm, '<h2 class="text-lg font-semibold mt-3 mb-1">$1</h2>');
            text = text.replace(/^# (.+)$/gm, '<h1 class="text-xl font-semibold mt-3 mb-1">$1</h1>');
            text = text.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code class="language-$1">$2</code></pre>');
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

        // Voice
        var recognition = null;
        var isRecording = false;
        var micBtn = document.getElementById('micBtn');
        var SpeechRecognitionAPI = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognitionAPI) micBtn.style.display = 'none';

        function toggleRecording() {
            if (isRecording) { stopRecording(); return; }
            startRecording();
        }

        function startRecording() {
            if (!SpeechRecognitionAPI) return;
            if (recognition) recognition.abort();
            recognition = new SpeechRecognitionAPI();
            recognition.lang = 'en-US';
            recognition.interimResults = true;
            recognition.continuous = true;
            recognition.onresult = function(event) {
                var transcript = '';
                for (var i = event.resultIndex; i < event.results.length; i++) {
                    transcript += event.results[i][0].transcript;
                }
                messageInput.value = transcript;
                autoResize(messageInput);
            };
            recognition.onerror = function(event) {
                stopRecording();
                if (event.error === 'not-allowed') showToast('Microphone access denied');
            };
            recognition.onend = function() {
                if (messageInput.value.trim()) sendMessage();
                stopRecording();
            };
            try {
                recognition.start();
                isRecording = true;
                micBtn.classList.add('recording');
                micBtn.innerHTML = '<i class="fas fa-stop"></i>';
                micBtn.title = 'Stop recording';
            } catch(e) {}
        }

        function stopRecording() {
            if (recognition) { try { recognition.stop(); } catch(e) {} recognition = null; }
            isRecording = false;
            micBtn.classList.remove('recording');
            micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            micBtn.title = 'Voice input';
        }

        function speakMessage(btn) {
            var bubbleWrap = btn.closest('.relative');
            var textEl = bubbleWrap ? bubbleWrap.querySelector('.markdown-content') : null;
            if (!textEl) return;
            var text = textEl.textContent.trim();
            if (!text) return;
            if (btn.classList.contains('speaking')) {
                window.speechSynthesis.cancel();
                btn.classList.remove('speaking');
                return;
            }
            window.speechSynthesis.cancel();
            document.querySelectorAll('.speaker-btn.speaking').forEach(function(el) { el.classList.remove('speaking'); });
            var utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'en-US';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.onstart = function() { btn.classList.add('speaking'); };
            utterance.onend = function() { btn.classList.remove('speaking'); };
            utterance.onerror = function() { btn.classList.remove('speaking'); };
            window.speechSynthesis.speak(utterance);
        }

        // Helpers
        function escHtml(str) { var d = document.createElement('div'); d.textContent = str; return d.innerHTML; }
        function timeAgo(dateStr) { return timeAgoFromDate(new Date(dateStr.replace(' ', 'T') + 'Z')); }
        function timeAgoFromDate(date) {
            var diff = Math.floor((new Date() - date) / 1000);
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return date.toLocaleDateString();
        }
    </script>
</body>
</html>
