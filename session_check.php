<?php
// Verificação de sessão para APIs
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['perfil_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Usuário não autenticado. Faça login para acessar este recurso.']);
    exit();
}

// Verificar se a sessão não expirou (opcional - 8 horas)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 28800)) {
    // Sessão expirada
    session_unset();
    session_destroy();
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Sessão expirada. Faça login novamente.']);
    exit();
}

// Atualizar última atividade
$_SESSION['last_activity'] = time();
?>
