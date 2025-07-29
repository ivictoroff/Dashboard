<?php
session_start(); 

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['message' => 'Usuário não autenticado. Favor fazer login novamente.']);
    exit();
}
$criadoPorId = $_SESSION['usuario_id'];

header('Content-Type: application/json');

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Decodifica o JSON enviado
    $data = json_decode(file_get_contents('php://input'), true);

    $titulo = $data['assunto'] ?? '';
    $critico = $data['critico'] ?? '';
    $prazo = $data['prazo'] ?? '';
    $estado = $data['estado'] ?? '';
    $acoes = $data['acoes'] ?? [];

    $acaoJson = json_encode($acoes);

    // Validação básica dos dados:
    // Garanta que os campos obrigatórios não estão vazios.
    if (empty($data['assunto']) || empty($data['prazo']) || empty($data['estado'])) {
        http_response_code(400);
        echo json_encode(['message' => 'Campos obrigatórios faltando.']);
        exit();
    }

    $criadoPorId = $criadoPorId; 

    require_once '../db.php';

    // Prepara a inserção do assunto principal
    // Inclua 'criadoPor' na sua lista de colunas e no bind_param
    $stmt = $conn->prepare("INSERT INTO assuntos (assunto, critico, prazo, estado, criadoPor, dataAtualizacao) VALUES (?, ?, ?, ?, ?, CURDATE())");
    
    // O 'i' indica que criadoPor é um inteiro
    $stmt->bind_param("ssssi", 
        $data['assunto'], 
        $data['critico'], 
        $data['prazo'], 
        $data['estado'], 
        $criadoPorId // Usando o ID fixo
    );

    // A linha 22 do seu erro anterior provavelmente era o $stmt->execute()
    if ($stmt->execute()) {
        $assuntoId = $conn->insert_id; // Pega o ID do assunto recém-inserido

        // Registra no histórico a criação do assunto
        $stmtHistorico = $conn->prepare("INSERT INTO historico (assunto_id, data, usuario, acao) VALUES (?, NOW(), ?, ?)");
        $acaoHistorico = "Criou o assunto \"" . $data['assunto'] . "\"";
        $stmtHistorico->bind_param("iis", $assuntoId, $criadoPorId, $acaoHistorico);
        $stmtHistorico->execute();
        $stmtHistorico->close();

        // Insere as ações associadas ao assunto (se houverem)
        if (!empty($data['acoes'])) {
            $stmtAcao = $conn->prepare("INSERT INTO acoes (assunto_id, acao, providencia, estado, responsavel, dataAtualizacao) VALUES (?, ?, ?, ?, ?, CURDATE())");
            foreach ($data['acoes'] as $acao) {
                // Se 'responsavel' for o mesmo que 'criadoPor', use $criadoPorId
                $responsavel = $acao['responsavel'] ?? $criadoPorId; // Assumindo que a ação pode ter um responsável, caso contrário usa o criador
                
                $stmtAcao->bind_param("issss", 
                    $assuntoId, 
                    $acao['acao'], 
                    $acao['providencia'], 
                    $acao['estado'], 
                    $responsavel
                );
                if ($stmtAcao->execute()) {
                    // Registra no histórico cada ação adicionada
                    $stmtHistoricoAcao = $conn->prepare("INSERT INTO historico (assunto_id, data, usuario, acao) VALUES (?, NOW(), ?, ?)");
                    $acaoHistoricoTexto = "Adicionou ação \"" . $acao['acao'] . "\"";
                    $stmtHistoricoAcao->bind_param("iis", $assuntoId, $criadoPorId, $acaoHistoricoTexto);
                    $stmtHistoricoAcao->execute();
                    $stmtHistoricoAcao->close();
                }
            }
            $stmtAcao->close();
        }

        echo json_encode(['message' => 'Assunto adicionado com sucesso!', 'id' => $assuntoId]);
    } else {
        http_response_code(500);
        // Não inclua $stmt->error diretamente em produção por questões de segurança.
        // Registre em um log e mostre uma mensagem genérica para o usuário.
        echo json_encode(['message' => 'Erro ao inserir assunto no banco de dados: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} else {
    http_response_code(405); // Método não permitido
    echo json_encode(['message' => 'Método não permitido.']);
}
?>