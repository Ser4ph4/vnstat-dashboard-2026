<?php
require_once 'config.php';

// Se já estiver logado, vai para o painel
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $path = __DIR__ . '/pass/credentials.php';
    
    if (!file_exists($path)) {
        $error = "Erro: Arquivo de credenciais não encontrado.";
    } else {
        $credentials = require $path;
        $user_input = $_POST['username'] ?? '';
        $pass_input = $_POST['password'] ?? '';
        
        // Validação usando o hash do seu credentials.php
        if ($user_input === $credentials['username'] && password_verify($pass_input, $credentials['password_hash'])) {
            $_SESSION['loggedin'] = true;
            session_write_close(); 
            header('Location: index.php');
            exit;
        } else {
            $error = "Usuário ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VnStat Dashboard</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
        integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Tailwind CSS -->
    <script src="libs/tailwind.min.js"></script>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

    * {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    body {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        min-height: 100vh;
    }

    .glass-effect {
        background: rgba(15, 23, 42, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(148, 163, 184, 0.1);
    }

    .gradient-text {
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-20px);
        }
    }

    .floating-icon {
        animation: float 3s ease-in-out infinite;
    }

    @keyframes pulse-slow {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .pulse-slow {
        animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .input-focus:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    @keyframes shake {

        0%,
        100% {
            transform: translateX(0);
        }

        10%,
        30%,
        50%,
        70%,
        90% {
            transform: translateX(-5px);
        }

        20%,
        40%,
        60%,
        80% {
            transform: translateX(5px);
        }
    }

    .shake {
        animation: shake 0.5s;
    }

    .bg-particles {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        z-index: 0;
        overflow: hidden;
    }

    .particle {
        position: absolute;
        background: rgba(59, 130, 246, 0.2);
        border-radius: 50%;
        animation: particle-float 20s infinite;
    }

    @keyframes particle-float {
        0% {
            transform: translateY(100vh) translateX(0);
            opacity: 0;
        }

        10% {
            opacity: 0.3;
        }

        90% {
            opacity: 0.3;
        }

        100% {
            transform: translateY(-100vh) translateX(100px);
            opacity: 0;
        }
    }
    </style>
</head>

<body class="bg-slate-900">
    <!-- Particles Background -->
    <div class="bg-particles">
        <div class="particle" style="width: 4px; height: 4px; left: 10%; animation-delay: 0s;"></div>
        <div class="particle" style="width: 6px; height: 6px; left: 20%; animation-delay: 2s;"></div>
        <div class="particle" style="width: 3px; height: 3px; left: 30%; animation-delay: 4s;"></div>
        <div class="particle" style="width: 5px; height: 5px; left: 40%; animation-delay: 1s;"></div>
        <div class="particle" style="width: 4px; height: 4px; left: 50%; animation-delay: 3s;"></div>
        <div class="particle" style="width: 6px; height: 6px; left: 60%; animation-delay: 5s;"></div>
        <div class="particle" style="width: 3px; height: 3px; left: 70%; animation-delay: 2.5s;"></div>
        <div class="particle" style="width: 5px; height: 5px; left: 80%; animation-delay: 4.5s;"></div>
        <div class="particle" style="width: 4px; height: 4px; left: 90%; animation-delay: 1.5s;"></div>
    </div>

    <!-- Login Container -->
    <div class="min-h-screen flex items-center justify-center p-4 relative z-10">
        <div class="w-full max-w-md">
            <!-- Logo/Icon -->
            <div class="text-center mb-8">
                <div class="inline-block floating-icon">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-2xl shadow-blue-500/50 mb-4">
                        <i class="fas fa-chart-line text-4xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-4xl font-bold text-white mb-2">
                    VnStat <span class="gradient-text">Dashboard</span>
                </h1>
                <p class="text-slate-400 text-sm">
                    <i class="fas fa-lock mr-2"></i>
                    Sistema de Monitoramento de Rede
                </p>
            </div>

            <!-- Login Box -->
            <div class="glass-effect rounded-2xl p-8 shadow-2xl <?php echo $error ? 'shake' : ''; ?>">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-white mb-2">
                        <i class="fas fa-sign-in-alt mr-2 text-blue-400"></i>
                        Bem-vindo de volta
                    </h2>
                    <p class="text-slate-400 text-sm">Entre com suas credenciais para acessar</p>
                </div>

                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-400 text-xl mt-0.5"></i>
                        <div>
                            <h3 class="text-red-400 font-semibold mb-1">Erro de autenticação</h3>
                            <p class="text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="login.php" class="space-y-5" id="loginForm">
                    <!-- Username Input -->
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-medium text-slate-300">
                            <i class="fas fa-user mr-2 text-blue-400"></i>
                            Usuário
                        </label>
                        <div class="relative">
                            <input type="text" id="username" name="username" required autofocus
                                class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 transition-all input-focus"
                                placeholder="Digite seu usuário">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <i class="fas fa-user-circle text-slate-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-slate-300">
                            <i class="fas fa-lock mr-2 text-purple-400"></i>
                            Senha
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-white placeholder-slate-500 transition-all input-focus pr-12"
                                placeholder="Digite sua senha">
                            <button type="button" onclick="togglePassword()"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition-colors">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me (opcional) -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox"
                                class="w-4 h-4 text-blue-600 bg-slate-800 border-slate-700 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-slate-400">Lembrar-me</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                        class="w-full py-3 px-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-lg shadow-lg shadow-blue-500/50 transition-all transform hover:scale-105 active:scale-95">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Entrar no Dashboard
                    </button>
                </form>

                <!-- Footer Info -->
                <div class="mt-6 pt-6 border-t border-slate-700">
                    <div class="flex items-center justify-center gap-2 text-xs text-slate-500">
                        <i class="fas fa-shield-alt pulse-slow text-green-400"></i>
                        <span>Conexão segura e criptografada</span>
                    </div>
                </div>
            </div>

            <!-- Bottom Info -->
            <div class="mt-6 text-center">
                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1">
                        <i class="fas fa-server text-blue-400"></i>
                        VnStat v2.10
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-code text-purple-400"></i>
                        Dashboard v2.0
                    </span>
                    <span>•</span>
                    <span class="flex items-center gap-1">
                        <i class="fas fa-copyright text-slate-600"></i>
                        <?php echo date('Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }

    // Auto-focus no campo de usuário ao carregar
    document.addEventListener('DOMContentLoaded', function() {
        const usernameInput = document.getElementById('username');
        if (usernameInput) {
            usernameInput.focus();
        }

        // Adicionar efeito de loading ao submeter
        const loginForm = document.getElementById('loginForm');
        loginForm.addEventListener('submit', function(e) {
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Entrando...';
            submitBtn.disabled = true;
        });
    });

    // Tecla Enter para submeter
    document.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('loginForm').submit();
        }
    });

    // Mostrar/ocultar erro com animação
    <?php if ($error): ?>
    setTimeout(function() {
        const errorBox = document.querySelector('.shake');
        if (errorBox) {
            errorBox.classList.remove('shake');
        }
    }, 500);
    <?php endif; ?>
    </script>
</body>


</html>
