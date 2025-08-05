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
    // Verificar se a divisão existe e pegar dados para validação
    $stmt = $conn->prepare("SELECT id, chefia_id FROM divisao WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Divisão não encontrada']);
        exit();
    }
    
    $divisaoAtual = $result->fetch_assoc();
    $stmt->close();
    
    // Validações específicas para perfil 5 (Cadastro de Usuário)
    if ($perfilId === 5) {
        // Só pode editar divisões da sua própria chefia
        $chefiaUsuarioLogado = $_SESSION['chefia_id'] ?? null;
        if ($divisaoAtual['chefia_id'] !== $chefiaUsuarioLogado) {
            http_response_code(403);
            echo json_encode(['error' => 'Você só pode editar divisões da sua própria chefia.']);
            exit;
        }
        
        // Não pode alterar a chefia da divisão para outra chefia
        if ($chefia_id !== $chefiaUsuarioLogado) {
            http_response_code(403);
            echo json_encode(['error' => 'Você não pode alterar a chefia da divisão.']);
            exit;
        }
    }
    
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
