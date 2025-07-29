<?php
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['nome']) || trim($data['nome']) === '' || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Nome da chefia e ID são obrigatórios.']);
    exit;
}

$nome = trim($data['nome']);
$id = intval($data['id']);

// Verifica se já existe outra chefia com mesmo nome
$stmt = $conn->prepare('SELECT id FROM chefia WHERE nome = ? AND id != ?');
$stmt->bind_param('si', $nome, $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'Já existe uma chefia com este nome.']);
    exit;
}
$stmt->close();

// Verifica se a chefia existe
$stmt = $conn->prepare('SELECT id FROM chefia WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'Chefia não encontrada.']);
    exit;
}
$stmt->close();

// Atualiza a chefia
$stmt = $conn->prepare('UPDATE chefia SET nome = ? WHERE id = ?');
$stmt->bind_param('si', $nome, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao atualizar chefia.',
        'mysql_error' => $conn->error
    ]);
}
