<?php
session_start();
require_once '../../controllers/session_controller.php';
require_once '../../config/db.php';

// Verificar si hay una sesión activa
checkSession();

// Obtener información del usuario y verificar que sea una empresa
$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'];
$userType = $_SESSION['user_type'];

// Redireccionar si no es una empresa
if ($userType !== 'empresa') {
    header('Location: ../cliente/dashboard_cliente.php');
    exit();
}

// Mensaje de actualización (si existe)
$message = '';
$messageType = '';

// Procesar el formulario si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger los datos del formulario
    $razonSocial = trim($_POST['razon_social']);
    $email = trim($_POST['email']);
    $telefono = trim($_POST['telefono']);
    $whatsapp = trim($_POST['whatsapp']);
    $direccion = trim($_POST['direccion']);
    $descripcion = trim($_POST['descripcion']);
    $horarioAtencion = trim($_POST['horario_atencion']);
    $latitud = isset($_POST['latitud']) ? trim($_POST['latitud']) : '';
$longitud = isset($_POST['longitud']) ? trim($_POST['longitud']) : '';
    
    // Validar campos obligatorios
    if (empty($razonSocial) || empty($email) || empty($telefono)) {
        $message = 'Los campos marcados con * son obligatorios';
        $messageType = 'danger';
    } else {
        // Procesar la imagen si se ha subido una nueva
       // Procesar la imagen si se ha subido una nueva
$logoPath = '';
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
    $fileType = $_FILES['logo']['type'];
    
    if (in_array($fileType, $allowedTypes)) {
        // Create directory if it doesn't exist
        $uploadDir = '../../assets/img/uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid('empresa_') . '_' . basename($_FILES['logo']['name']);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetPath)) {
            $logoPath = $fileName;
        } else {
            $message = 'Error al subir la imagen. Ruta: ' . $targetPath;
            $messageType = 'danger';
        }
    } else {
        $message = 'Solo se permiten archivos JPG, PNG y GIF';
        $messageType = 'danger';
    }
}
        
        // Si no hay errores, actualizar la base de datos
        if (empty($message)) {
            // Preparar la consulta SQL
            $sql = "UPDATE empresas SET razon_social = ?, email = ?, telefono = ?, whatsapp = ?, direccion = ?, descripcion = ?, horario_atencion = ?, latitud = ?, longitud = ?";
            $params = [$razonSocial, $email, $telefono, $whatsapp, $direccion, $descripcion, $horarioAtencion, $latitud, $longitud];
            $types = "sssssssdd"; // string, string, string, string, string, string, string, double, double
            
            // Añadir logo si se ha subido uno nuevo
            if (!empty($logoPath)) {
                $sql .= ", logo = ?";
                $params[] = $logoPath;
                $types .= "s";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $message = 'Perfil actualizado correctamente';
                $messageType = 'success';
                
                // Actualizar el nombre de usuario en la sesión si cambió
                $_SESSION['user_name'] = $razonSocial;
            } else {
                $message = 'Error al actualizar el perfil: ' . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        }
    }
}

