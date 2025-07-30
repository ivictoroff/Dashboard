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

try {
    $assuntoId = $_GET['assunto_id'] ?? null;
    
    if (!$assuntoId) {
        http_response_code(400);
        echo json_encode(['error' => 'Assunto ID é obrigatório']);
        exit();
    }
    
    // Buscar notas de auditoria do assunto
    $stmt = $conn->prepare("
        SELECT 
            n.id,
            n.nota,
            n.data_criacao,
            u.nome,
            u.pg,
            p.nome as perfil_nome
        FROM notas_auditoria n
        JOIN usuarios u ON u.id = n.usuario_id
        LEFT JOIN perfis p ON p.id = u.perfil_id
        WHERE n.assunto_id = ?
        ORDER BY n.data_criacao DESC
    ");
    
    $stmt->bind_param('i', $assuntoId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notas = [];
    while ($row = $result->fetch_assoc()) {
        $notas[] = [
            'id' => $row['id'],
            'nota' => $row['nota'],
            'data_criacao' => $row['data_criacao'],
            'autor' => $row['pg'] . ' ' . $row['nome'],
            'perfil' => $row['perfil_nome'] ?? 'N/A'
        ];
    }
    
    echo json_encode($notas);
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}

$conn->close();
?>
