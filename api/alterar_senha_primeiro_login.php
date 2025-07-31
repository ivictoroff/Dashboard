<?php
session_start();
require_once '../db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Verificar se é realmente o primeiro login
if (!isset($_SESSION['primeiro_login']) || !$_SESSION['primeiro_login']) {
    echo json_encode(['success' => false, 'message' => 'Operação não permitida']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $usuarioId = $_SESSION['usuario_id'];

    // Validações básicas
    if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
        echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
        exit();
    }

    if ($novaSenha !== $confirmarSenha) {
        echo json_encode(['success' => false, 'message' => 'As senhas não coincidem']);
        exit();
    }

    if (strlen($novaSenha) < 6) {
        echo json_encode(['success' => false, 'message' => 'A nova senha deve ter pelo menos 6 caracteres']);
        exit();
    }

    try {
        // Buscar a senha atual do usuário
        $stmt = $conn->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $usuarioId);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            exit();
        }

        // Verificar se a senha atual está correta
        if (!password_verify($senhaAtual, $usuario['senha'])) {
            echo json_encode(['success' => false, 'message' => 'Senha atual incorreta']);
            exit();
        }

        // Criptografar a nova senha
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        // Atualizar a senha e marcar que não é mais primeiro login
        $stmt = $conn->prepare("UPDATE usuarios SET senha = ?, primeiro_login = FALSE WHERE id = ?");
        $stmt->bind_param('si', $novaSenhaHash, $usuarioId);
        
        if ($stmt->execute()) {
            $stmt->close();
            
            // Atualizar a sessão
            $_SESSION['primeiro_login'] = false;
            
            echo json_encode(['success' => true, 'message' => 'Senha alterada com sucesso']);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar senha no banco de dados']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>
