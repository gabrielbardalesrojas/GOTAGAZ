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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario
    $nombre = trim(htmlspecialchars($_POST['nombre']));
    $descripcion = trim(htmlspecialchars($_POST['descripcion']));
    $precio = floatval($_POST['precio']);
    $stock = intval($_POST['stock']);
    $disponible = isset($_POST['disponible']) ? 1 : 0;
    
    // Inicializar variable para el nombre de archivo de imagen
    $nombreImagen = '';
    
    // Procesar la imagen si se ha enviado
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
        
        // Generar nombre único para la imagen
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
    }
    
    // Insertar el producto en la base de datos
    $stmt = $conn->prepare("INSERT INTO productos (empresa_id, nombre, descripcion, precio, stock, disponible, imagen) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdiis", $userId, $nombre, $descripcion, $precio, $stock, $disponible, $nombreImagen);
    
    if ($stmt->execute()) {
        // Redirigir con mensaje de éxito
        header('Location: producto.php?success=2');
        exit();
    } else {
        // Si hay error al insertar pero se subió una imagen, eliminarla
        if (!empty($nombreImagen) && file_exists('../../assets/img/producto/' . $nombreImagen)) {
            unlink('../../assets/img/producto/' . $nombreImagen);
        }
        
        // Redirigir con mensaje de error
        header('Location: producto.php?error=3');
        exit();
    }
    
    $stmt->close();
} else {
    // Si no es POST, redirigir a la página de productos
    header('Location: producto.php');
    exit();
}