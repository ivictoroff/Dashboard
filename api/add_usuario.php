<?php
// api/add_usuario.php
header('Content-Type: application/json');

require_once '../db.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

$idt_Mil = trim($data['idt_Mil'] ?? '');
$pg = trim($data['pg'] ?? '');
$nome = trim($data['nome'] ?? '');
$senha = $data['senha'] ?? '';
$chefia_id = $data['chefia'] ?? null;
$divisao_id = $data['divisao'] ?? null;
$perfil_id = $data['perfil_id'] ?? null;

// Converter strings vazias para null
$chefia_id = empty($chefia_id) ? null : (int)$chefia_id;
$divisao_id = empty($divisao_id) ? null : (int)$divisao_id;
$perfil_id = empty($perfil_id) ? null : (int)$perfil_id;

if (!$idt_Mil || !$pg || !$nome || !$senha || !$perfil_id || !$chefia_id || !$divisao_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Preencha todos os campos obrigatórios.']);
    exit;
}

// Validar se os IDs de chefia e divisão existem
if ($chefia_id) {
    $stmt = $conn->prepare('SELECT id FROM chefia WHERE id = ?');
    $stmt->bind_param('i', $chefia_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Chefia selecionada não existe.']);
        exit;
    }
    $stmt->close();
}

if ($divisao_id) {
    $stmt = $conn->prepare('SELECT id FROM divisao WHERE id = ?');
    $stmt->bind_param('i', $divisao_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Divisão selecionada não existe.']);
        exit;
    }
    $stmt->close();
}

// Verifica se idt_Mil já existe
$stmt = $conn->prepare('SELECT id FROM usuarios WHERE idt_Mil = ?');
$stmt->bind_param('s', $idt_Mil);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    http_response_code(409);
    echo json_encode(['error' => 'Identidade Militar já cadastrada.']);
    exit;
}
$stmt->close();

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO usuarios (idt_Mil, pg, nome, senha, chefia_id, divisao_id, perfil_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('ssssiii', $idt_Mil, $pg, $nome, $senhaHash, $chefia_id, $divisao_id, $perfil_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar usuário.']);
}
