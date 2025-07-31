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
error_log('Dados recebidos no edit_usuario.php: ' . print_r($data, true));

$id = $data['id'] ?? '';
$idt_Mil = trim($data['idt_Mil'] ?? '');
$pg = trim($data['pg'] ?? '');
$nome = trim($data['nome'] ?? '');
$senha = $data['senha'] ?? '';
$chefia_id = $data['chefia_id'] ?? null; // Usar chefia_id em vez de chefia
$divisao_id = $data['divisao_id'] ?? null; // Usar divisao_id em vez de divisao
$perfil_id = $data['perfil_id'] ?? null;

// Converter strings vazias para null
$chefia_id = empty($chefia_id) ? null : (int)$chefia_id;
$divisao_id = empty($divisao_id) ? null : (int)$divisao_id;
$perfil_id = empty($perfil_id) ? null : (int)$perfil_id;

if (!$id || !$idt_Mil || !$pg || !$nome || !$perfil_id || !$chefia_id || !$divisao_id) {
    $missing = [];
    if (!$id) $missing[] = 'id';
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

// Verificar se o usuário a ser editado existe e pegar seus dados atuais
$stmt = $conn->prepare('SELECT chefia_id FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $stmt->close();
    http_response_code(404);
    echo json_encode(['error' => 'Usuário não encontrado.']);
    exit;
}
$usuarioAtual = $result->fetch_assoc();
$stmt->close();

// Validações específicas para perfil 5 (Cadastro de Usuário)
if ($perfilId === 5) {
    $chefiaUsuarioLogado = $_SESSION['chefia_id'] ?? null;
    
    // Só pode editar usuários da sua própria chefia
    if ($usuarioAtual['chefia_id'] !== $chefiaUsuarioLogado) {
        http_response_code(403);
        echo json_encode(['error' => 'Você só pode editar usuários da sua própria chefia.']);
        exit;
    }
    
    // Só pode manter/alterar para perfil 2 (Auditor OM/Chefia) ou 4 (Editor)
    if ($perfil_id !== 2 && $perfil_id !== 4) {
        http_response_code(403);
        echo json_encode(['error' => 'Você só pode definir perfil Auditor OM/Chefia ou Editor.']);
        exit;
    }
    
    // Não pode alterar a chefia do usuário para outra chefia
    if ($chefia_id !== $chefiaUsuarioLogado) {
        http_response_code(403);
        echo json_encode(['error' => 'Você não pode alterar a chefia do usuário.']);
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

try {

    // Check if idt_Mil already exists for other users
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE idt_Mil = ? AND id != ?');
    $stmt->bind_param('si', $idt_Mil, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        http_response_code(409);
        echo json_encode(['error' => 'Identidade Militar já cadastrada para outro usuário.']);
        exit;
    }
    $stmt->close();

    if (!empty($senha) && trim($senha) !== '') {
        // Update with new password
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE usuarios SET idt_Mil = ?, pg = ?, nome = ?, senha = ?, chefia_id = ?, divisao_id = ?, perfil_id = ? WHERE id = ?');
        $stmt->bind_param('ssssiiii', $idt_Mil, $pg, $nome, $senhaHash, $chefia_id, $divisao_id, $perfil_id, $id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare('UPDATE usuarios SET idt_Mil = ?, pg = ?, nome = ?, chefia_id = ?, divisao_id = ?, perfil_id = ? WHERE id = ?');
        $stmt->bind_param('sssiiii', $idt_Mil, $pg, $nome, $chefia_id, $divisao_id, $perfil_id, $id);
    }

    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar usuário.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao atualizar usuário: ' . $e->getMessage()]);
}
