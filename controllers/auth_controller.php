<?php
// controllers/auth_controller.php
session_start();
require_once '../config/db.php';

// Verificar si se envió un formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'login':
            handleLogin($conn);
            break;
        case 'register_client':
            handleClientRegistration($conn);
            break;
        case 'register_company':
            handleCompanyRegistration($conn);
            break;
        default:
            sendResponse(false, 'Acción no válida');
            break;
    }
} else {
    // Si alguien accede directamente a este archivo
    header('Location: ../index.php');
    exit;
}

// Función para manejar login
function handleLogin($conn) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        sendResponse(false, 'Por favor, complete todos los campos');
        return;
    }
    
    // Verificar primero en la tabla de clientes
    $stmt = $conn->prepare("SELECT id, nombre, password FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Es un cliente
       $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Contraseña correcta
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_type'] = 'cliente';
            
            // Redirigir directamente al dashboard de cliente
            header('Location: ../views/cliente/dashboard_cliente.php');
            exit;
        } else {
            sendResponse(false, 'Contraseña incorrecta');
        } 
        
    } else {
        // Verificar en la tabla de empresas
        $stmt = $conn->prepare("SELECT id, ruc, password FROM empresas WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Es una empresa
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Contraseña correcta
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['ruc']; // Usamos RUC como nombre
                $_SESSION['user_type'] = 'empresa';
                
                // Redirigir directamente al dashboard de empresa
                header('Location: ../views/empresa/dashboard_empresa.php');
                exit;
            } else {
                sendResponse(false, 'Contraseña incorrecta');
            }
        } else {
            sendResponse(false, 'Usuario no encontrado');
        }
    }
    
    $stmt->close();
}

// Función para manejar registro de clientes
function handleClientRegistration($conn) {
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
        sendResponse(false, 'Por favor, complete todos los campos');
        return;
    }
    
    if ($password !== $confirm_password) {
        sendResponse(false, 'Las contraseñas no coinciden');
        return;
    }
    
    // Verificar si el email ya existe en clientes o empresas
    if (emailExists($conn, $email)) {
        sendResponse(false, 'El correo electrónico ya está registrado');
        return;
    }
    
    // Hash de la contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO clientes (nombre, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $hash);
    
    if ($stmt->execute()) {
        // Iniciar sesión automáticamente después del registro
        $user_id = $conn->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['user_type'] = 'cliente';
        
        // Redirigir al dashboard de cliente
        header('Location: ../views/cliente/dashboard_cliente.php');
        exit;
    } else {
        sendResponse(false, 'Error al registrar: ' . $conn->error);
    }
    
    $stmt->close();
}

// Función para manejar registro de empresas
function handleCompanyRegistration($conn) {
    $ruc = filter_input(INPUT_POST, 'ruc', FILTER_SANITIZE_NUMBER_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validaciones
    if (empty($ruc) || empty($email) || empty($password) || empty($confirm_password)) {
        sendResponse(false, 'Por favor, complete todos los campos');
        return;
    }
    
    if (strlen($ruc) !== 11 || !ctype_digit($ruc)) {
        sendResponse(false, 'El RUC debe tener exactamente 11 dígitos numéricos');
        return;
    }
    
    if ($password !== $confirm_password) {
        sendResponse(false, 'Las contraseñas no coinciden');
        return;
    }
    
    // Verificar si el email ya existe en clientes o empresas
    if (emailExists($conn, $email)) {
        sendResponse(false, 'El correo electrónico ya está registrado');
        return;
    }
    
    // Verificar si el RUC ya existe
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE ruc = ?");
    $stmt->bind_param("s", $ruc);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        sendResponse(false, 'El RUC ya está registrado');
        $stmt->close();
        return;
    }
    
    // Hash de la contraseña
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Insertar en la base de datos
    $stmt = $conn->prepare("INSERT INTO empresas (ruc, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $ruc, $email, $hash);
    
    if ($stmt->execute()) {
        // Iniciar sesión automáticamente después del registro
        $user_id = $conn->insert_id;
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $ruc;
        $_SESSION['user_type'] = 'empresa';
        
        // Redirigir al dashboard de empresa
        header('Location: ../views/empresa/dashboard_empresa.php');
        exit;
    } else {
        sendResponse(false, 'Error al registrar: ' . $conn->error);
    }
    
    $stmt->close();
}

// Verificar si un email ya existe
function emailExists($conn, $email) {
    // Verificar en clientes
    $stmt = $conn->prepare("SELECT id FROM clientes WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $clienteExists = ($result->num_rows > 0);
    $stmt->close();
    
    if ($clienteExists) {
        return true;
    }
    
    // Verificar en empresas
    $stmt = $conn->prepare("SELECT id FROM empresas WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $empresaExists = ($result->num_rows > 0);
    $stmt->close();
    
    return $empresaExists;
}

// Función para enviar respuesta en formato JSON (sólo para errores ahora)
function sendResponse($success, $message, $redirect = false) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>