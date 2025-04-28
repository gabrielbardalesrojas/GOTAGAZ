<?php
session_start();
require_once '../../controllers/session_controller.php';
require_once '../../config/db.php';

// Verificar si hay una sesión activa
checkSession();

// Obtener información del usuario y verificar que sea una empresa
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];

// Redireccionar si no es una empresa
if ($userType !== 'empresa') {
    header('Location: ../cliente/dashboard_cliente.php');
    exit();
}

// Verificar que se enviaron datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id']) && isset($_POST['nuevo_estado'])) {
    // Obtener y sanitizar los datos del formulario
    $pedidoId = intval($_POST['pedido_id']);
    $nuevoEstado = trim($_POST['nuevo_estado']);
    $comentario = isset($_POST['comentario']) ? trim(htmlspecialchars($_POST['comentario'])) : '';
    
    // Validar el estado
    $estadosValidos = ['pendiente', 'en_proceso', 'en_camino', 'entregado', 'cancelado'];
    if (!in_array($nuevoEstado, $estadosValidos)) {
        header('Location: pedidos.php?error=2');
        exit();
    }
    
    // Verificar que el pedido pertenezca a esta empresa
    $stmt = $conn->prepare("SELECT id FROM pedidos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $pedidoId, $userId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        // El pedido no existe o no pertenece a esta empresa
        header('Location: pedidos.php?error=3');
        exit();
    }
    $stmt->close();
    
    // Actualizar el estado del pedido
    $stmt = $conn->prepare("UPDATE pedidos SET estado = ?, ultimo_comentario = ?, fecha_actualizacion = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $nuevoEstado, $comentario, $pedidoId);
    
    if ($stmt->execute()) {
        // Registrar el cambio de estado en el historial
        $stmtHistorial = $conn->prepare("INSERT INTO historial_pedidos (pedido_id, estado_anterior, estado_nuevo, comentario, usuario_id) VALUES (?, (SELECT estado FROM pedidos WHERE id = ?), ?, ?, ?)");
        $stmtHistorial->bind_param("iissi", $pedidoId, $pedidoId, $nuevoEstado, $comentario, $userId);
        $stmtHistorial->execute();
        $stmtHistorial->close();
        
        // Redirigir con mensaje de éxito
        header('Location: pedidos.php?success=1');
        exit();
    } else {
        // Redirigir con mensaje de error
        header('Location: pedidos.php?error=1');
        exit();
    }
    
    $stmt->close();
} else {
    // Si no es POST o faltan datos, redirigir a la página de pedidos
    header('Location: pedidos.php');
    exit();
}