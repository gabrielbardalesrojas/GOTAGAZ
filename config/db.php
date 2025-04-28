<?php
// config/db.php
$host = 'localhost';
$user = 'root'; 
$password = ''; // Por defecto en Laragon suele estar vacío
$database = 'gotagasgo';

// Crear conexión
$conn = new mysqli($host, $user, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer codificación de caracteres
$conn->set_charset("utf8");
?>