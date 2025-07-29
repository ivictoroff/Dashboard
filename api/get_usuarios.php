<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $chefia_id = $_GET['chefia_id'] ?? null;
    $divisao_id = $_GET['divisao_id'] ?? null;

    $sql = "SELECT u.id, u.idt_Mil, u.pg, u.nome, ch.nome AS chefia, d.nome AS divisao, u.chefia_id, u.divisao_id, u.perfil_id, p.nome AS perfil
            FROM usuarios u
            JOIN chefia ch ON ch.id = u.chefia_id
            JOIN divisao d ON d.id = u.divisao_id
            LEFT JOIN perfis p ON p.id = u.perfil_id
            WHERE 1=1";

    $params = [];
    $types = "";

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
