<?php
// login.php
session_start(); // Inicia a sessão PHP

require_once 'db.php'; // Inclui as configurações do banco de dados (com a conexão MySQLi)

// Testar conexão com banco
if ($conn->connect_error) {
    error_log("Erro de conexão: " . $conn->connect_error);
    $_SESSION['login_error'] = "Erro de conexão com o banco de dados.";
    header('Location: index.php');
    exit();
}

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
    $stmt = $conn->prepare("SELECT u.id, u.idt_Mil, u.pg, u.senha, u.nome, u.divisao_id, u.chefia_id, u.perfil_id, u.primeiro_login, u.ativo, d.nome AS divisao_nome, c.nome AS chefia_nome FROM usuarios u LEFT JOIN divisao d ON d.id = u.divisao_id LEFT JOIN chefia c ON c.id = u.chefia_id WHERE u.idt_Mil = ?");

    if ($stmt === false) {
        // Erro na preparação da consulta
        error_log("Erro MySQL: " . $conn->error);
        $_SESSION['login_error'] = "Erro interno do servidor. Tente novamente.";
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
        // Verificar se o usuário está ativo
        if ($usuario['ativo'] != 1) {
            $_SESSION['login_error'] = "Sua conta está inativa. Entre em contato com o administrador para reativar seu acesso.";
            error_log("Tentativa de login de usuário inativo: " . $idt_Mil);
            header('Location: index.php');
            exit();
        }   
        
        // Login bem-sucedido - usuário ativo!
        $_SESSION['logged_in'] = true;
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['idt_Mil'] = $usuario['idt_Mil'];
        $_SESSION['pg'] = $usuario['pg'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['divisao_id'] = $usuario['divisao_id'] ?? null;
        $_SESSION['chefia_id'] = $usuario['chefia_id'] ?? null;
        $_SESSION['perfil_id'] = $usuario['perfil_id'] ?? null;
        $_SESSION['primeiro_login'] = (bool)($usuario['primeiro_login'] ?? false);
        $_SESSION['divisao_nome'] = $usuario['divisao_nome'] ?? 'Não definida';
        $_SESSION['chefia_nome'] = $usuario['chefia_nome'] ?? 'Não definida';

        // Define a duração da sessão se "Lembrar-me" for marcado
        if ($lembrar_me) {
            // Configurar cookie de sessão para durar 7 dias
            setcookie(session_name(), session_id(), time() + (86400 * 7), "/");
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