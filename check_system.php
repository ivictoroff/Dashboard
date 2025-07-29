<?php
// Script de verificação do sistema para produção
// Execute este script para verificar se o sistema está configurado corretamente

header('Content-Type: text/plain; charset=utf-8');

echo "=== VERIFICAÇÃO DO SISTEMA ===\n\n";

// Verificar PHP
echo "1. Versão do PHP: " . phpversion() . "\n";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "   AVISO: PHP 7.4+ recomendado\n";
}

// Verificar extensões necessárias
echo "\n2. Extensões PHP:\n";
$required_extensions = ['mysqli', 'json', 'session'];
foreach ($required_extensions as $ext) {
    echo "   - $ext: " . (extension_loaded($ext) ? "OK" : "FALTANDO") . "\n";
}

// Verificar conexão com banco
echo "\n3. Conexão com banco de dados:\n";
try {
    require_once 'db.php';
    echo "   - Conexão: OK\n";
    
    // Verificar tabelas
    $tables = ['usuarios', 'perfis', 'assuntos', 'chefia', 'divisao', 'historico', 'acoes'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "   - Tabela $table: " . ($result->num_rows > 0 ? "OK" : "FALTANDO") . "\n";
    }
    $conn->close();
} catch (Exception $e) {
    echo "   - Conexão: ERRO - " . $e->getMessage() . "\n";
}

// Verificar arquivos críticos
echo "\n4. Arquivos críticos:\n";
$critical_files = [
    'index.php',
    'home.php',
    'login.php',
    'logout.php',
    'db.php',
    'api/get_assuntos.php',
    'api/get_usuarios.php'
];

foreach ($critical_files as $file) {
    echo "   - $file: " . (file_exists($file) ? "OK" : "FALTANDO") . "\n";
}

// Verificar permissões
echo "\n5. Permissões:\n";
echo "   - Diretório raiz: " . (is_writable('.') ? "ESCRITA OK" : "SEM ESCRITA") . "\n";
echo "   - Diretório api: " . (is_writable('api') ? "ESCRITA OK" : "SEM ESCRITA") . "\n";

// Verificar configurações de segurança
echo "\n6. Configurações de segurança:\n";
echo "   - display_errors: " . (ini_get('display_errors') ? "ATIVO (mudar para produção)" : "DESATIVO") . "\n";
echo "   - HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "ATIVO" : "INATIVO") . "\n";

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
echo "Execute este script após implantação em produção.\n";
?>
