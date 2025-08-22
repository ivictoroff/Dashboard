<?php
session_start();

// Configuração de headers antes de qualquer output
header('Content-Type: application/json');

// Verificação de autenticação 
if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Sessão expirada ou usuário não autenticado', 
        'action' => 'redirect_login',
        'message' => 'Por favor, faça login novamente para continuar'
    ]);
    exit;
}

// Verificação de método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

// Recebe dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validação básica
if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos - ID obrigatório']);
    exit;
}

// Validação de campos obrigatórios
if (empty($data['assunto'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Campo assunto é obrigatório']);
    exit;
}

// Conexão com o banco
require_once '../db.php';

try {
    // Inicia transação
    $conn->begin_transaction();

    $id = intval($data['id']);
    $assunto = trim($data['assunto'] ?? '');
    $prazo = $data['prazo'] ?? null;
    $estado = $data['estado'] ?? 'pendente';
    $critico = $data['critico'] ?? 'nao';
    $acoes = $data['acoes'] ?? [];
    $usuarioId = $_SESSION['usuario_id'];

    // Verifica se o assunto existe e as permissões do usuário
    $stmtCheck = $conn->prepare("
        SELECT a.assunto, a.prazo, a.estado, a.critico, a.criadoPor,
               u_criador.chefia_id as criador_chefia, u_criador.divisao_id as criador_divisao,
               u_atual.chefia_id as atual_chefia, u_atual.divisao_id as atual_divisao
        FROM assuntos a 
        JOIN usuarios u_criador ON a.criadoPor = u_criador.id
        JOIN usuarios u_atual ON u_atual.id = ?
        WHERE a.id = ? AND a.ativo = 1
    ");
    $stmtCheck->bind_param("ii", $usuarioId, $id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Assunto não encontrado no sistema");
    }
    
    $row = $result->fetch_assoc();
    $assuntoAtual = [
        'assunto' => $row['assunto'],
        'prazo' => $row['prazo'],
        'estado' => $row['estado'],
        'critico' => $row['critico']
    ];
    
    // Verificar permissões: usuário pode editar se:
    // 1. É o criador do assunto OU
    // 2. Está na mesma chefia do criador OU  
    // 3. Está na mesma divisão do criador
    $podeEditar = (
        $row['criadoPor'] == $usuarioId || // É o criador
        $row['criador_chefia'] == $row['atual_chefia'] || // Mesma chefia
        $row['criador_divisao'] == $row['atual_divisao'] // Mesma divisão
    );
    
    if (!$podeEditar) {
        throw new Exception("Sem permissão para editar este assunto. Você deve estar na mesma chefia/divisão do criador.");
    }
    
    $stmtCheck->close();

    // Array para armazenar mudanças para o histórico
    $mudancas = [];

    // Verifica mudanças nos campos principais
    if ($assuntoAtual['assunto'] !== $assunto) {
        $mudancas[] = "Alterou o assunto de \"{$assuntoAtual['assunto']}\" para \"$assunto\"";
    }
    if ($assuntoAtual['prazo'] !== $prazo) {
        $prazoAtual = $assuntoAtual['prazo'] ? date('d/m/Y', strtotime($assuntoAtual['prazo'])) : 'Não definido';
        $prazoDest = $prazo ? date('d/m/Y', strtotime($prazo)) : 'Não definido';
        $mudancas[] = "Alterou o prazo de \"$prazoAtual\" para \"$prazoDest\"";
    }
    if ($assuntoAtual['estado'] !== $estado) {
        $estadoAtual = $assuntoAtual['estado'] === 'concluido' ? 'Concluído' : 'Pendente';
        $estadoDest = $estado === 'concluido' ? 'Concluído' : 'Pendente';
        $mudancas[] = "Alterou o estado de \"$estadoAtual\" para \"$estadoDest\"";
    }
    if ($assuntoAtual['critico'] !== $critico) {
        $criticoAtual = $assuntoAtual['critico'] === 'sim' ? 'Sim' : 'Não';
        $criticoDest = $critico === 'sim' ? 'Sim' : 'Não';
        $mudancas[] = "Alterou criticidade de \"$criticoAtual\" para \"$criticoDest\"";
    }

    // Atualiza o assunto principal
    $stmt = $conn->prepare("UPDATE assuntos SET assunto=?, prazo=?, estado=?, critico=?, dataAtualizacao=NOW() WHERE id=?");
    $stmt->bind_param("ssssi", $assunto, $prazo, $estado, $critico, $id);

    if (!$stmt->execute()) {
        throw new Exception("Erro ao atualizar assunto: " . $stmt->error);
    }
    $stmt->close();

    // Busca ações atuais para comparação histórica
    $acoesAtuais = [];
    $stmtAcoesAtuais = $conn->prepare("SELECT id, acao, providencia, estado, responsavel FROM acoes WHERE assunto_id = ?");
    $stmtAcoesAtuais->bind_param("i", $id);
    $stmtAcoesAtuais->execute();
    $resultAcoes = $stmtAcoesAtuais->get_result();
    while ($row = $resultAcoes->fetch_assoc()) {
        $acoesAtuais[$row['id']] = $row;
    }
    $stmtAcoesAtuais->close();

    // Processa as ações
    $acoesExistentes = [];
    foreach ($acoes as $index => $acao) {
        $acaoId = isset($acao['id']) && is_numeric($acao['id']) && $acao['id'] > 0 ? intval($acao['id']) : null;
        $acaoTxt = trim($acao['acao'] ?? '');
        $providencia = trim($acao['providencia'] ?? '');
        $estadoAcao = $acao['estado'] ?? 'pendente';
        
        // Para ações existentes, manter o responsável original. Para novas ações, usar o usuário atual
        $responsavel = $acaoId && isset($acoesAtuais[$acaoId]) ? $acoesAtuais[$acaoId]['responsavel'] : $usuarioId;

        // Pula ações vazias
        if (empty($acaoTxt)) {
            file_put_contents('debug_edit_assunto.log', date('Y-m-d H:i:s') . " - Ação $index pulada por estar vazia\n", FILE_APPEND);
            continue;
        }

        if ($acaoId) {
            
            // Verifica mudanças na ação para o histórico
            if (isset($acoesAtuais[$acaoId])) {
                $acaoAtual = $acoesAtuais[$acaoId];
                if ($acaoAtual['acao'] !== $acaoTxt) {
                    $mudancas[] = "Modificou ação: \"{$acaoAtual['acao']}\" → \"$acaoTxt\"";
                }
                if ($acaoAtual['providencia'] !== $providencia) {
                    $provAtual = $acaoAtual['providencia'] ?: 'Vazia';
                    $provNova = $providencia ?: 'Vazia';
                    $mudancas[] = "Modificou providência da ação \"$acaoTxt\": \"$provAtual\" → \"$provNova\"";
                }
                if ($acaoAtual['estado'] !== $estadoAcao) {
                    $estAtual = $acaoAtual['estado'] === 'concluido' ? 'Concluído' : 'Pendente';
                    $estNovo = $estadoAcao === 'concluido' ? 'Concluído' : 'Pendente';
                    $mudancas[] = "Alterou estado da ação \"$acaoTxt\" de \"$estAtual\" para \"$estNovo\"";
                }
            }
            
            $stmtAcao = $conn->prepare("UPDATE acoes SET acao=?, providencia=?, estado=?, responsavel=?, dataAtualizacao=NOW() WHERE id=? AND assunto_id=?");
            $stmtAcao->bind_param("ssssii", $acaoTxt, $providencia, $estadoAcao, $responsavel, $acaoId, $id);

            if (!$stmtAcao->execute()) {
                throw new Exception("Erro ao atualizar ação ID $acaoId: " . $stmtAcao->error);
            } 
            $stmtAcao->close();
            $acoesExistentes[] = $acaoId;
        } else {
            $mudancas[] = "Adicionou nova ação: \"$acaoTxt\"";
            
            $stmtAcao = $conn->prepare("INSERT INTO acoes (assunto_id, acao, providencia, estado, responsavel, dataAtualizacao) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmtAcao->bind_param("isssi", $id, $acaoTxt, $providencia, $estadoAcao, $responsavel);

            if (!$stmtAcao->execute()) {
                $error = "Erro ao inserir nova ação: " . $stmtAcao->error;
                file_put_contents('debug_edit_assunto.log', date('Y-m-d H:i:s') . " - $error\n", FILE_APPEND);
                throw new Exception($error);
            } else {
                $novoId = $conn->insert_id;
                $acoesExistentes[] = $novoId;
            }
            $stmtAcao->close();
        }
    }

    // Identificar ações removidas para o histórico
    $acoesRemovidas = [];
    foreach ($acoesAtuais as $acaoId => $acaoAtual) {
        if (!in_array($acaoId, $acoesExistentes)) {
            $acoesRemovidas[] = $acaoAtual['acao'];
        }
    }
    
    if (!empty($acoesRemovidas)) {
        foreach ($acoesRemovidas as $acaoRemovida) {
            $mudancas[] = "Removeu ação: \"$acaoRemovida\"";
        }
    }
    
    if (!empty($acoesExistentes)) {
        // Há ações existentes que devem ser mantidas - remove apenas as não listadas
        $placeholders = str_repeat('?,', count($acoesExistentes) - 1) . '?';
        $stmtDelete = $conn->prepare("DELETE FROM acoes WHERE assunto_id = ? AND id NOT IN ($placeholders)");

        // Bind parameters: primeiro o assunto_id, depois os IDs das ações
        $types = 'i' . str_repeat('i', count($acoesExistentes));
        $params = array_merge([$id], $acoesExistentes);
        $stmtDelete->bind_param($types, ...$params);
        $stmtDelete->execute();
        $linhasAfetadas = $stmtDelete->affected_rows;
        $stmtDelete->close();
    } else {
        // Se não há ações existentes (todas foram removidas ou são novas), remove todas as ações antigas
        $stmtDelete = $conn->prepare("DELETE FROM acoes WHERE assunto_id = ?");
        $stmtDelete->bind_param("i", $id);
        $stmtDelete->execute();
        $linhasAfetadas = $stmtDelete->affected_rows;
        $stmtDelete->close();
    }

    // Registra no histórico todas as mudanças realizadas
    if (!empty($mudancas)) {
        $acaoHistorico = implode("; ", $mudancas);
        $stmtHistorico = $conn->prepare("INSERT INTO historico (assunto_id, data, usuario, acao) VALUES (?, NOW(), ?, ?)");
        $stmtHistorico->bind_param("iis", $id, $usuarioId, $acaoHistorico);
        if (!$stmtHistorico->execute()) {
          }
        $stmtHistorico->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Assunto atualizado com sucesso',
        'id' => $id
    ]);

} catch (Exception $e) {
    $conn->rollback();

    // Log detalhado do erro
    $errorMsg = "Erro ao editar assunto ID $id: " . $e->getMessage();

    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => $errorMsg
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>