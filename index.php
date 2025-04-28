<?php
session_start();
// Redirigir si ya está autenticado
if (isset($_SESSION['user_id'])) {
    // Verificar el tipo de usuario y redirigir al dashboard correspondiente
    if ($_SESSION['user_type'] == 'cliente') {
        header("Location: views/cliente/dashboard_cliente.php");
        exit;
    } elseif ($_SESSION['user_type'] == 'empresa') {
        header("Location: views/empresa/dashboard_empresa.php");
        exit;
    } else {
        // En caso de un tipo desconocido o mal configurado
        // Destruir la sesión y recargar la página
        session_destroy();
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GOTAGAS - Sistema de Gestión</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
   

/* Estilos para los botones de categoría */
.category-btn {
        transition: all 0.3s ease;
        min-width: 80px;
        font-weight: 500;
        font-size: 0.85rem;
        border-radius: 20px;
        padding: 0.4rem 0.75rem;
    }
    
    .category-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    
    .category-btn.active {
        box-shadow: 0 0 0 2px #fff, 0 0 0 3px currentColor;
    }
    
    /* Estilos para las tarjetas de producto */
    .product-card {
        transition: transform 0.3s ease;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.1) !important;
    }
    
    .card-img-container {
        position: relative;
        overflow: hidden;
    }
    
    .card-img-top {
        height: 120px;
        object-fit: cover;
        object-position: center;
        transition: transform 0.3s ease;
    }
    
    .product-card:hover .card-img-top {
        transform: scale(1.05);
    }
    
    .product-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background-color: rgba(25, 135, 84, 0.85);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
    }
    
    .card-title {
        font-size: 1rem;
        line-height: 1.2;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .card-company {
        font-size: 0.75rem;
    }
    
    .card-price {
        font-size: 1.1rem;
    }
    
    .card-address {
        font-size: 0.7rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .action-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .action-btn:hover {
        transform: scale(1.1);
    }
    
    /* Estilos responsivos */
    @media (max-width: 768px) {
        .category-btn {
            min-width: 70px;
            margin-bottom: 6px;
            font-size: 0.8rem;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
        }
    }
    
    /* Fuentes más bonitas */
    body {
        font-family: 'Nunito', 'Segoe UI', sans-serif;
    }
    
    h1, h2, h3, h4, h5, .fw-bold {
        font-family: 'Montserrat', 'Segoe UI', sans-serif;
    }
</style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-gas-pump me-2"></i>GOTAGAS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
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
            </div>
        </div>
    </nav>

    



<!-- Estilos adicionales -->

<!-- Categorías de Productos - Fila de Botones Horizontal Mejorada -->
<div class="category-buttons-section py-3 bg-white">
    <div class="container">
        <h3 class="text-center mb-3 fw-bold text-primary">Explora Nuestros Productos</h3>
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <!-- Botón Gas -->
                    <button class="btn btn-primary category-btn" onclick="filterCategory('gas')">
                        <i class="bi bi-fire"></i>
                        <span class="ms-1">Gas</span>
                    </button>
                    
                    <!-- Botón Agua -->
                    <button class="btn btn-info category-btn text-white" onclick="filterCategory('agua')">
                        <i class="bi bi-droplet-fill"></i>
                        <span class="ms-1">Agua</span>
                    </button>
                    
                    <!-- Botón Tanques -->
                    <button class="btn btn-success category-btn" onclick="filterCategory('tanques')">
                        <i class="bi bi-fuel-pump"></i>
                        <span class="ms-1">Tanques</span>
                    </button>
                    
                    <!-- Botón Accesorios -->
                    <button class="btn btn-warning category-btn" onclick="filterCategory('accesorios')">
                        <i class="bi bi-tools"></i>
                        <span class="ms-1">Accesorios</span>
                    </button>
                    
                    <!-- Botón Servicios -->
                    <button class="btn btn-danger category-btn" onclick="filterCategory('servicios')">
                        <i class="bi bi-gear-fill"></i>
                        <span class="ms-1">Servicios</span>
                    </button>
                    
                    <!-- Botón Promociones -->
                    <button class="btn btn-secondary category-btn" onclick="filterCategory('promociones')">
                        <i class="bi bi-tag-fill"></i>
                        <span class="ms-1">Promos</span>
                    </button>
                    
                    <!-- Botón Todos -->
                    <button class="btn btn-outline-dark category-btn" onclick="filterCategory('todos')">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        <span class="ms-1">Todos</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Ejemplo de tarjeta de producto mejorada -->
<div class="container mt-4">
    <div class="row">
        <!-- Ejemplo de tarjeta de producto -->
        <div class="col-6 col-md-4 col-lg-3 mb-3">
            <div class="product-card card h-100 shadow-sm">
                <div class="card-img-container">
                    <img src="assets/img/agua.jpeg" class="card-img-top" alt="Tanque Estacionario 120kg">
                    <span class="product-badge">Disponible</span>
                </div>
                <div class="card-body p-2">
                    <h5 class="card-title fw-bold text-primary mb-1">Tanque 120kg</h5>
                    <p class="card-company mb-0 text-muted small">GOTAGAS</p>
                    <p class="card-price mb-1 fw-bold text-success">$3,500.00</p>
                    <p class="card-address small mb-2"><i class="bi bi-geo-alt-fill text-secondary me-1"></i>Av. Principal #123</p>
                    <div class="d-flex justify-content-end mt-2 gap-2">
                        <button class="btn btn-sm btn-outline-primary action-btn" onclick="callPhone('5551234567')" title="Llamar">
                            <i class="bi bi-telephone-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-success action-btn" onclick="sendWhatsApp('5551234567', 'Hola, me interesa el producto')" title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Ejemplo de tarjeta de producto -->
        <div class="col-6 col-md-4 col-lg-3 mb-3">
            <div class="product-card card h-100 shadow-sm">
                <div class="card-img-container">
                    <img src="assets/img/agua.jpeg" class="card-img-top" alt="Tanque Estacionario 120kg">
                    <span class="product-badge">Disponible</span>
                </div>
                <div class="card-body p-2">
                    <h5 class="card-title fw-bold text-primary mb-1">Tanque 120kg</h5>
                    <p class="card-company mb-0 text-muted small">GOTAGAS</p>
                    <p class="card-price mb-1 fw-bold text-success">$3,500.00</p>
                    <p class="card-address small mb-2"><i class="bi bi-geo-alt-fill text-secondary me-1"></i>Av. Principal #123</p>
                    <div class="d-flex justify-content-end mt-2 gap-2">
                        <button class="btn btn-sm btn-outline-primary action-btn" onclick="callPhone('5551234567')" title="Llamar">
                            <i class="bi bi-telephone-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-success action-btn" onclick="sendWhatsApp('5551234567', 'Hola, me interesa el producto')" title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Ejemplo de tarjeta de producto -->
        <div class="col-6 col-md-4 col-lg-3 mb-3">
            <div class="product-card card h-100 shadow-sm">
                <div class="card-img-container">
                    <img src="assets/img/agua.jpeg" class="card-img-top" alt="Tanque Estacionario 120kg">
                    <span class="product-badge">Disponible</span>
                </div>
                <div class="card-body p-2">
                    <h5 class="card-title fw-bold text-primary mb-1">Tanque 120kg</h5>
                    <p class="card-company mb-0 text-muted small">GOTAGAS</p>
                    <p class="card-price mb-1 fw-bold text-success">$3,500.00</p>
                    <p class="card-address small mb-2"><i class="bi bi-geo-alt-fill text-secondary me-1"></i>Av. Principal #123</p>
                    <div class="d-flex justify-content-end mt-2 gap-2">
                        <button class="btn btn-sm btn-outline-primary action-btn" onclick="callPhone('5551234567')" title="Llamar">
                            <i class="bi bi-telephone-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-success action-btn" onclick="sendWhatsApp('5551234567', 'Hola, me interesa el producto')" title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Ejemplo de tarjeta de producto -->
        <div class="col-6 col-md-4 col-lg-3 mb-3">
            <div class="product-card card h-100 shadow-sm">
                <div class="card-img-container">
                    <img src="assets/img/agua.jpeg" class="card-img-top" alt="Tanque Estacionario 120kg">
                    <span class="product-badge">Disponible</span>
                </div>
                <div class="card-body p-2">
                    <h5 class="card-title fw-bold text-primary mb-1">Tanque 120kg</h5>
                    <p class="card-company mb-0 text-muted small">GOTAGAS</p>
                    <p class="card-price mb-1 fw-bold text-success">$3,500.00</p>
                    <p class="card-address small mb-2"><i class="bi bi-geo-alt-fill text-secondary me-1"></i>Av. Principal #123</p>
                    <div class="d-flex justify-content-end mt-2 gap-2">
                        <button class="btn btn-sm btn-outline-primary action-btn" onclick="callPhone('5551234567')" title="Llamar">
                            <i class="bi bi-telephone-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-success action-btn" onclick="sendWhatsApp('5551234567', 'Hola, me interesa el producto')" title="WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Script para manejar el filtrado de categorías -->
<script>
    function filterCategory(category) {
        // Marcar el botón activo
        const buttons = document.querySelectorAll('.category-btn');
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.textContent.trim().toLowerCase().includes(category) || 
               (category === 'todos' && btn.textContent.trim().toLowerCase().includes('todos'))) {
                btn.classList.add('active');
            }
        });
        
        // Aquí puedes implementar la lógica para filtrar los productos según la categoría
        console.log(`Filtrando por categoría: ${category}`);
        
        // Implementación de filtrado
        const productos = document.querySelectorAll('.product-card');
        
        productos.forEach(producto => {
            if (category === 'todos' || producto.dataset.category === category) {
                producto.parentElement.style.display = 'block';
            } else {
                producto.parentElement.style.display = 'none';
            }
        });
    }
    
    // Inicializar con "todos" seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        filterCategory('todos');
    });
    
    function callPhone(phoneNumber) {
        window.location.href = `tel:${phoneNumber}`;
    }
    
    function sendWhatsApp(phoneNumber, message) {
        // Codificar el mensaje para URL
        const encodedMessage = encodeURIComponent(message);
        window.location.href = `https://wa.me/${phoneNumber}?text=${encodedMessage}`;
    }
</script>

    <!-- Features Section -->
    <section class="bg-light py-5">
        <div class="container">
            <h2 class="text-center mb-5">Características Principales</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <h5 class="card-title">Panel de Control</h5>
                            <p class="card-text">Monitorea toda tu operación desde un intuitivo dashboard con métricas en tiempo real.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                                <i class="fas fa-truck"></i>
                            </div>
                            <h5 class="card-title">Gestión de Pedidos</h5>
                            <p class="card-text">Administra eficientemente los pedidos y optimiza las rutas de entrega.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary bg-gradient text-white rounded-circle mb-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="card-title">Reportes Detallados</h5>
                            <p class="card-text">Analiza el rendimiento con reportes y estadísticas personalizables.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-gas-pump me-2"></i>GOTAGAS</h5>
                    <p>Sistema integral para la gestión y distribución de gas y agua.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>© 2025 GOTAGAS. Todos los derechos reservados.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Modals -->
    <?php include 'includes/modals.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>