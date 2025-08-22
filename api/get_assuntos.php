<?php
session_start();
require_once '../session_check.php';

// Verificar permissões baseadas no perfil
$perfil = $_SESSION['perfil_id'] ?? 0;

// Apenas perfis 1 (Suporte), 2 (Auditor OM), 3 (Auditor COLOG) e Perfil 4 (Editor) podem acessar assuntos
if (!in_array($perfil, [1, 2, 3, 4])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado. Você não tem permissão para visualizar assuntos.']);
    exit();
}
header('Content-Type: application/json');
require_once '../db.php';

try {
    $filterType = $_GET['filter'] ?? 'todos';
    $dataInicio = $_GET['dataInicio'] ?? null;
    $dataFim = $_GET['dataFim'] ?? null;
    
    // Obter informações do usuário logado
    $perfilId = $_SESSION['perfil_id'] ?? 2;
    $chefiaId = $_SESSION['chefia_id'] ?? null;

    $sql = "
        SELECT 
            a.id AS assunto_id,
            a.assunto,
            a.critico,
            a.prazo,
            a.estado,
            a.dataAtualizacao,
            u.idt_Mil AS criadoPor,
            u.pg AS criadoPorPg,
            u.nome AS criadoPorNome,
            ch.nome AS chefia,
            d.nome AS divisao,

            ac.id AS acao_id,
            ac.acao,
            ac.providencia,
            ac.estado AS acao_estado,
            ac.responsavel,
            ac.dataAtualizacao AS acao_dataAtualizacao,
            ur.pg AS responsavel_pg,
            ur.nome AS responsavel_nome,

            h.id AS historico_id,
            h.data AS historico_data,
            h.usuario AS historico_usuario,
            h.acao AS historico_acao,
            uh.pg AS historico_usuario_pg,
            uh.nome AS historico_usuario_nome

        FROM assuntos a
        INNER JOIN usuarios u ON u.id = a.criadoPor
        INNER JOIN chefia ch ON ch.id = u.chefia_id
        INNER JOIN divisao d ON d.id = u.divisao_id
        LEFT JOIN acoes ac ON ac.assunto_id = a.id
        LEFT JOIN usuarios ur ON ur.id = ac.responsavel
        LEFT JOIN historico h ON h.assunto_id = a.id
        LEFT JOIN usuarios uh ON uh.id = h.usuario
        WHERE a.ativo = 1
    ";

    $params = [];
    $types = '';

    // Filtro baseado no perfil do usuário
    if ($perfilId === 2) { // Auditor OM/Chefia
        if ($chefiaId) {
            $sql .= " AND ch.id = ?";
            $params[] = $chefiaId;
            $types .= 'i';
        } else {
            // Se não tem chefia definida, não deve ver nenhum assunto
            $sql .= " AND 1 = 0";
        }
    } elseif ($perfilId === 4) { // Editor
        if ($chefiaId) {
            $sql .= " AND ch.id = ?";
            $params[] = $chefiaId;
            $types .= 'i';
        } else {
            // Se não tem chefia definida, não deve ver nenhum assunto
            $sql .= " AND 1 = 0";
        }
    }
    // Perfis 1 e 3 veem todos os assuntos (com possível filtro adicional no frontend)

    // Filtros
    if ($filterType === 'criticos') {
        $sql .= " AND a.critico = ?";
        $params[] = 'sim';
        $types .= 's';
    } elseif ($filterType === 'pendentes') {
        $sql .= " AND a.estado = ?";
        $params[] = 'pendente';
        $types .= 's';
    } elseif ($filterType === 'concluidos') {
        $sql .= " AND a.estado = ?";
        $params[] = 'concluido';
        $types .= 's';
    }

    if ($dataInicio) {
        $sql .= " AND a.prazo >= ?";
        $params[] = $dataInicio;
        $types .= 's';
    }
    if ($dataFim) {
        $sql .= " AND a.prazo <= ?";
        $params[] = $dataFim;
        $types .= 's';
    }

    $sql .= " ORDER BY a.id";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Erro na preparação da consulta: " . $conn->error);

    if (!empty($params)) {
        $bindParams = array_merge([$types], $params);
        $refs = [];
        foreach ($bindParams as $k => $v) {
            $refs[$k] = &$bindParams[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $assuntos = [];
    while ($row = $result->fetch_assoc()) {
        $id = $row['assunto_id'];

        if (!isset($assuntos[$id])) {
            $assuntos[$id] = [
                'id' => $id,
                'assunto' => $row['assunto'],
                'critico' => $row['critico'],
                'prazo' => $row['prazo'],
                'chefia' => $row['chefia'],
                'divisao' => $row['divisao'],
                'estado' => $row['estado'],
                'dataAtualizacao' => $row['dataAtualizacao'],
                'criadoPor' => $row['criadoPor'],
                'criadoPorPg' => $row['criadoPorPg'],
                'criadoPorNome' => $row['criadoPorNome'],
                'acoes' => [],
                'historico' => []
            ];
        }

        if (!empty($row['acao_id']) && !isset($assuntos[$id]['acoes'][$row['acao_id']])) {
            $assuntos[$id]['acoes'][$row['acao_id']] = [
                'id' => $row['acao_id'],
                'acao' => $row['acao'],
                'providencia' => $row['providencia'],
                'estado' => $row['acao_estado'],
                'responsavel' => $row['responsavel'],
                'responsavelPg' => $row['responsavel_pg'],
                'responsavelNome' => $row['responsavel_nome'],
                'dataAtualizacao' => $row['acao_dataAtualizacao']
            ];
        }

        if (!empty($row['historico_id']) && !isset($assuntos[$id]['historico'][$row['historico_id']])) {
            $assuntos[$id]['historico'][$row['historico_id']] = [
                'id' => $row['historico_id'],
                'data' => $row['historico_data'],
                'usuario' => $row['historico_usuario'],
                'usuarioPg' => $row['historico_usuario_pg'],
                'usuarioNome' => $row['historico_usuario_nome'],
                'acao' => $row['historico_acao']
            ];
        }
    }

    // Converte arrays associativos de ações/histórico em arrays numéricos
    $assuntos = array_values(array_map(function ($a) {
        $a['acoes'] = array_values($a['acoes']);
        $a['historico'] = array_values($a['historico']);
        return $a;
    }, $assuntos));

    echo json_encode($assuntos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $stmt->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
