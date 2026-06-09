<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatBot - CI4 HMVC</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        #messages { scroll-behavior: smooth; }
        .typing-indicator span { animation: blink 1.4s infinite both; }
        .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
        .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%, 80%, 100% { opacity: 0; } 40% { opacity: 1; } }
    </style>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg bg-white rounded-2xl shadow-xl flex flex-col h-[600px]">
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white p-4 rounded-t-2xl flex items-center gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-robot text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-semibold">ChatBot</h1>
                <p class="text-xs text-blue-200">CI4 + HMVC</p>
            </div>
        </div>

        <div id="messages" class="flex-1 overflow-y-auto p-4 space-y-4">
            <?php if (empty($messages)): ?>
                <div class="flex justify-start">
                    <div class="bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm px-4 py-2.5 max-w-[80%] text-sm">
                        Hello! I'm your chatbot. Ask me anything!
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <?php $isUser = $msg->role === 'user'; ?>
                    <div class="flex <?= $isUser ? 'justify-end' : 'justify-start' ?>">
                        <div class="<?= $isUser ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm' ?> px-4 py-2.5 max-w-[80%] text-sm">
                            <?= esc($msg->message) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div id="typingIndicator" class="hidden px-4 pb-2">
            <div class="flex justify-start">
                <div class="bg-gray-100 rounded-2xl rounded-bl-sm px-4 py-3 flex items-center gap-1 typing-indicator">
                    <span class="w-2 h-2 bg-gray-400 rounded-full inline-block"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full inline-block"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full inline-block"></span>
                </div>
            </div>
        </div>

        <div class="border-t p-4">
            <form id="chatForm" class="flex gap-2">
                <input type="text" id="messageInput" autocomplete="off" placeholder="Type your message..."
                    class="flex-1 border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white rounded-xl px-5 py-2.5 text-sm transition-colors">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        const messagesContainer = document.getElementById('messages');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const typingIndicator = document.getElementById('typingIndicator');
        const sendUrl = <?= json_encode($sendUrl) ?>;

        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const message = messageInput.value.trim();
            if (!message) return;

            appendMessage(message, true);
            messageInput.value = '';
            typingIndicator.classList.remove('hidden');
            scrollToBottom();

            try {
                const formData = new FormData();
                formData.append('message', message);

                const response = await fetch(sendUrl, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                typingIndicator.classList.add('hidden');

                if (data.reply) {
                    appendMessage(data.reply, false);
                }
            } catch (err) {
                typingIndicator.classList.add('hidden');
                appendMessage('Sorry, something went wrong. Please try again.', false);
            }

            scrollToBottom();
        });

        function appendMessage(text, isUser) {
            const div = document.createElement('div');
            div.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');

            const bubble = document.createElement('div');
            bubble.className = (isUser ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm' : 'bg-gray-100 text-gray-800 rounded-2xl rounded-bl-sm') + ' px-4 py-2.5 max-w-[80%] text-sm';
            bubble.textContent = text;

            div.appendChild(bubble);
            messagesContainer.appendChild(div);
        }

        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        scrollToBottom();
    </script>
</body>
</html>
