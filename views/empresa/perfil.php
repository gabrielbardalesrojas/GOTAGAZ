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

// Configuración inicial - Token de autenticación para la API SUNAT
$token = 'apis-token-14619.f1zYpdbph2WW8q74cD2GQT8mpU2MMt40';

// Inicializar variables
$success = "";
$error = "";
$empresa = null;

// Obtener datos actuales de la empresa
$sql = "SELECT * FROM empresas WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $empresa = $result->fetch_assoc();
    
    // Si tenemos RUC pero no tenemos razón social o dirección, consultamos a la API SUNAT
    if (!empty($empresa['ruc']) && (empty($empresa['razon_social']) || empty($empresa['direccion']))) {
        $ruc = $empresa['ruc'];
        
        // Iniciar llamada a API
        $curl = curl_init();
        
        // Configurar la solicitud cURL para consultar el RUC
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.apis.net.pe/v2/sunat/ruc/full?numero=' . $ruc,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Referer: http://apis.net.pe/api-ruc',
                'Authorization: Bearer ' . $token
            ),
        ));
        
        // Ejecutar la solicitud
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        // Si no hay errores, actualizar los datos de la empresa
        if (!$err) {
            $apiResponse = json_decode($response, true);
            
            if (isset($apiResponse['razonSocial']) && isset($apiResponse['direccion'])) {
                $razonSocial = $apiResponse['razonSocial'];
                $direccion = $apiResponse['direccion'];
                
                // Actualizar en la base de datos
                $sqlUpdate = "UPDATE empresas SET razon_social = ?, direccion = ? WHERE id = ?";
                $stmtUpdate = $conn->prepare($sqlUpdate);
                $stmtUpdate->bind_param("ssi", $razonSocial, $direccion, $userId);
                $stmtUpdate->execute();
                
                // Actualizar variable de empresa
                $empresa['razon_social'] = $razonSocial;
                $empresa['direccion'] = $direccion;
            }
        }
    }
}

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $telefono = trim($_POST['telefono']);
    $whatsapp = trim($_POST['whatsapp']);
    $latitud = !empty($_POST['latitud']) ? $_POST['latitud'] : null;
    $longitud = !empty($_POST['longitud']) ? $_POST['longitud'] : null;
    
    // Validación básica
    if (strlen($telefono) < 6 && !empty($telefono)) {
        $error = "El número de teléfono debe tener al menos 6 dígitos";
    } elseif (strlen($whatsapp) < 6 && !empty($whatsapp)) {
        $error = "El número de WhatsApp debe tener al menos 6 dígitos";
    } else {
        // Preparar la consulta SQL
        $sqlFields = "UPDATE empresas SET telefono = ?, whatsapp = ?";
        $paramTypes = "ss";
        $paramValues = [$telefono, $whatsapp];
        
        // Añadir latitud y longitud si están presentes
        if ($latitud !== null && $longitud !== null) {
            $sqlFields .= ", latitud = ?, longitud = ?";
            $paramTypes .= "dd";
            $paramValues[] = $latitud;
            $paramValues[] = $longitud;
        }
        
        // Procesar el logo si se ha subido uno
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            $fileType = $_FILES['logo']['type'];
            $fileSize = $_FILES['logo']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $error = "Solo se permiten archivos JPG, PNG y GIF";
            } elseif ($fileSize > $maxSize) {
                $error = "El tamaño del archivo no debe superar los 2MB";
            } else {
                $uploadDir = '../../assets/img/logos/';
                
                // Crear el directorio si no existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['logo']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $targetFilePath)) {
                    $sqlFields .= ", logo = ?";
                    $paramTypes .= "s";
                    $paramValues[] = $fileName;
                } else {
                    $error = "Hubo un error al subir el archivo";
                }
            }
        }
        
        // Completar la consulta SQL
        $sqlFields .= " WHERE id = ?";
        $paramTypes .= "i";
        $paramValues[] = $userId;
        
        // Ejecutar la actualización si no hay errores
        if (empty($error)) {
            $stmt = $conn->prepare($sqlFields);
            
            // Bind parameters dynamically
            $stmt->bind_param($paramTypes, ...$paramValues);
            
            if ($stmt->execute()) {
                $success = "Perfil actualizado correctamente";
                
                // Actualizar datos en la variable de empresa
                $empresa['telefono'] = $telefono;
                $empresa['whatsapp'] = $whatsapp;
                
                if ($latitud !== null && $longitud !== null) {
                    $empresa['latitud'] = $latitud;
                    $empresa['longitud'] = $longitud;
                }
                
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0 && empty($error)) {
                    $empresa['logo'] = $fileName;
                }
            } else {
                $error = "Error al actualizar el perfil: " . $conn->error;
            }
        }
    }
}

