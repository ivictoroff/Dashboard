<?php
// logout.php
session_start(); // Inicia a sessão

// Destrói todas as variáveis de sessão
$_SESSION = array();

// Se for desejado destruir completamente a sessão, também deleta o cookie de sessão.
// Nota: Isso irá destruir a sessão, e não apenas a dados da sessão!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destrói a sessão
session_destroy();

// Redireciona para a página de login ou página inicial
header('Location: index.php');
exit();
?>