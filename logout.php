<?php
// logout.php
session_start();
require_once 'controllers/session_controller.php';

// Destruir la sesión
destroySession();

// Redirigir al index
header("Location: index.php");
exit;
?>