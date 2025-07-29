<?php
// api/add_chefia.php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['nome']) || trim($data['nome']) === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Nome da chefia é obrigatório.']);
    exit;
}
$nome = trim($data['nome']);

// Verifica se já existe
$stmt = $conn->prepare('SELECT id FROM chefia WHERE nome = ?');
$stmt->bind_param('s', $nome);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'Chefia já cadastrada.']);
    exit;
}
$stmt->close();

$stmt = $conn->prepare('INSERT INTO chefia (nome) VALUES (?)');
$stmt->bind_param('s', $nome);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar chefia.']);
}
