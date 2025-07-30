<?php
// api/delete_assunto.php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar permissões - todos os perfis podem excluir
$perfil = $_SESSION['perfil_id'] ?? 2;

header('Content-Type: application/json');
require_once '../db.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || empty($input['id'])) {
        throw new Exception('ID do assunto é obrigatório');
    }
    
    $assuntoId = intval($input['id']);
    $usuarioId = $_SESSION['usuario_id'];
    
    // Verificar se o assunto existe e está ativo
    $stmt = $conn->prepare("SELECT assunto FROM assuntos WHERE id = ? AND ativo = 1");
    $stmt->bind_param('i', $assuntoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Assunto não encontrado ou já foi excluído');
    }
    
    $assunto = $result->fetch_assoc();
    $stmt->close();
    
    // Desativar o assunto
    $stmt = $conn->prepare("UPDATE assuntos SET ativo = 0 WHERE id = ?");
    $stmt->bind_param('i', $assuntoId);
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao excluir assunto: ' . $conn->error);
    }
    
    $stmt->close();
    
    // Registrar no histórico
    $acao = "Excluiu o assunto: " . $assunto['assunto'];
    $stmt = $conn->prepare("INSERT INTO historico (assunto_id, data, usuario, acao) VALUES (?, CURDATE(), ?, ?)");
    $stmt->bind_param('iis', $assuntoId, $usuarioId, $acao);
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Assunto excluído com sucesso']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
