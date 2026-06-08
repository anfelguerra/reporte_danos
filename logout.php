<?php
// 1. Inicializar la sesión existente
session_start();

// 2. Desvincular todas las variables de sesión almacenadas (usuario_id, rol, etc.)
$_SESSION = array();

// 3. Destruir físicamente la cookie de sesión en el navegador si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destruir la sesión en el servidor
session_destroy();

// 5. Redireccionar de inmediato a la pantalla de login centralizada
header("Location: login.php");
exit();
?>
