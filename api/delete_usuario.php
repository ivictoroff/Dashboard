<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário tem permissão (apenas Suporte Técnico)
$perfilId = $_SESSION['perfil_id'] ?? 2;
if ($perfilId !== 1) { // 1=Suporte Técnico
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada. Apenas Suporte Técnico pode gerenciar usuários.']);
    exit();
}

require_once '../db.php';

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

// Obter dados JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID do usuário é obrigatório']);
    exit();
}

$id = $data['id'];

try {
    // Verificar se o usuário existe
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Usuário não encontrado']);
        exit();
    }
    $stmt->close();
    
    // Não permitir que o usuário exclua a si mesmo
    if ($id == $_SESSION['usuario_id']) {
        http_response_code(400);
        echo json_encode(['error' => 'Não é possível excluir seu próprio usuário']);
        exit();
    }
    
    // Excluir o usuário
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao excluir usuário: ' . $stmt->error]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
