<?php
session_start(); 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Login</title>
    <link rel="icon" href="colog.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-pattern {
            background-image: 
                linear-gradient(135deg, rgba(59, 130, 246, 0.1) 25%, transparent 25%),
                linear-gradient(-135deg, rgba(59, 130, 246, 0.1) 25%, transparent 25%),
                linear-gradient(45deg, rgba(59, 130, 246, 0.05) 25%, transparent 25%),
                linear-gradient(-45deg, rgba(59, 130, 246, 0.05) 25%, transparent 25%);
            background-size: 30px 30px;
            background-position: 0 0, 0 15px, 15px -15px, -15px 0px;
        }
        
        .login-container {
            min-height: calc(100vh - 60px);
        }
        
        .form-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .login-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="bg-gray-100 bg-pattern">
    <div class="login-container flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="bg-blue-600 text-white p-6 rounded-t-lg">
                    <h1 class="text-2xl font-bold mb-2">Painel Administrativo</h1>
                    <h2 class="text-lg font-semibold">COLOG</h2>
                    <p class="text-blue-100 text-sm mt-2">Comando Logístico</p>
                </div>
            </div>

            <!-- Login Form -->
            <div class="login-card rounded-b-lg shadow-xl p-8 border border-gray-200">
                <div class="text-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">Acesso ao Sistema</h3>
                    <p class="text-gray-600 text-sm mt-2">Digite suas credenciais para continuar</p>
                </div>
                <?php
                    if (isset($_SESSION['login_error'])) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">';
                        echo '<strong class="font-bold">Erro:</strong>';
                        echo '<span class="block sm:inline"> ' . htmlspecialchars($_SESSION['login_error']) . '</span>';
                        echo '</div>';
                        unset($_SESSION['login_error']); // Limpa a mensagem após exibi-la
                    }
                ?>
                <form id="loginForm" class="space-y-6" method="POST" action="login.php">
                    <div>
                        <label for="idt_Mil" class="block text-sm font-medium text-gray-700 mb-2">
                            Identidade Militar
                        </label>
                        <input 
                            type="text" 
                            id="idt_Mil" 
                            name="idt_Mil" 
                            required 
                            class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                            placeholder="Digite sua identidade militar"
                            autocomplete="username"
                        >
                    </div>

                    <div>
                        <label for="senha" class="block text-sm font-medium text-gray-700 mb-2">
                            Senha
                        </label>
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            required 
                            class="form-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors"
                            placeholder="Digite sua senha"
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="flex items-center">
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="lembrar_me" 
                                name="lembrar_me" 
                                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                            >
                            <label for="lembrar_me" class="ml-2 block text-sm text-gray-700">
                                Lembrar-me
                            </label>
                        </div>
                    </div>

                    <div>
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            Entrar no Sistema
                        </button>
                    </div>
                </form>

                <!-- Support Contact -->
                <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-600">
                        Suporte técnico: Ramal 4374 / 4161
                    </p>
                    <div class="mt-3">
                        <a 
                            href="Manual_do_Usuário_do_Painel_Administrativo.pdf" 
                            target="_blank"
                            class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 hover:underline transition-colors"
                        >
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                            </svg>
                            Manual do Usuário
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-black text-white py-3 px-6 text-center text-sm">
        Exército Brasileiro • Comando Logístico • Chefia de Material • SMU, Bloco C, Térreo. CEP: 70630-901 • Brasília DF • Divisão de Planejamento, Integração e Controle • Ramal 4374 / 5451
    </footer>

    <script>
        // Validação básica do formulário
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const identidadeMilitar = document.getElementById('idt_Mil').value;
            const senha = document.getElementById('senha').value;
            
            // Validação básica
            if (!identidadeMilitar.trim()) {
                alert('Por favor, digite sua identidade militar.');
                document.getElementById('idt_Mil').focus();
                return;
            }
            
            if (!senha.trim()) {
                alert('Por favor, digite sua senha.');
                document.getElementById('senha').focus();
                return;
            }
            
            // Validação de formato da identidade militar (exemplo)
            if (identidadeMilitar.length < 8) {
                alert('Identidade militar deve ter pelo menos 8 caracteres.');
                document.getElementById('idt_Mil').focus();
                return;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Entrando...';
            submitBtn.disabled = true;
            
        
        // Permitir Enter para submeter o formulário
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
        
        // Focus no primeiro campo quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('idt_Mil').focus();
        });
    </script>
</body>
</html>