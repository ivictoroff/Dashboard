<?php
require_once '../session_check.php';

// Verificar permissões baseadas no perfil
$perfil = $_SESSION['perfil_id'] ?? 0;

// Apenas perfis 1 (Suporte) e 5 (Cadastro) podem acessar chefias
if (!in_array($perfil, [1, 5])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado. Você não tem permissão para visualizar Chefias.']);
    exit();
}
header('Content-Type: application/json');
require_once '../db.php';
try {
    $sql = "SELECT id, nome FROM chefia ORDER BY id";
    $result = $conn->query($sql);

    $chefias = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($chefias, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar chefias: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
