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

// Filtrar por estado si se especifica
$filtroEstado = isset($_GET['estado']) ? $_GET['estado'] : '';

// Preparar consulta base
$sql = "SELECT p.*, c.nombre as cliente_nombre, c.telefono as cliente_telefono, c.direccion as cliente_direccion 
        FROM pedidos p 
        JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.empresa_id = ? ";

// Agregar filtro por estado si se ha especificado
if (!empty($filtroEstado)) {
    $sql .= " AND p.estado = ? ";
}

// Ordenar por fecha de pedido descendente
$sql .= " ORDER BY p.fecha_pedido DESC";

// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);

if (!empty($filtroEstado)) {
    $stmt->bind_param("is", $userId, $filtroEstado);
} else {
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
    <title>Gestión de Pedidos - GOTAGAS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
            <h1 class="h2"><i class="fas fa-clipboard-list me-2"></i> Gestión de Pedidos</h1>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php if ($_GET['success'] == 1): ?>
                    Estado del pedido actualizado con éxito.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php if ($_GET['error'] == 1): ?>
                    Error al actualizar el estado del pedido.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <form action="pedidos.php" method="get" class="d-flex">
                            <select name="estado" class="form-select me-2">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $filtroEstado === 'pendiente' ? 'selected' : ''; ?>>Pendientes</option>
                                <option value="en_proceso" <?php echo $filtroEstado === 'en_proceso' ? 'selected' : ''; ?>>En proceso</option>
                                <option value="en_camino" <?php echo $filtroEstado === 'en_camino' ? 'selected' : ''; ?>>En camino</option>
                                <option value="entregado" <?php echo $filtroEstado === 'entregado' ? 'selected' : ''; ?>>Entregados</option>
                                <option value="cancelado" <?php echo $filtroEstado === 'cancelado' ? 'selected' : ''; ?>>Cancelados</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Filtrar</button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="pedidos.php" class="btn btn-outline-secondary">Limpiar filtros</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Pedidos -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Lista de Pedidos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="pedidosTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Teléfono</th>
                                <th>Dirección</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pedidos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay pedidos registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pedidos as $pedido): ?>
                                    <tr>
                                        <td><?php echo $pedido['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                        <td><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($pedido['cliente_telefono']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($pedido['cliente_direccion'], 0, 30)) . (strlen($pedido['cliente_direccion']) > 30 ? '...' : ''); ?></td>
                                        <td>S/ <?php echo number_format($pedido['total'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo getEstadoClass($pedido['estado']); ?>">
                                                <?php echo estadoEnEspanol($pedido['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-success btn-actualizar-estado" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#actualizarEstadoModal"
                                                        data-pedido-id="<?php echo $pedido['id']; ?>"
                                                        data-estado-actual="<?php echo $pedido['estado']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Actualizar Estado -->
    <div class="modal fade" id="actualizarEstadoModal" tabindex="-1" aria-labelledby="actualizarEstadoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actualizarEstadoModalLabel">Actualizar Estado del Pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actualizar_estado_pedido.php" method="post">
                    <input type="hidden" id="pedido_id" name="pedido_id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nuevo_estado" class="form-label">Nuevo Estado</label>
                            <select class="form-select" id="nuevo_estado" name="nuevo_estado" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="en_proceso">En proceso</option>
                                <option value="en_camino">En camino</option>
                                <option value="entregado">Entregado</option>
                                <option value="cancelado">Cancelado</option>
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
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#pedidosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                responsive: true,
                order: [[0, 'desc']]
            });
            
            // Configurar modal de actualización de estado
            $('.btn-actualizar-estado').click(function() {
                var pedidoId = $(this).data('pedido-id');
                var estadoActual = $(this).data('estado-actual');
                
                $('#pedido_id').val(pedidoId);
                $('#nuevo_estado').val(estadoActual);
            });
        });
    </script>
</body>
</html>