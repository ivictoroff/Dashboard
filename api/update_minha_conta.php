<?php
session_start();
require_once '../db.php';

// Adicionar cabeçalho para JSON
header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

try {
    $usuarioId = $_SESSION['usuario_id'];
    $pg = $_POST['pg'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos obrigatórios
    if (empty($pg) || empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Posto/Graduação e Nome são obrigatórios']);
        exit();
    }
    
    // Preparar query de atualização
    if (!empty($senha)) {
        // Atualizar com nova senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET pg = ?, nome = ?, senha = ? WHERE id = ?");
        $stmt->bind_param('sssi', $pg, $nome, $senhaHash, $usuarioId);
    } else {
        // Atualizar sem alterar a senha
        $stmt = $conn->prepare("UPDATE usuarios SET pg = ?, nome = ? WHERE id = ?");
        $stmt->bind_param('ssi', $pg, $nome, $usuarioId);
    }
    
    if ($stmt->execute()) {
        // Atualizar dados na sessão
        $_SESSION['pg'] = $pg;
        $_SESSION['nome'] = $nome;
        
        echo json_encode([
            'success' => true,
            'message' => 'Dados atualizados com sucesso!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar dados: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
