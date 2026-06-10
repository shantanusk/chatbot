<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding Settings - <?= esc($settings->app_name ?? 'AI ChatBot') ?></title>
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
        input, textarea { outline: none; }
        .toast {
            position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(12px); color: white;
            padding: 8px 20px; border-radius: 12px; font-size: 13px;
            transition: transform 0.3s ease; z-index: 100; pointer-events: none;
        }
        .toast.show { transform: translateX(-50%) translateY(0); pointer-events: auto; }
    </style>
</head>
<body>
    <div id="toast" class="toast"></div>

    <div class="glass rounded-2xl p-8 w-full max-w-2xl" style="max-height:90vh;overflow-y:auto;">
        <div class="flex items-center gap-3 mb-6">
            <a href="<?= base_url('admin') ?>" class="text-white/50 hover:text-white/80 transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-white font-semibold text-lg">Branding Settings</h1>
                <p class="text-white/50 text-sm">Customize your chatbot's appearance</p>
            </div>
        </div>

        <form id="settingsForm" class="space-y-5">

            <!-- App Name -->
            <div>
                <label class="text-white/70 text-sm font-medium block mb-1.5">App Name</label>
                <input type="text" name="app_name" value="<?= esc($settings->app_name ?? 'AI ChatBot') ?>" class="w-full glass-card rounded-xl px-4 py-2.5 text-white/90 text-sm">
            </div>

            <!-- Welcome Title + Subtitle -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-white/70 text-sm font-medium block mb-1.5">Welcome Title</label>
                    <input type="text" name="welcome_title" value="<?= esc($settings->welcome_title ?? '') ?>" class="w-full glass-card rounded-xl px-4 py-2.5 text-white/90 text-sm" placeholder="Hello! I'm {app_name}">
                </div>
                <div>
                    <label class="text-white/70 text-sm font-medium block mb-1.5">Welcome Subtitle</label>
                    <input type="text" name="welcome_subtitle" value="<?= esc($settings->welcome_subtitle ?? '') ?>" class="w-full glass-card rounded-xl px-4 py-2.5 text-white/90 text-sm">
                </div>
            </div>

            <!-- Logo + Favicon -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-white/70 text-sm font-medium block mb-1.5">Logo</label>
                    <div class="flex items-center gap-3">
                        <?php if ($settings->logo_path): ?>
                            <img src="<?= base_url($settings->logo_path) ?>" class="w-10 h-10 rounded-lg object-cover border border-white/10">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center"><i class="fas fa-image text-white/40"></i></div>
                        <?php endif; ?>
                        <input type="file" name="logo" accept="image/*" class="text-white/50 text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-white/10 file:text-white/70 hover:file:bg-white/20">
                    </div>
                </div>
                <div>
                    <label class="text-white/70 text-sm font-medium block mb-1.5">Favicon</label>
                    <div class="flex items-center gap-3">
                        <?php if ($settings->favicon_path): ?>
                            <img src="<?= base_url($settings->favicon_path) ?>" class="w-8 h-8 rounded border border-white/10">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded bg-white/10 flex items-center justify-center"><i class="fas fa-globe text-white/40 text-xs"></i></div>
                        <?php endif; ?>
                        <input type="file" name="favicon" accept="image/x-icon,image/png,image/jpeg,image/svg+xml" class="text-white/50 text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-white/10 file:text-white/70 hover:file:bg-white/20">
                    </div>
                </div>
            </div>

            <!-- Gradient Colors -->
            <div>
                <label class="text-white/70 text-sm font-medium block mb-1.5">Gradient Colors</label>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="text-white/40 text-xs block mb-1">Start</label>
                        <input type="color" name="primary_color_start" value="<?= esc($settings->primary_color_start ?? '#667eea') ?>" class="w-full h-10 rounded-xl cursor-pointer border-0">
                    </div>
                    <div>
                        <label class="text-white/40 text-xs block mb-1">Middle</label>
                        <input type="color" name="primary_color_mid" value="<?= esc($settings->primary_color_mid ?? '#764ba2') ?>" class="w-full h-10 rounded-xl cursor-pointer border-0">
                    </div>
                    <div>
                        <label class="text-white/40 text-xs block mb-1">End</label>
                        <input type="color" name="primary_color_end" value="<?= esc($settings->primary_color_end ?? '#f093fb') ?>" class="w-full h-10 rounded-xl cursor-pointer border-0">
                    </div>
                </div>
            </div>

            <!-- Footer Text -->
            <div>
                <label class="text-white/70 text-sm font-medium block mb-1.5">Footer Text</label>
                <input type="text" name="footer_text" value="<?= esc($settings->footer_text ?? '') ?>" class="w-full glass-card rounded-xl px-4 py-2.5 text-white/90 text-sm" placeholder="Powered by AI ChatBot">
            </div>

            <!-- Save -->
            <div class="flex gap-3 pt-2">
                <button type="submit" class="glass-card rounded-xl px-6 py-2.5 text-white/90 text-sm font-medium hover:bg-white/15 transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <a href="<?= base_url('chatbot') ?>" class="glass-card rounded-xl px-6 py-2.5 text-white/70 text-sm font-medium hover:bg-white/10 transition-all flex items-center gap-2">
                    <i class="fas fa-eye"></i> Preview
                </a>
            </div>
        </form>
    </div>

    <script>
        document.getElementById('settingsForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            try {
                var res = await fetch('<?= base_url('admin/settings/save') ?>', {
                    method: 'POST', body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                var data = await res.json();
                if (data.success) {
                    showToast('Settings saved!');
                }
            } catch {}
        });

        function showToast(msg) {
            var toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 2000);
        }
    </script>
</body>
</html>
