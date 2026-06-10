<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - <?= esc($settings->app_name ?? 'AI ChatBot') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .glass-card:hover { background: rgba(255, 255, 255, 0.18); }
    </style>
</head>
<body>
    <div class="glass rounded-2xl p-8 w-full max-w-lg">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 rounded-full bg-white/15 flex items-center justify-center">
                <i class="fas fa-crown text-white/80 text-xl"></i>
            </div>
            <div>
                <h1 class="text-white font-semibold text-lg">Admin Dashboard</h1>
                <p class="text-white/50 text-sm"><?= esc($settings->app_name ?? 'AI ChatBot') ?></p>
            </div>
        </div>

        <div class="grid gap-3">
            <a href="<?= base_url('admin/settings') ?>" class="glass-card rounded-xl px-5 py-4 text-white/80 flex items-center gap-3 transition-all hover:bg-white/10">
                <i class="fas fa-paint-brush text-white/60 w-6"></i>
                <div>
                    <div class="font-medium text-sm">Branding Settings</div>
                    <div class="text-white/40 text-xs">Logo, colors, app name, footer</div>
                </div>
                <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
            </a>
            <a href="<?= base_url('chatbot') ?>" class="glass-card rounded-xl px-5 py-4 text-white/80 flex items-center gap-3 transition-all hover:bg-white/10">
                <i class="fas fa-comment-dots text-white/60 w-6"></i>
                <div>
                    <div class="font-medium text-sm">Chatbot</div>
                    <div class="text-white/40 text-xs">Return to the chatbot</div>
                </div>
                <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
            </a>
            <a href="<?= base_url('auth/logout') ?>" class="glass-card rounded-xl px-5 py-4 text-red-300/80 flex items-center gap-3 transition-all hover:bg-white/10">
                <i class="fas fa-sign-out-alt text-red-300/60 w-6"></i>
                <div>
                    <div class="font-medium text-sm">Logout</div>
                </div>
                <i class="fas fa-chevron-right text-white/30 ml-auto"></i>
            </a>
        </div>
    </div>
</body>
</html>
