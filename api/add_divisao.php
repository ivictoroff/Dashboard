<?php
// api/add_divisao.php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário tem permissão (apenas Suporte Técnico)
$perfilId = $_SESSION['perfil_id'] ?? 2;
if ($perfilId !== 1) { // 1=Suporte Técnico
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada. Apenas Suporte Técnico pode gerenciar divisões.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['nome']) || trim($data['nome']) === '' || !isset($data['chefia_id']) || !$data['chefia_id']) {
    http_response_code(400);
    echo json_encode(['error' => 'Nome da divisão e chefia são obrigatórios.']);
    exit;
}
$nome = trim($data['nome']);
$chefia_id = intval($data['chefia_id']);

// Verifica se já existe
$stmt = $conn->prepare('SELECT id FROM divisao WHERE nome = ? AND chefia_id = ?');
$stmt->bind_param('si', $nome, $chefia_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'Divisão já cadastrada para esta chefia.']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO divisao (nome, chefia_id) VALUES (?, ?)');
$stmt->bind_param('si', $nome, $chefia_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao salvar divisão.',
        'mysql_error' => $conn->error
    ]);
}
