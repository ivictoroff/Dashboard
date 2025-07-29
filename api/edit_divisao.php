<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
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

if (!$data || !isset($data['id']) || !isset($data['nome']) || !isset($data['chefia_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID, nome e chefia_id são obrigatórios']);
    exit();
}

$id = $data['id'];
$nome = trim($data['nome']);
$chefia_id = $data['chefia_id'];

// Validação
if (empty($nome)) {
    http_response_code(400);
    echo json_encode(['error' => 'Nome da divisão é obrigatório']);
    exit();
}

try {
    // Verificar se a divisão existe
    $stmt = $conn->prepare("SELECT id FROM divisao WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Divisão não encontrada']);
        exit();
    }
    $stmt->close();
    
    // Verificar se a chefia existe
    $stmt = $conn->prepare("SELECT id FROM chefia WHERE id = ?");
    $stmt->bind_param('i', $chefia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Chefia não encontrada']);
        exit();
    }
    $stmt->close();
    
    // Verificar se já existe outra divisão com o mesmo nome
    $stmt = $conn->prepare("SELECT id FROM divisao WHERE nome = ? AND id != ?");
    $stmt->bind_param('si', $nome, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Já existe uma divisão com este nome']);
        exit();
    }
    $stmt->close();
    
    // Atualizar a divisão
    $stmt = $conn->prepare("UPDATE divisao SET nome = ?, chefia_id = ? WHERE id = ?");
    $stmt->bind_param('sii', $nome, $chefia_id, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Divisão atualizada com sucesso']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar divisão: ' . $stmt->error]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}

$conn->close();
?>
