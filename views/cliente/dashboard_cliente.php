<?php
session_start();
require_once '../../controllers/session_controller.php';

// Verificar si hay una sesión activa
checkSession();

// Obtener información del usuario y verificar que sea un cliente
$userName = $_SESSION['user_name'];
$userType = $_SESSION['user_type'];

// Redireccionar si no es un cliente
if ($userType !== 'cliente') {
    header('Location: dashboard_empresa.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - GOTAGAS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard_cliente.php">
                <i class="fas fa-gas-pump me-2"></i>GOTAGAS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard_cliente.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="perfil.php">
                            <i class="fas fa-shopping-cart me-1"></i> Perfil
                        </a>
                    </li>
                    
                    
                </ul>
                <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <a class="btn btn-light dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i> 
                                <?php echo htmlspecialchars($empresaInfo['razon_social'] ?? $userName); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuLink">
                                <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-cog me-1"></i> Mi Perfil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="../../logout.php"><i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <div>
                                <h2 class="mb-1">¡Bienvenido, <?php echo htmlspecialchars($userName); ?>!</h2>
                                <p class="text-muted mb-0">Panel de Cliente</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Resumen</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Pedidos Realizados:</span>
                            <span class="badge bg-primary">3</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Pedidos Pendientes:</span>
                            <span class="badge bg-warning">1</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Pedidos Completados:</span>
                            <span class="badge bg-success">2</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i> Nuevo Pedido
                            </button>
                            <button class="btn btn-outline-primary">
                                <i class="fas fa-history me-2"></i> Ver Historial
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Mis Últimos Pedidos</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Fecha</th>
                                        <th>Producto</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>25/04/2025</td>
                                        <td>Gas Doméstico 10kg</td>
                                        <td><span class="badge bg-warning">En proceso</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>2</td>
                                        <td>20/04/2025</td>
                                        <td>Gas Doméstico 5kg</td>
                                        <td><span class="badge bg-success">Entregado</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
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
    <script src="../../assets/js/scripts.js"></script>
</body>
</html>