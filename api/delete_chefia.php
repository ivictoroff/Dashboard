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
    echo json_encode(['error' => 'Permissão negada. Apenas Suporte Técnico pode gerenciar chefias.']);
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
    echo json_encode(['error' => 'ID da chefia é obrigatório']);
    exit();
}

$id = $data['id'];

try {
    // Verificar se a chefia existe
    $stmt = $conn->prepare("SELECT id, nome FROM chefia WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Chefia não encontrada']);
        exit();
    }
    $stmt->close();
    
    // Verificar se existem divisões vinculadas a esta chefia
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM divisao WHERE chefia_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Não é possível excluir esta chefia pois existem divisões vinculadas a ela. Exclua as divisões primeiro.']);
        exit();
    }
    $stmt->close();
    
    // Verificar se existem usuários vinculados a esta chefia
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE chefia_id = (SELECT id FROM chefia WHERE id = ?)");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Não é possível excluir esta chefia pois existem usuários vinculados a ela. Transfira os usuários para outra chefia primeiro.']);
        exit();
    }
    $stmt->close();
    
    // Excluir a chefia
    $stmt = $conn->prepare("DELETE FROM chefia WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Chefia excluída com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao excluir chefia: ' . $stmt->error]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
