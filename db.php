<?php
$host = "localhost";
$user = "root";
$pass = "@160l0nc3T";
$dbname = "cel";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Erro de conexão com banco de dados']));
}

$conn->set_charset("utf8");
?>
