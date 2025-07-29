<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $chefia_id_param = $_GET['chefia_id'] ?? null;

    // Join divisao with itself to get the chefia's name
    $sql = "SELECT d.id, d.nome, d.chefia_id, c.nome as chefia_nome 
            FROM divisao d 
            LEFT JOIN chefia c ON d.chefia_id = c.id 
            WHERE 1=1";

    if ($chefia_id_param) {
        $sql .= " AND d.chefia_id = ?";
    }

    $stmt = $conn->prepare($sql);

    if ($chefia_id_param) {
        $stmt->bind_param("i", $chefia_id_param);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $divisoes = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($divisoes, JSON_UNESCAPED_UNICODE);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao buscar divisões: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>