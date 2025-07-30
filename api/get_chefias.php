<?php
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
