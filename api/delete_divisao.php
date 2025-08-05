<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário tem permissão (Suporte Técnico ou Cadastro de Usuário)
$perfilId = $_SESSION['perfil_id'] ?? 2;
if ($perfilId !== 1 && $perfilId !== 5) { // 1=Suporte Técnico, 5=Cadastro de Usuário
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada. Apenas Suporte Técnico e Cadastro de Usuário podem gerenciar divisões.']);
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
    echo json_encode(['error' => 'ID da divisão é obrigatório']);
    exit();
}

$id = $data['id'];

try {
    // Verificar se a divisão existe e pegar dados para validação
    $stmt = $conn->prepare("SELECT id, nome, chefia_id FROM divisao WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Divisão não encontrada']);
        exit();
    }
    
    $divisao = $result->fetch_assoc();
    $stmt->close();
    
    // Validações específicas para perfil 5 (Cadastro de Usuário)
    if ($perfilId === 5) {
        // Só pode excluir divisões da sua própria chefia
        $chefiaUsuarioLogado = $_SESSION['chefia_id'] ?? null;
        if ($divisao['chefia_id'] !== $chefiaUsuarioLogado) {
            http_response_code(403);
            echo json_encode(['error' => 'Você só pode excluir divisões da sua própria chefia.']);
            exit;
        }
    }
    
    // Verificar se existem usuários vinculados a esta divisão
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM usuarios WHERE divisao_id = (SELECT id FROM divisao WHERE id = ?)");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Não é possível excluir esta divisão pois existem usuários vinculados a ela. Transfira os usuários para outra divisão primeiro.']);
        exit();
    }
    $stmt->close();
    
    // Excluir a divisão
    $stmt = $conn->prepare("DELETE FROM divisao WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Divisão excluída com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao excluir divisão: ' . $stmt->error]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
