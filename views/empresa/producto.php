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

// Manejar la eliminación de producto
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $productoId = $_GET['delete'];
    
    // Verificar que el producto pertenezca a esta empresa
    $stmt = $conn->prepare("SELECT id FROM productos WHERE id = ? AND empresa_id = ?");
    $stmt->bind_param("ii", $productoId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Eliminar el producto
        $deleteStmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $deleteStmt->bind_param("i", $productoId);
        
        if ($deleteStmt->execute()) {
            // Redirigir con mensaje de éxito
            header('Location: producto.php?success=1');
            exit();
        } else {
            // Redirigir con mensaje de error
            header('Location: producto.php?error=1');
            exit();
        }
        $deleteStmt->close();
    } else {
        // Producto no encontrado o no pertenece a esta empresa
        header('Location: producto.php?error=2');
        exit();
    }
    $stmt->close();
}

// Obtener todos los productos de la empresa
$stmt = $conn->prepare("SELECT * FROM productos WHERE empresa_id = ? ORDER BY id DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Productos - GOTAGAS</title>
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
        
        .product-image {
            height: 120px;
            object-fit: contain;
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
                    <a class="nav-link active" href="producto.php">
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
            <h1 class="h2"><i class="fas fa-box me-2"></i> Gestión de Productos</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#agregarProductoModal">
                    <i class="fas fa-plus me-1"></i> Agregar Producto
                </button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php if ($_GET['success'] == 1): ?>
                    Producto eliminado con éxito.
                <?php elseif ($_GET['success'] == 2): ?>
                    Producto agregado con éxito.
                <?php elseif ($_GET['success'] == 3): ?>
                    Producto actualizado con éxito.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php if ($_GET['error'] == 1): ?>
                    Error al eliminar el producto.
                <?php elseif ($_GET['error'] == 2): ?>
                    Producto no encontrado o no pertenece a esta empresa.
                <?php elseif ($_GET['error'] == 3): ?>
                    Error al agregar el producto.
                <?php elseif ($_GET['error'] == 4): ?>
                    Error al actualizar el producto.
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Products List -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Lista de Productos</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productosTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Disponible</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($productos)): ?>
                                <tr>
                                    <td colspan="8" class="text-center">No hay productos registrados</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($productos as $producto): ?>
                                    <tr>
                                        <td><?php echo $producto['id']; ?></td>
                                        <td>
                                            <?php if (!empty($producto['imagen'])): ?>
                                                <img src="../../assets/img/producto/<?php echo $producto['imagen']; ?>" class="product-image img-thumbnail" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                            <?php else: ?>
                                                <img src="../../assets/img/no-image.jpg" class="product-image img-thumbnail" alt="Sin imagen">
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($producto['descripcion'], 0, 50)) . (strlen($producto['descripcion']) > 50 ? '...' : ''); ?></td>
                                        <td>S/ <?php echo number_format($producto['precio'], 2); ?></td>
                                        <td><?php echo $producto['stock']; ?></td>
                                        <td>
                                            <?php if ($producto['disponible']): ?>
                                                <span class="badge bg-success">Disponible</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">No Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info btn-editar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editarProductoModal"
                                                data-id="<?php echo $producto['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>"
                                                data-descripcion="<?php echo htmlspecialchars($producto['descripcion']); ?>"
                                                data-precio="<?php echo $producto['precio']; ?>"
                                                data-stock="<?php echo $producto['stock']; ?>"
                                                data-disponible="<?php echo $producto['disponible']; ?>"
                                                data-imagen="<?php echo $producto['imagen']; ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-eliminar" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#eliminarProductoModal"
                                                data-id="<?php echo $producto['id']; ?>"
                                                data-nombre="<?php echo htmlspecialchars($producto['nombre']); ?>">
                                                <i class="fas fa-trash"></i>
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

    <!-- Modal Agregar Producto -->
    <div class="modal fade" id="agregarProductoModal" tabindex="-1" aria-labelledby="agregarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="agregarProductoModalLabel">Agregar Nuevo Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="agregar_producto.php" method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="precio" class="form-label">Precio (S/) *</label>
                            <input type="number" class="form-control" id="precio" name="precio" step="0.1" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="disponible" name="disponible" value="1" checked>
                            <label class="form-check-label" for="disponible">Disponible para venta</label>
                        </div>
                        <div class="mb-3">
                            <label for="imagen" class="form-label">Imagen del Producto</label>
                            <input type="file" class="form-control" id="imagen" name="imagen" accept="image/*">
                            <small class="form-text text-muted">Tamaño recomendado: 500x500px. Máximo 2MB.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Producto -->
    <div class="modal fade" id="editarProductoModal" tabindex="-1" aria-labelledby="editarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarProductoModalLabel">Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="actualizar_producto.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_precio" class="form-label">Precio (S/) *</label>
                            <input type="number" class="form-control" id="edit_precio" name="precio" step="0.01" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="edit_stock" name="stock" min="0">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="edit_disponible" name="disponible" value="1">
                            <label class="form-check-label" for="edit_disponible">Disponible para venta</label>
                        </div>
                        <div class="mb-3">
                            <label for="edit_imagen" class="form-label">Imagen del Producto</label>
                            <input type="file" class="form-control" id="edit_imagen" name="imagen" accept="image/*">
                            <small class="form-text text-muted">Deje vacío para mantener la imagen actual.</small>
                            <div id="imagen_actual_container" class="mt-2">
                                <label>Imagen Actual:</label>
                                <img id="imagen_actual" src="" class="img-thumbnail" style="max-height: 100px;" alt="Imagen actual">
                            </div>
                            <input type="hidden" name="imagen_actual" id="imagen_actual_input">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Producto -->
    <div class="modal fade" id="eliminarProductoModal" tabindex="-1" aria-labelledby="eliminarProductoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarProductoModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar el producto <strong id="eliminar_nombre"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="btn_eliminar" class="btn btn-danger">Eliminar</a>
                </div>
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
            $('#productosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                responsive: true
            });
            
            // Configurar modal de edición
            $('.btn-editar').click(function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                var descripcion = $(this).data('descripcion');
                var precio = $(this).data('precio');
                var stock = $(this).data('stock');
                var disponible = $(this).data('disponible');
                var imagen = $(this).data('imagen');
                
                $('#edit_id').val(id);
                $('#edit_nombre').val(nombre);
                $('#edit_descripcion').val(descripcion);
                $('#edit_precio').val(precio);
                $('#edit_stock').val(stock);
                $('#edit_disponible').prop('checked', disponible == 1);
                $('#imagen_actual_input').val(imagen);
                
                if (imagen) {
                    $('#imagen_actual').attr('src', '../../assets/img/producto/' + imagen);
                    $('#imagen_actual_container').show();
                } else {
                    $('#imagen_actual').attr('src', '../../assets/img/no-image.jpg');
                    $('#imagen_actual_container').show();
                }
            });
            
            // Configurar modal de eliminación
            $('.btn-eliminar').click(function() {
                var id = $(this).data('id');
                var nombre = $(this).data('nombre');
                
                $('#eliminar_nombre').text(nombre);
                $('#btn_eliminar').attr('href', 'producto.php?delete=' + id);
            });
        });
    </script>
</body>
</html>