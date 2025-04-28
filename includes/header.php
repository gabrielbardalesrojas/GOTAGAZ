<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'GOTAGAS - Sistema de Gestión'; ?></title>
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
            <a class="navbar-brand" href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php'; ?>">
                <i class="fas fa-gas-pump me-2"></i>GOTAGAS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Usuario autenticado -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                        </a>
                    </li>
                    <?php if ($_SESSION['user_type'] == 'empresa'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'distribucion' ? 'active' : ''; ?>" href="#">
                            <i class="fas fa-truck me-1"></i> Distribución
                        </a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'pedidos' ? 'active' : ''; ?>" href="#">
                            <i class="fas fa-shopping-cart me-1"></i> Mis Pedidos
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $currentPage == 'perfil' ? 'active' : ''; ?>" href="#">
                            <i class="fas fa-user-cog me-1"></i> Perfil
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <span class="text-light me-3">
                        <i class="fas fa-user-circle me-1"></i> 
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i> Cerrar Sesión
                    </a>
                </div>
                <?php else: ?>
                <!-- Usuario no autenticado -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#registerTypeModal">
                            <i class="fas fa-user-plus me-1"></i> Registrarse
                        </button>
                    </li>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>