// Procesar cambio de contraseña (separado del resto del formulario)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_password'])) {
    $nuevaPassword = trim($_POST['nueva_password']);
    $confirmarPassword = trim($_POST['confirmar_password']);
    
    // Validación
    if (empty($nuevaPassword)) {
        $error = "La nueva contraseña es requerida";
    } elseif ($nuevaPassword !== $confirmarPassword) {
        $error = "Las contraseñas no coinciden";
    } else {
        // Encriptar contraseña
        $hashedPassword = password_hash($nuevaPassword, PASSWORD_DEFAULT);
        
        // Actualizar en la base de datos
        $sqlPassword = "UPDATE empresas SET password = ? WHERE id = ?";
        $stmtPassword = $conn->prepare($sqlPassword);
        $stmtPassword->bind_param("si", $hashedPassword, $userId);
        
        if ($stmtPassword->execute()) {
            $success = "Contraseña actualizada correctamente";
        } else {
            $error = "Error al actualizar la contraseña: " . $conn->error;
        }
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
        .profile-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-control:disabled {
            background-color: #e9ecef;
            opacity: 1;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
            width: 240px;
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
        .map-container {
            height: 300px;
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 30px;
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

    <!-- Contenido principal -->
    <main class="main-content">
        <div class="container-fluid">
            

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Información general -->
            <div class="card">
                <div class="card-body">
                    <div class="row profile-header align-items-center">
                        <div class="col-md-3 text-center">
                            <?php
                            $logoSrc = "../../assets/img/default-company.png"; // Imagen por defecto
                            if (!empty($empresa['logo'])) {
                                $logoPath = "../../assets/img/logos/" . $empresa['logo'];
                                if (file_exists($logoPath)) {
                                    $logoSrc = $logoPath;
                                }
                            }
                            ?>
                            <img src="<?php echo $logoSrc; ?>" alt="Logo de la empresa" class="profile-img mb-3">
                        </div>
                        <div class="col-md-9">
                            <h3><?php echo htmlspecialchars($empresa['razon_social'] ?? 'Empresa'); ?></h3>
                            <p class="text-muted">RUC: <?php echo htmlspecialchars($empresa['ruc'] ?? ''); ?></p>
                            <p><i class="fas fa-map-marker-alt me-2"></i> <?php echo htmlspecialchars($empresa['direccion'] ?? ''); ?></p>
                            <p><i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($empresa['email'] ?? ''); ?></p>
                            <?php if (!empty($empresa['telefono'])): ?>
                                <p><i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($empresa['telefono']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($empresa['whatsapp'])): ?>
                                <p><i class="fab fa-whatsapp me-2"></i> <?php echo htmlspecialchars($empresa['whatsapp']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 1: Información básica -->
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Básica</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <!-- Campos no editables -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">RUC</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($empresa['ruc'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="form-label">Razón Social</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($empresa['razon_social'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($empresa['email'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($empresa['direccion'] ?? ''); ?>" disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Campos editables -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="telefono" class="form-control" value="<?php echo htmlspecialchars($empresa['telefono'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">WhatsApp</label>
                                    <input type="tel" name="whatsapp" class="form-control" value="<?php echo htmlspecialchars($empresa['whatsapp'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Subir nuevo logo</label>
                                    <input type="file" name="logo" class="form-control">
                                    <small class="text-muted">Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB</small>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Información
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sección 2: Ubicación -->
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Ubicación</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row mb-3">
                            <div class="col-12">
                                <div id="map" class="map-container"></div>
                                <button type="button" id="btn-ubicacion" class="btn btn-primary mb-3">
                                    <i class="fas fa-map-marker-alt me-2"></i>Obtener mi ubicación
                                </button>
                                <div id="location-status" class="alert alert-info d-none">
                                    <i class="fas fa-info-circle me-2"></i> Solicitando acceso a tu ubicación...
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Latitud</label>
                                    <input type="text" id="latitud" name="latitud" class="form-control" value="<?php echo htmlspecialchars($empresa['latitud'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Longitud</label>
                                    <input type="text" id="longitud" name="longitud" class="form-control" value="<?php echo htmlspecialchars($empresa['longitud'] ?? ''); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Guardar Ubicación
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sección 3: Cambiar Contraseña -->
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Cambiar Contraseña</h4>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Nueva Contraseña</label>
                                    <input type="password" name="nueva_password" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Confirmar Contraseña</label>
                                    <input type="password" name="confirmar_password" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="submit" name="cambiar_password" class="btn btn-warning">
                                    <i class="fas fa-key me-1"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap" async defer></script>
    
    <script>
        let map;
        let marker;
        
        function initMap() {
            // Coordenadas predeterminadas (Perú)
            let defaultLat = -12.046374;
            let defaultLng = -77.042793;
            
            // Si hay coordenadas guardadas, usarlas
            const latInput = document.getElementById('latitud');
            const lngInput = document.getElementById('longitud');
            
            if (latInput.value && lngInput.value) {
                defaultLat = parseFloat(latInput.value);
                defaultLng = parseFloat(lngInput.value);
            }
            
            const mapOptions = {
                center: { lat: defaultLat, lng: defaultLng },
                zoom: 15
            };
            
            map = new google.maps.Map(document.getElementById('map'), mapOptions);
            
            // Si hay coordenadas guardadas, colocar marcador
            if (latInput.value && lngInput.value) {
                placeMarker({ lat: defaultLat, lng: defaultLng });
            }
            
            // Agregar evento de clic en el mapa
            map.addListener('click', function(event) {
                placeMarker(event.latLng);
                updateCoordinates(event.latLng);
            });
            
            // Botón para obtener ubicación actual
            document.getElementById('btn-ubicacion').addEventListener('click', function() {
                // Mostrar estado de solicitud de ubicación
                const locationStatus = document.getElementById('location-status');
                locationStatus.classList.remove('d-none');
                locationStatus.innerHTML = '<i class="fas fa-info-circle me-2"></i> Solicitando acceso a tu ubicación...';
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        function(position) {
                            // Éxito al obtener la ubicación
                            const pos = {
                                lat: position.coords.latitude,
                                lng: position.coords.longitude
                            };
                            
                            map.setCenter(pos);
                            placeMarker(pos);
                            updateCoordinates(pos);
                            
                            // Actualizar estado
                            locationStatus.className = 'alert alert-success';
                            locationStatus.innerHTML = '<i class="fas fa-check-circle me-2"></i> ¡Ubicación obtenida correctamente!';
                            
                            // Ocultar mensaje después de 3 segundos
                            setTimeout(function() {
                                locationStatus.classList.add('d-none');
                            }, 3000);
                        },
                        function(error) {
                            // Error al obtener la ubicación
                            locationStatus.className = 'alert alert-danger';
                            
                            switch(error.code) {
                                case error.PERMISSION_DENIED:
                                    locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Debes activar los permisos de ubicación en tu navegador.';
                                    break;
                                case error.POSITION_UNAVAILABLE:
                                    locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> La información de ubicación no está disponible.';
                                    break;
                                case error.TIMEOUT:
                                    locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Se agotó el tiempo para obtener la ubicación.';
                                    break;
                                default:
                                    locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Error desconocido al obtener la ubicación.';
                            }
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        }
                    );
                } else {
                    locationStatus.className = 'alert alert-danger';
                    locationStatus.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Tu navegador no soporta geolocalización.';
                }
            });
        }
        
        function placeMarker(location) {
            if (marker) {
                marker.setPosition(location);
            } else {
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    draggable: true
                });
                
                // Actualizar coordenadas cuando se arrastra el marcador
                google.maps.event.addListener(marker, 'dragend', function() {
                    updateCoordinates(marker.getPosition());
                });
            }
        }
        
        function updateCoordinates(position) {
            document.getElementById('latitud').value = position.lat();
            document.getElementById('longitud').value = position.lng();
        }
    </script>
</body>
</html>