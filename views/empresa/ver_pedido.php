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
// Verificar que se haya proporcionado un ID de pedido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: pedidos.php');
    exit();
}
$pedidoId = intval($_GET['id']);
// Obtener información del pedido
$stmt = $conn->prepare("
    SELECT p., c.nombre as cliente_nombre, c.email as cliente_email, 
           c.telefono as cliente_telefono, c.direccion as cliente_direccion, 
           c.referencia as cliente_referencia
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.id = ? AND p.empresa_id = ?
");
$stmt->bind_param("ii", $pedidoId, $userId);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows === 0) {
    // El pedido no existe o no pertenece a esta empresa
    header('Location: pedidos.php?error=3');
    exit();
}
$pedido = $resultado->fetch_assoc();
$stmt->close();
// Obtener detalles de productos del pedido
$stmt = $conn->prepare("
    SELECT dp., p.nombre as producto_nombre, p.imagen as producto_imagen
    FROM detalles_pedido dp
    JOIN productos p ON dp.producto_id = p.id
    WHERE dp.pedido_id = ?
");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$detallesPedido = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Obtener historial de estados del pedido
$stmt = $conn->prepare("
    SELECT hp.*, u.nombre as usuario_nombre
    FROM historial_pedidos hp
    LEFT JOIN usuarios u ON hp.usuario_id = u.id
    WHERE hp.pedido_id = ?
    ORDER BY hp.fecha_cambio DESC
");
$stmt->bind_param("i", $pedidoId);
$stmt->execute();
$historialEstados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Función para mostrar el estado en español
function estadoEnEspanol($estado) {
    $estados = [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'en_camino' => 'En camino',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado'
    ];
    return $estados[$estado] ?? $estado;
}
// Función para obtener la clase de color según el estado
function getEstadoClass($estado) {
    $clases = [
        'pendiente' => 'bg-warning',
        'en_proceso' => 'bg-info',
        'en_camino' => 'bg-primary',
        'entregado' => 'bg-success',
        'cancelado' => 'bg-danger'
    ];
    return $clases[$estado] ?? 'bg-secondary';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Pedido #<?php echo $pedidoId; ?> - GOTAGAS</title>
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
        
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -20px;
            top: 0;
            height: 100%;
            width: 2px;
            background-color: #e9ecef;
        }
        
        .timeline-item:last-child:before {
            height: 50%;
        }
        
        .timeline-badge {
            position: absolute;
            left: -29px;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            z-index: 1;
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
                    <a class="nav-link active" href="pedidos.php">
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
                    <a class="nav-link" href="perfil.php">
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
        <!-- Page Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2"><i class="fas fa-clipboard-check me-2"></i> Detalles del Pedido #<?php echo $pedidoId; ?></h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="pedidos.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver a pedidos
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Información del pedido -->
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Información del Pedido</h5>
                        <span class="badge <?php echo getEstadoClass($pedido['estado']); ?> fs-6">
                            <?php echo estadoEnEspanol($pedido['estado']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Número de Pedido:</strong> #<?php echo $pedido['id']; ?></p>
                                <p><strong>Fecha del Pedido:</strong> <?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></p>
                                <p><strong>Total:</strong> S/ <?php echo number_format($pedido['total'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Método de Pago:</strong> <?php echo ucfirst($pedido['metodo_pago'] ?? 'No especificado'); ?></p>
                                <p><strong>Fecha de Entrega:</strong> 
                                    <?php echo $pedido['fecha_entrega'] ? date('d/m/Y', strtotime($pedido['fecha_entrega'])) : 'No programada'; ?>
                                </p>
                                <p><strong>Hora de Entrega:</strong> 
                                    <?php echo $pedido['hora_entrega'] ? date('H:i', strtotime($pedido['hora_entrega'])) : 'No programada'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if (!empty($pedido['comentarios'])): ?>
                        <div class="mb-3">
                            <h6>Comentarios del Cliente:</h6>
                            <div class="alert alert-light">
                                <?php echo nl2br(htmlspecialchars($pedido['comentarios'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#actualizarEstadoModal">
                                <i class="fas fa-edit me-1"></i> Actualizar Estado
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Productos del Pedido -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Productos del Pedido</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio Unit.</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detallesPedido as $detalle): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($detalle['producto_imagen'])): ?>
                                                        <img src="../../assets/img/productos/<?php echo $detalle['producto_imagen']; ?>" 
                                                             alt="<?php echo htmlspecialchars($detalle['producto_nombre']); ?>" 
                                                             class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center me-2" 
                                                             style="width: 50px; height: 50px;">
                                                            <i class="fas fa-box text-secondary"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($detalle['producto_nombre']); ?></span>
                                                </div>
                                            </td>
                                            <td><?php echo $detalle['cantidad']; ?></td>
                                            <td>S/ <?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                            <td>S/ <?php echo number_format($detalle['precio_unitario'] * $detalle['cantidad'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Subtotal:</th>
                                        <td>S/ <?php echo number_format($pedido['subtotal'] ?? ($pedido['total'] - ($pedido['costo_envio'] ?? 0)), 2); ?></td>
                                    </tr>
                                    <?php if (isset($pedido['costo_envio']) && $pedido['costo_envio'] > 0): ?>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Costo de Envío:</th>
                                        <td>S/ <?php echo number_format($pedido['costo_envio'], 2); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Total:</th>
                                        <td><strong>S/ <?php echo number_format($pedido['total'], 2); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Historial de Estados -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Historial de Estados</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php if (empty($historialEstados)): ?>
                                <p>No hay historial de cambios de estado disponible.</p>
                            <?php else: ?>
                                <?php foreach ($historialEstados as $index => $estado): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-badge <?php echo getEstadoClass($estado['estado']); ?>"></div>
                                        <div class="card mb-2">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-1"><?php echo estadoEnEspanol($estado['estado']); ?></h6>
                                                    <small><?php echo date('d/m/Y H:i', strtotime($estado['fecha_cambio'])); ?></small>
                                                </div>
                                                <?php if (!empty($estado['comentario'])): ?>
                                                    <p class="mb-1 small"><?php echo nl2br(htmlspecialchars($estado['comentario'])); ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted">Actualizado por: <?php echo htmlspecialchars($estado['usuario_nombre'] ?? 'Sistema'); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Información del Cliente -->
            <div class="col-md-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Información del Cliente</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($pedido['cliente_nombre']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($pedido['cliente_email']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($pedido['cliente_telefono']); ?></p>
                        <hr>
                        <h6>Dirección de Entrega:</h6>
                        <address>
                            <?php echo nl2br(htmlspecialchars($pedido['cliente_direccion'])); ?>
                        </address>
                        <?php if (!empty($pedido['cliente_referencia'])): ?>
                            <p><strong>Referencia:</strong> <?php echo htmlspecialchars($pedido['cliente_referencia']); ?></p>
                        <?php endif; ?>
                        <div class="d-grid gap-2 mt-3">
                            <a href="tel:<?php echo $pedido['cliente_telefono']; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-phone me-1"></i> Llamar al Cliente
                            </a>
                            <a href="https://wa.me/51<?php echo preg_replace('/[^0-9]/', '', $pedido['cliente_telefono']); ?>" 
                               target="_blank" class="btn btn-outline-success">
                                <i class="fab fa-whatsapp me-1"></i> Contactar por WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Mapa de Ubicación -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Ubicación de Entrega</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($pedido['latitud']) && !empty($pedido['longitud'])): ?>
                            <div id="mapa" style="height: 250px;" class="mb-3"></div>
                            <div class="d-grid">
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $pedido['latitud']; ?>,<?php echo $pedido['longitud']; ?>" 
                                   class="btn btn-outline-secondary" target="_blank">
                                    <i class="fas fa-directions me-1"></i> Cómo llegar
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No se ha registrado la ubicación GPS para este pedido.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Actualizar Estado -->
    <div class="modal fade" id="actualizarEstadoModal" tabindex="-1" aria-labelledby="actualizarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actualizarEstadoModalLabel">Actualizar Estado del Pedido #<?php echo $pedidoId; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actualizar_estado_pedido.php" method="post">
                    <input type="hidden" name="pedido_id" value="<?php echo $pedidoId; ?>">
                    <input type="hidden" name="redirect" value="ver_pedido.php?id=<?php echo $pedidoId; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="pendiente" <?php echo $pedido['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="en_proceso" <?php echo $pedido['estado'] === 'en_proceso' ? 'selected' : ''; ?>>En proceso</option>
                                <option value="en_camino" <?php echo $pedido['estado'] === 'en_camino' ? 'selected' : ''; ?>>En camino</option>
                                <option value="entregado" <?php echo $pedido['estado'] === 'entregado' ? 'selected' : ''; ?>>Entregado</option>
                                <option value="cancelado" <?php echo $pedido['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario (opcional)</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <?php if (!empty($pedido['latitud']) && !empty($pedido['longitud'])): ?>
    <!-- Script de Google Maps -->
    <script>
        function initMap() {
            const latitud = <?php echo $pedido['latitud']; ?>;
            const longitud = <?php echo $pedido['longitud']; ?>;
            const ubicacion = { lat: latitud, lng: longitud };
            
            const map = new google.maps.Map(document.getElementById("mapa"), {
                zoom: 15,
                center: ubicacion,
            });
            
            const marker = new google.maps.Marker({
                position: ubicacion,
                map: map,
                title: "Ubicación de entrega"
            });
        }
    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap">
    </script>
    <?php endif; ?>
</body>
</html>