<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AI ChatBot</title>
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
        .glass {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .glass-strong {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 14px;
            outline: none;
            width: 100%;
            transition: border-color 0.2s;
        }
        input:focus { border-color: rgba(255, 255, 255, 0.4); }
        input::placeholder { color: rgba(255, 255, 255, 0.4); }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }
        .btn-primary:hover { transform: scale(1.02); box-shadow: 0 0 20px rgba(102, 126, 234, 0.4); }
    </style>
</head>
<body>
    <div class="glass-strong rounded-3xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-full bg-white/10 backdrop-blur-xl flex items-center justify-center mx-auto mb-4 border border-white/15">
                <i class="fas fa-robot text-white/70 text-2xl"></i>
            </div>
            <h1 class="text-white/90 text-2xl font-semibold">Welcome Back</h1>
            <p class="text-white/50 text-sm mt-1">Sign in to your account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="glass-card rounded-xl px-4 py-3 mb-4 flex items-center gap-2">
                <i class="fas fa-exclamation-circle text-red-400 text-sm"></i>
                <span class="text-white/80 text-sm"><?= esc($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <div>
                <label class="text-white/60 text-xs font-medium block mb-1.5">Username or Email</label>
                <input type="text" name="username" placeholder="Enter your username or email" required autofocus>
            </div>
            <div>
                <label class="text-white/60 text-xs font-medium block mb-1.5">Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-primary">Sign In</button>
        </form>

        <p class="text-center mt-6 text-white/40 text-sm">
            Don't have an account?
            <a href="<?= base_url('auth/register') ?>" class="text-white/70 hover:text-white transition-colors">Create one</a>
        </p>
    </div>
</body>
</html>
