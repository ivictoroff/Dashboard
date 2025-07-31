<?php
// api/add_usuario.php
session_start();
header('Content-Type: application/json');

require_once '../db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuário não autenticado']);
    exit();
}

// Verificar se o usuário tem permissão (Suporte Técnico ou Cadastro de Usuário)
$perfilId = $_SESSION['perfil_id'] ?? 2;
if ($perfilId !== 1 && $perfilId !== 5) { // 1=Suporte Técnico, 5=Cadastro de Usuário
    http_response_code(403);
    echo json_encode(['error' => 'Permissão negada. Apenas Suporte Técnico e Cadastro de Usuário podem gerenciar usuários.']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

// Log para debug - remover em produção
error_log('Dados recebidos no add_usuario.php: ' . print_r($data, true));

$idt_Mil = trim($data['idt_Mil'] ?? '');
$pg = trim($data['pg'] ?? '');
$nome = trim($data['nome'] ?? '');
$senha = $data['senha'] ?? ''; // Será ignorada e substituída pela idt_Mil
$chefia_id = $data['chefia_id'] ?? null; // Usar chefia_id em vez de chefia
$divisao_id = $data['divisao_id'] ?? null; // Usar divisao_id em vez de divisao
$perfil_id = $data['perfil_id'] ?? null;

// A senha padrão será sempre a identidade militar
$senhaDefault = $idt_Mil;

// Converter strings vazias para null
$chefia_id = empty($chefia_id) ? null : (int)$chefia_id;
$divisao_id = empty($divisao_id) ? null : (int)$divisao_id;
$perfil_id = empty($perfil_id) ? null : (int)$perfil_id;

if (!$idt_Mil || !$pg || !$nome || !$perfil_id || !$chefia_id || !$divisao_id) {
    $missing = [];
    if (!$idt_Mil) $missing[] = 'idt_Mil';
    if (!$pg) $missing[] = 'pg';
    if (!$nome) $missing[] = 'nome';
    if (!$perfil_id) $missing[] = 'perfil_id';
    if (!$chefia_id) $missing[] = 'chefia_id';
    if (!$divisao_id) $missing[] = 'divisao_id';
    
    http_response_code(400);
    echo json_encode(['error' => 'Campos obrigatórios faltando: ' . implode(', ', $missing)]);
    exit;
}

// Validações específicas para perfil 5 (Cadastro de Usuário)
if ($perfilId === 5) {
    // Só pode criar usuários na sua própria chefia
    $chefiaUsuarioLogado = $_SESSION['chefia_id'] ?? null;
    if ($chefia_id !== $chefiaUsuarioLogado) {
        http_response_code(403);
        echo json_encode(['error' => 'Você só pode criar usuários na sua própria chefia.']);
        exit;
    }
    
    // Só pode criar usuários com perfil 2 (Auditor OM/Chefia) ou 4 (Editor)
    if ($perfil_id !== 2 && $perfil_id !== 4) {
        http_response_code(403);
        echo json_encode(['error' => 'Você só pode criar usuários com perfil Auditor OM/Chefia ou Editor.']);
        exit;
    }
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

// Criptografa a senha padrão (identidade militar)
$senhaHash = password_hash($senhaDefault, PASSWORD_DEFAULT);

$stmt = $conn->prepare('INSERT INTO usuarios (idt_Mil, pg, nome, senha, chefia_id, divisao_id, perfil_id, primeiro_login) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)');
$stmt->bind_param('ssssiii', $idt_Mil, $pg, $nome, $senhaHash, $chefia_id, $divisao_id, $perfil_id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao salvar usuário.']);
}
