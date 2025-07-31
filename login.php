<?php
// login.php
session_start(); // Inicia a sessão PHP

require_once 'db.php'; // Inclui as configurações do banco de dados (com a conexão MySQLi)

// Verifica se o formulário foi submetido (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idt_Mil = trim($_POST['idt_Mil'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $lembrar_me = isset($_POST['lembrar_me']); // Verifica se a checkbox foi marcada

    // Validação básica dos campos
    if (empty($idt_Mil) || empty($senha)) {
        $_SESSION['login_error'] = "Por favor, preencha todos os campos.";
        header('Location: index.php'); // Redireciona de volta para a página de login
        exit();
    }

    // Validação de formato da identidade militar (exemplo)
    if (strlen($idt_Mil) < 8) {
        $_SESSION['login_error'] = "Identidade militar deve ter pelo menos 8 caracteres.";
        header('Location: index.php'); // Redireciona de volta para a página de login
        exit();
    }

    // Usando prepared statements com MySQLi para prevenir injeção de SQL
    $stmt = $conn->prepare("SELECT id, idt_Mil, pg, senha, nome, divisao_id, chefia_id, perfil_id, primeiro_login FROM usuarios WHERE idt_Mil = ?");

    if ($stmt === false) {
        // Erro na preparação da consulta
        $_SESSION['login_error'] = "Erro interno do servidor (stmt prepare).";
        // Em um ambiente de produção, logar $conn->error
        header('Location: index.php');
        exit();
    }

    // Bind do parâmetro
    $stmt->bind_param('s', $idt_Mil); // 's' indica que o parâmetro é uma string

    // Executa a consulta
    $stmt->execute();

    // Obtém o resultado
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc(); // Busca o usuário como um array associativo

    // Fecha o statement
    $stmt->close();
    // A conexão $conn permanece aberta, mas pode ser fechada no final do script se não for mais usada
    // $conn->close(); // Fechar a conexão aqui pode ser prematuro se outros scripts a usarem

    // Verifica se o usuário existe e se a senha está correta
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Login bem-sucedido!
        $_SESSION['logged_in'] = true;
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['idt_Mil'] = $usuario['idt_Mil'];
        $_SESSION['pg'] = $usuario['pg'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['divisao_id'] = $usuario['divisao_id'] ?? null;
        $_SESSION['chefia_id'] = $usuario['chefia_id'] ?? null;
        $_SESSION['perfil_id'] = $usuario['perfil_id'] ?? null;
        $_SESSION['primeiro_login'] = (bool)($usuario['primeiro_login'] ?? false);

        // Define a duração da sessão se "Lembrar-me" for marcado
        if ($lembrar_me) {
            $cookie_lifetime = time() + (86400 * 7); // 7 dias
            session_set_cookie_params($cookie_lifetime); // Isso deve ser chamado ANTES de session_start() para ter efeito completo.
                                                        // Para persistência real, considere um token "remember me" no DB.
        }

        header('Location: home.php'); // Redireciona para a página de dashboard
        exit();
    } else {
        // Credenciais inválidas
        $_SESSION['login_error'] = "Identidade militar ou senha inválidos.";
        header('Location: index.php'); // Redireciona de volta para a página de login
        exit();
    }
} else {
    // Se a página for acessada diretamente sem POST
    header('Location: index.php');
    exit();
}
?>