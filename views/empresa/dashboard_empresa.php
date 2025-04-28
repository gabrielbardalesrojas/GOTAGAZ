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

// Obtener información detallada de la empresa
$stmt = $conn->prepare("SELECT * FROM empresas WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$empresaInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Contar productos
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM productos WHERE empresa_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$totalProductos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar pedidos pendientes
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM pedidos WHERE empresa_id = ? AND estado = 'pendiente'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pedidosPendientes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar pedidos entregados
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM pedidos WHERE empresa_id = ? AND estado = 'entregado'");
$stmt->bind_param("i", $userId);
$stmt->execute();
$pedidosEntregados = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Contar clientes únicos que han hecho pedidos
$stmt = $conn->prepare("SELECT COUNT(DISTINCT cliente_id) as total FROM pedidos WHERE empresa_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$clientesActivos = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Obtener últimos pedidos
$stmt = $conn->prepare("
    SELECT p.id, p.fecha_pedido, p.estado, p.total, c.nombre as cliente_nombre
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    WHERE p.empresa_id = ?
    ORDER BY p.fecha_pedido DESC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$ultimosPedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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

// Establecer la zona horaria a la de Perú (America/Lima)
date_default_timezone_set('America/Lima');

// Mostrar la fecha actual en el formato "día/mes/año"


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Empresa - GOTAGAS</title>
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
        
        .navbar {
            padding: 0.5rem 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.5rem 0.75rem;
        }
        
        .stats-icon {
            font-size: 2rem;
            height: 60px;
            width: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        
        .stat-card {
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
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
            
            <div class="d-flex align-items-center flex-column justify-content-center  mb-3">
            <img src="../../assets/img/gotita.jpg" alt="Imagen de GOTAGAS" class="rounded-circle mt-2" style="width: 80px; height: 80px;">

            <a class="navbar-brand text-light" href="dashboard_empresa.php">
        <i class="fas fa-gas-pump me-2"></i>GOTAGAS
    </a>
 </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard_empresa.php">
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
        <!-- Navbar -->
        

        <!-- Welcome Card -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-building fa-2x"></i>
                    </div>
                    
                    <div>
                        <h2 class="mb-1">¡Bienvenido, <?php echo htmlspecialchars($empresaInfo['razon_social'] ?? $userName); ?>!</h2>
                        <p class="text-muted mb-0">Panel de Empresa | <?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2 stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Pedidos Pendientes</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pedidosPendientes; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-warning text-white">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2 stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Entregas Realizadas</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pedidosEntregados; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-success text-white">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2 stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Clientes Activos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $clientesActivos; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-info text-white">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2 stat-card">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Productos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProductos; ?></div>
                            </div>
                            <div class="col-auto">
                                <div class="stats-icon bg-primary text-white">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Summary -->
        <div class="row">
           
            <div class="col-md-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Estadistica de ventas</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Orders -->
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Últimos Pedidos</h5>
                        <a href="pedidos.php" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="pedidosTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Cliente</th>
                                        <th>Total</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ultimosPedidos)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No hay pedidos registrados</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ultimosPedidos as $pedido): ?>
                                            <tr>
                                                <td><?php echo $pedido['id']; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($pedido['fecha_pedido'])); ?></td>
                                                <td><?php echo htmlspecialchars($pedido['cliente_nombre']); ?></td>
                                                <td>S/ <?php echo number_format($pedido['total'], 2); ?></td>
                                                <td><span class="badge <?php echo getEstadoClass($pedido['estado']); ?>"><?php echo estadoEnEspanol($pedido['estado']); ?></span></td>
                                                <td>
                                                    <a href="ver_pedido.php?id=<?php echo $pedido['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-success btn-actualizar-estado" data-pedido-id="<?php echo $pedido['id']; ?>" data-bs-toggle="modal" data-bs-target="#actualizarEstadoModal">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
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
        </div>
    </div>

   

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
</body>
</html>