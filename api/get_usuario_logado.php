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

try {
    $usuarioId = $_SESSION['usuario_id'];
    
    // Buscar dados básicos do usuário
    $stmt = $conn->prepare("
        SELECT id, idt_Mil, pg, nome
        FROM usuarios 
        WHERE id = ?
    ");
    
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado'
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
