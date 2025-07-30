<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário tem permissão (Auditor OM/Chefia ou Auditor COLOG)
$perfilId = $_SESSION['perfil_id'] ?? 2;
if (!in_array($perfilId, [2, 3])) { // 2=Auditor OM/Chefia, 3=Auditor COLOG
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit();
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $assuntoId = $data['assunto_id'] ?? null;
    $nota = trim($data['nota'] ?? '');
    $usuarioId = $_SESSION['usuario_id'];
    
    if (!$assuntoId || !$nota) {
        http_response_code(400);
        echo json_encode(['error' => 'Assunto ID e nota são obrigatórios']);
        exit();
    }
    
    // Verificar se o assunto existe
    $stmt = $conn->prepare("SELECT id FROM assuntos WHERE id = ? AND ativo = 1");
    $stmt->bind_param('i', $assuntoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Assunto não encontrado']);
        exit();
    }
    $stmt->close();
    
    // Inserir a nota de auditoria
    $stmt = $conn->prepare("INSERT INTO notas_auditoria (assunto_id, usuario_id, nota, data_criacao) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param('iis', $assuntoId, $usuarioId, $nota);
    
    if ($stmt->execute()) {
        $notaId = $conn->insert_id;
        
        // Buscar informações da nota recém-criada
        $stmt2 = $conn->prepare("
            SELECT n.id, n.nota, n.data_criacao, u.nome, u.pg
            FROM notas_auditoria n
            JOIN usuarios u ON u.id = n.usuario_id
            WHERE n.id = ?
        ");
        $stmt2->bind_param('i', $notaId);
        $stmt2->execute();
        $result = $stmt2->get_result();
        $notaInfo = $result->fetch_assoc();
        $stmt2->close();
        
        echo json_encode([
            'success' => true,
            'nota' => $notaInfo
        ]);
    } else {
        throw new Exception('Erro ao inserir nota de auditoria');
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}

$conn->close();
?>