// Obtener información detallada de la empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$empresaInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Procesar el formulario para cambiar la contraseña
if (isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verificar la contraseña actual
    $stmt = $conn->prepare("SELECT password FROM empresas WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
    
    if (password_verify($currentPassword, $userData['password'])) {
        // Verificar que las nuevas contraseñas coincidan
        if ($newPassword === $confirmPassword) {
            // Actualizar la contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE empresas SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashedPassword, $userId);
            
            if ($stmt->execute()) {
                $passwordMessage = 'Contraseña actualizada correctamente';
                $passwordMessageType = 'success';
            } else {
                $passwordMessage = 'Error al actualizar la contraseña: ' . $conn->error;
                $passwordMessageType = 'danger';
            }
            
            $stmt->close();
        } else {
            $passwordMessage = 'Las nuevas contraseñas no coinciden';
            $passwordMessageType = 'danger';
        }
    } else {
        $passwordMessage = 'La contraseña actual es incorrecta';
        $passwordMessageType = 'danger';
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - GOTAGAS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: #fff;
            padding: 0.8rem 1rem;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link.active {
            background-color: rgba(255, 255, 255, 0.2);
        }
        
        .main-content {
            margin-left: 240px;
            padding: 2rem 1.5rem;
        }
        
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 10px 15px;
        }
        
        .nav-tabs .nav-link.active {
            color: #007bff;
            background-color: transparent;
            border-bottom: 2px solid #007bff;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .required:after {
            content: " *";
            color: red;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                padding-top: 0;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
        <div class="position-sticky sidebar-sticky">
            <div class="d-flex align-items-center flex-column justify-content-center mb-3">
                <img src="../../assets/img/gotita.jpg" alt="Imagen de GOTAGAS" class="rounded-circle mt-2" style="width: 80px; height: 80px;">
                <a class="navbar-brand text-light" href="dashboard_empresa.php">
                    <i class="fas fa-gas-pump me-2"></i>GOTAGAS
                </a>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard_empresa.php">
                        <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="pedidos.php">
                        <i class="fas fa-clipboard-list me-2"></i> Pedidos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="producto.php">
                        <i class="fas fa-box me-2"></i> Productos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="clientes.php">
                        <i class="fas fa-users me-2"></i> Clientes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cobertura.php">
                        <i class="fas fa-map-marked-alt me-2"></i> Ubicación en el mapa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reportes.php">
                        <i class="fas fa-chart-bar me-2"></i> Reportes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="perfil.php">
                        <i class="fas fa-user-cog me-2"></i> Mi Perfil
                    </a>
                </li>
                <li class="nav-item mt-2">
                    <a class="nav-link text-danger" href="../../logout.php">
                        <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Profile Header -->
        <div class="profile-header d-flex align-items-center">
            <div class="text-center me-4">
                <?php if (!empty($empresaInfo['logo'])): ?>
                    <img src="../../assets/img/uploads/<?php echo $empresaInfo['logo']; ?>" alt="Logo de la empresa" class="profile-img">
                <?php else: ?>
                    <div class="profile-img d-flex align-items-center justify-content-center bg-primary text-white">
                        <i class="fas fa-building fa-3x"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="fs-2 mb-1"><?php echo htmlspecialchars($empresaInfo['razon_social'] ?? $userName); ?></h1>
                <p class="text-muted mb-0"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($empresaInfo['email']); ?></p>
                <p class="text-muted mb-0"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($empresaInfo['telefono'] ?? 'No especificado'); ?></p>
                <p class="text-muted mb-0"><i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($empresaInfo['direccion'] ?? 'No especificado'); ?></p>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($passwordMessage)): ?>
            <div class="alert alert-<?php echo $passwordMessageType; ?> alert-dismissible fade show" role="alert">
                <?php echo $passwordMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Tabs -->
        <ul class="nav nav-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                    <i class="fas fa-info-circle me-2"></i>Información General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="ubicacion-tab" data-bs-toggle="tab" data-bs-target="#ubicacion" type="button" role="tab" aria-controls="ubicacion" aria-selected="false">
                    <i class="fas fa-map-marker-alt me-2"></i>Ubicación
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                    <i class="fas fa-lock me-2"></i>Seguridad
                </button>
            </li>
        </ul>

        <div class="tab-content" id="profileTabsContent">
            <!-- Información General Tab -->
            <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Actualizar Información</h5>
                    </div>
                    <div class="card-body">
                        <form action="perfil.php" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ruc" class="form-label">RUC</label>
                                    <input type="text" class="form-control" id="ruc" value="<?php echo htmlspecialchars($empresaInfo['ruc']); ?>" readonly>
                                    <small class="text-muted">El RUC no se puede modificar</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="razon_social" class="form-label required">Razón Social</label>
                                    <input type="text" class="form-control" id="razon_social" name="razon_social" value="<?php echo htmlspecialchars($empresaInfo['razon_social'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label required">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($empresaInfo['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="telefono" class="form-label required">Teléfono</label>
                                    <input type="tel" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($empresaInfo['telefono'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="whatsapp" class="form-label">WhatsApp</label>
                                    <input type="tel" class="form-control" id="whatsapp" name="whatsapp" value="<?php echo htmlspecialchars($empresaInfo['whatsapp'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="horario_atencion" class="form-label">Horario de Atención</label>
                                    <input type="text" class="form-control" id="horario_atencion" name="horario_atencion" value="<?php echo htmlspecialchars($empresaInfo['horario_atencion'] ?? ''); ?>" placeholder="Ej: Lunes a Viernes 8:00 am - 6:00 pm">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($empresaInfo['direccion'] ?? ''); ?></textarea>
                            </div>
                            <div class="row">
    <div class="col-md-6 mb-3">
        <label for="latitud" class="form-label">Latitud</label>
        <input type="text" class="form-control" id="latitud" name="latitud" value="<?php echo htmlspecialchars($empresaInfo['latitud'] ?? ''); ?>">
    </div>
    <div class="col-md-6 mb-3">
        <label for="longitud" class="form-label">Longitud</label>
        <input type="text" class="form-control" id="longitud" name="longitud" value="<?php echo htmlspecialchars($empresaInfo['longitud'] ?? ''); ?>">
    </div>
</div>

<div class="mb-3">
    <button type="button" id="btn-ubicacion" class="btn btn-info">
        <i class="fas fa-location-arrow me-2"></i>Obtener Mi Ubicación Actual
    </button>
</div>

<div class="mb-4">
    <div id="mapa" style="height: 400px; width: 100%; border-radius: 8px;"></div>
    <small class="text-muted">Haz clic en el mapa para seleccionar la ubicación o usa el botón para obtener tu ubicación actual</small>
</div>
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción de la Empresa</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($empresaInfo['descripcion'] ?? ''); ?></textarea>
                                <small class="text-muted">Describe brevemente tu empresa y los servicios que ofreces</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="logo" class="form-label">Logo de la Empresa</label>
                                <input type="file" class="form-control" id="logo" name="logo" accept="image/jpeg, image/png, image/gif">
                                <?php if (!empty($empresaInfo['logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../../assets/uploads/<?php echo $empresaInfo['logo']; ?>" alt="Logo actual" class="img-thumbnail" style="max-height: 100px;">
                                        <small class="text-muted d-block">Logo actual</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Ubicación Tab -->
            <div class="tab-pane fade" id="ubicacion" role="tabpanel" aria-labelledby="ubicacion-tab">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ubicación en el Mapa</h5>
                    </div>
                    <div class="card-body">
                        <form action="perfil.php" method="POST">
                            <!-- En la sección de ubicación del formulario -->


<div class="mb-4">
    <div id="mapa" style="height: 400px; width: 100%; border-radius: 8px;"></div>
    <small class="text-muted">Haz clic en el mapa para seleccionar la ubicación o usa el botón para obtener tu ubicación actual</small>
</div>
                            
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Seguridad Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Cambiar Contraseña</h5>
                    </div>
                    <div class="card-body">
                        <form action="perfil.php" method="POST">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label required">Contraseña Actual</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label required">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="text-muted">La contraseña debe tener al menos 8 caracteres</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label required">Confirmar Nueva Contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Cambiar Contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
    
    <script>
        // Inicializar el mapa
        function initMap() {
            // Coordenadas predeterminadas (Perú)
            const defaultLat = <?php echo !empty($empresaInfo['latitud']) ? $empresaInfo['latitud'] : '-12.046374'; ?>;
            const defaultLng = <?php echo !empty($empresaInfo['longitud']) ? $empresaInfo['longitud'] : '-77.042793'; ?>;
            
            const mapOptions = {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: 15,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            };
            
            const map = new google.maps.Map(document.getElementById('mapa'), mapOptions);
            
            // Marcador para la ubicación actual
            let marker = new google.maps.Marker({
                position: { lat: defaultLat, lng: defaultLng },
                map: map,
                draggable: true,
                title: 'Ubicación de tu empresa'
            });
            
            // Actualizar las coordenadas cuando se mueve el marcador
            google.maps.event.addListener(marker, 'dragend', function(event) {
                document.getElementById('latitud').value = event.latLng.lat();
                document.getElementById('longitud').value = event.latLng.lng();
            });
            
            // Permitir hacer clic en el mapa para cambiar la ubicación
            google.maps.event.addListener(map, 'click', function(event) {
                marker.setPosition(event.latLng);
                document.getElementById('latitud').value = event.latLng.lat();
                document.getElementById('longitud').value = event.latLng.lng();
            });
        }
        
        // Mostrar vista previa de la imagen
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'img-thumbnail mt-2';
                    preview.style.maxHeight = '100px';
                    
                    const previewContainer = document.getElementById('logo').parentNode;
                    
                    // Eliminar vista previa anterior si existe
                    const oldPreview = previewContainer.querySelector('.preview-img');
                    if (oldPreview) {
                        previewContainer.removeChild(oldPreview);
                    }
                    
                    // Añadir contenedor para la vista previa
                    const container = document.createElement('div');
                    container.className = 'mt-2 preview-img';
                    container.appendChild(preview);
                    
                    const label = document.createElement('small');
                    label.className = 'text-muted d-block';
                    label.textContent = 'Vista previa';
                    container.appendChild(label);
                    
                    previewContainer.appendChild(container);
                };
                reader.readAsDataURL(file);
            }
        });


        // Añade esto dentro de tu script existente
let map, marker;

// Inicializar el mapa
function initMap() {
    // Coordenadas predeterminadas (Perú)
    const defaultLat = <?php echo !empty($empresaInfo['latitud']) ? $empresaInfo['latitud'] : '-12.046374'; ?>;
    const defaultLng = <?php echo !empty($empresaInfo['longitud']) ? $empresaInfo['longitud'] : '-77.042793'; ?>;
    
    const mapOptions = {
        center: { lat: parseFloat(defaultLat), lng: parseFloat(defaultLng) },
        zoom: 15,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    
    map = new google.maps.Map(document.getElementById('mapa'), mapOptions);
    
    // Marcador para la ubicación actual
    marker = new google.maps.Marker({
        position: { lat: parseFloat(defaultLat), lng: parseFloat(defaultLng) },
        map: map,
        draggable: true,
        title: 'Ubicación de tu empresa'
    });
    
    // Actualizar las coordenadas cuando se mueve el marcador
    google.maps.event.addListener(marker, 'dragend', function(event) {
        document.getElementById('latitud').value = event.latLng.lat();
        document.getElementById('longitud').value = event.latLng.lng();
    });
    
    // Permitir hacer clic en el mapa para cambiar la ubicación
    google.maps.event.addListener(map, 'click', function(event) {
        marker.setPosition(event.latLng);
        document.getElementById('latitud').value = event.latLng.lat();
        document.getElementById('longitud').value = event.latLng.lng();
    });
    
    // Configurar el botón de obtener ubicación
    document.getElementById('btn-ubicacion').addEventListener('click', obtenerUbicacionActual);
}

// Función para obtener la ubicación del usuario
function obtenerUbicacionActual() {
    const btnUbicacion = document.getElementById('btn-ubicacion');
    
    btnUbicacion.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Obteniendo ubicación...';
    btnUbicacion.disabled = true;
    
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                // Éxito al obtener la ubicación
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Actualizar los campos del formulario
                document.getElementById('latitud').value = lat;
                document.getElementById('longitud').value = lng;
                
                // Actualizar el mapa y el marcador
                const nuevaPos = new google.maps.LatLng(lat, lng);
                marker.setPosition(nuevaPos);
                map.setCenter(nuevaPos);
                
                // Restaurar el botón
                btnUbicacion.innerHTML = '<i class="fas fa-location-arrow me-2"></i>Obtener Mi Ubicación Actual';
                btnUbicacion.disabled = false;
                
                // Mostrar mensaje de éxito
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                    <strong>¡Ubicación obtenida!</strong> Latitud: ${lat.toFixed(6)}, Longitud: ${lng.toFixed(6)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.getElementById('btn-ubicacion').parentNode.appendChild(alertDiv);
                
                // Remover la alerta después de 5 segundos
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            },
            function(error) {
                // Error al obtener la ubicación
                let mensajeError;
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        mensajeError = "No diste permiso para acceder a tu ubicación.";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        mensajeError = "La información de ubicación no está disponible.";
                        break;
                    case error.TIMEOUT:
                        mensajeError = "La petición de ubicación expiró.";
                        break;
                    case error.UNKNOWN_ERROR:
                    default:
                        mensajeError = "Ocurrió un error desconocido al obtener la ubicación.";
                        break;
                }
                
                // Restaurar el botón
                btnUbicacion.innerHTML = '<i class="fas fa-location-arrow me-2"></i>Obtener Mi Ubicación Actual';
                btnUbicacion.disabled = false;
                
                // Mostrar mensaje de error
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
                alertDiv.innerHTML = `
                    <strong>Error:</strong> ${mensajeError}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                document.getElementById('btn-ubicacion').parentNode.appendChild(alertDiv);
                
                // Remover la alerta después de 5 segundos
                setTimeout(() => {
                    alertDiv.remove();
                }, 5000);
            }
        );
    } else {
        // El navegador no soporta geolocalización
        btnUbicacion.innerHTML = '<i class="fas fa-location-arrow me-2"></i>Obtener Mi Ubicación Actual';
        btnUbicacion.disabled = false;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
        alertDiv.innerHTML = `
            <strong>Error:</strong> Tu navegador no soporta geolocalización.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.getElementById('btn-ubicacion').parentNode.appendChild(alertDiv);
    }
}
    </script>
</body>
</html>