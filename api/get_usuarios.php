<?php

require_once '../session_check.php';
// Verificar permissões baseadas no perfil
$perfil = $_SESSION['perfil_id'] ?? 0;

// Apenas perfis 1 (Suporte) e 5 (Cadastro) podem acessar usuários
if (!in_array($perfil, [1, 5])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado. Você não tem permissão para visualizar usuários.']);
    exit();
}
header('Content-Type: application/json');
require_once '../db.php';
try {
    $chefia_id = $_GET['chefia_id'] ?? null;
    $divisao_id = $_GET['divisao_id'] ?? null;
    $incluir_inativos = $_GET['incluir_inativos'] ?? false;

    $sql = "SELECT u.id, u.idt_Mil, u.pg, u.nome, ch.nome AS chefia, d.nome AS divisao, u.chefia_id, u.divisao_id, u.perfil_id, p.nome AS perfil, u.ativo
            FROM usuarios u
            JOIN chefia ch ON ch.id = u.chefia_id
            JOIN divisao d ON d.id = u.divisao_id
            LEFT JOIN perfis p ON p.id = u.perfil_id
            WHERE 1=1";

    $params = [];
    $types = "";

    // Por padrão, mostrar apenas usuários ativos, a menos que seja especificado incluir inativos
    if (!$incluir_inativos) {
        $sql .= " AND u.ativo = 1";
    }

    if ($chefia_id) {
        $sql .= " AND u.chefia_id = ?";
        $params[] = $chefia_id;
        $types .= "i";
    }
    if ($divisao_id) {
        $sql .= " AND u.divisao_id = ?";
        $params[] = $divisao_id;
        $types .= "i";
    }

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $bind = array_merge([$types], $params);
        foreach ($bind as $i => $v) $bindRef[$i] = &$bind[$i];
        call_user_func_array([$stmt, 'bind_param'], $bindRef);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $usuarios = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($usuarios, JSON_UNESCAPED_UNICODE);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar usuários: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
