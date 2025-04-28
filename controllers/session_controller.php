<?php
// controllers/session_controller.php

// Verificar si hay una sesión activa
function checkSession() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
}

// Destruir la sesión
function destroySession() {
    // Eliminar todas las variables de sesión
    $_SESSION = array();
    
    // Si se desea destruir la cookie de sesión también
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destruir la sesión
    session_destroy();
}
?>