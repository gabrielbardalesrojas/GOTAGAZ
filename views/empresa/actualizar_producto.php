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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Obtener y sanitizar los datos del formulario
    $productoId = intval($_POST['id']);
    $nombre = trim(htmlspecialchars($_POST['nombre']));
    $descripcion = trim(htmlspecialchars($_POST['descripcion']));
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    $imagenActual = $_POST['imagen_actual'];
    
    // Verificar que el producto pertenezca a esta empresa
    $stmt = $conn->prepare("SELECT id, imagen FROM productos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $productoId, $userId);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        // El producto no existe o no pertenece a esta empresa
        header('Location: producto.php?error=2');
        exit();
    }
    
    $producto = $resultado->fetch_assoc();
    $stmt->close();
    
    // Variable para la consulta SQL
    $nombreImagen = $imagenActual;
    
    // Procesar la imagen si se ha enviado una nueva
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imagen = $_FILES['imagen'];
        
        // Validar tipo de archivo
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
        if (!in_array($imagen['type'], $tiposPermitidos)) {
            header('Location: producto.php?error=5'); // Error: Tipo de archivo no permitido
            exit();
        }
        
        // Validar tamaño de archivo (máximo 2MB)
        if ($imagen['size'] > 2 * 1024 * 1024) {
            header('Location: producto.php?error=6'); // Error: Archivo demasiado grande
            exit();
        }
        
        // Generar nombre único para la nueva imagen
        $nombreImagen = uniqid('prod_') . '_' . time() . '.' . pathinfo($imagen['name'], PATHINFO_EXTENSION);
        
        // Crear directorio si no existe
        $directorioDestino = '../../assets/img/producto/';
        if (!is_dir($directorioDestino)) {
            mkdir($directorioDestino, 0755, true);
        }
        
        // Mover el archivo al directorio de destino
        if (!move_uploaded_file($imagen['tmp_name'], $directorioDestino . $nombreImagen)) {
            header('Location: producto.php?error=7'); // Error al subir la imagen
            exit();
        }
        
        // Eliminar la imagen anterior si existe
        if (!empty($producto['imagen']) && file_exists($directorioDestino . $producto['imagen'])) {
            unlink($directorioDestino . $producto['imagen']);
        }
    }
    
    // Actualizar el producto en la base de datos
    $stmt = $conn->prepare("UPDATE productos SET nombre = ?, descripcion = ?, precio = ?, stock = ?, disponible = ?, imagen = ? WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ssdiisii", $nombre, $descripcion, $precio, $stock, $disponible, $nombreImagen, $productoId, $userId);
    
    if ($stmt->execute()) {
        // Redirigir con mensaje de éxito
        header('Location: producto.php?success=3');
        exit();
    } else {
        // Si hay error al actualizar pero se subió una imagen nueva, eliminarla
        if ($nombreImagen !== $imagenActual && file_exists('../../assets/img/producto/' . $nombreImagen)) {
            unlink('../../assets/img/producto/' . $nombreImagen);
        }
        
        // Redirigir con mensaje de error
        header('Location: productos.php?error=4');
        exit();
    }
    
    $stmt->close();
} else {
    // Si no es POST o no se especificó ID, redirigir a la página de productos
    header('Location: productos.php');
    exit();
}