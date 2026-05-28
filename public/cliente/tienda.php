<?php
/**
 * TIENDA - LISTADO DE PRODUCTOS
 * =============================
 * Página para que clientes vean y compren productos
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

try {
    // Obtener categorías activas
    $stmt = $conn->prepare("SELECT * FROM categoria_producto ORDER BY nombre");
    $stmt->execute();
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener productos (con filtro opcional por categoría)
    $categoriaFiltro = getGet('categoria', '');
    $busqueda = getGet('busqueda', '');

    $sql = "SELECT p.*, c.nombre as categoria_nombre
            FROM producto p
            LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria
            WHERE p.estado_activo = 1";

    $params = [];

    if (!empty($categoriaFiltro)) {
        $sql .= " AND p.id_categoria = ?";
        $params[] = $categoriaFiltro;
    }

    if (!empty($busqueda)) {
        $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
        $params[] = "%$busqueda%";
        $params[] = "%$busqueda%";
    }

    $sql .= " ORDER BY p.stock_actual DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $categorias = [];
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: white !important;
        }

        .btn-user {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
        }

        .btn-user:hover {
            background: white;
            color: #667eea;
        }

        /* FILTROS */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .search-input {
            border-radius: 25px;
            padding: 10px 20px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .category-btn {
            border-radius: 25px;
            margin: 5px;
            transition: all 0.3s;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
        }

        .category-btn:hover,
        .category-btn.active {
            background: #667eea;
            color: white;
        }

        /* PRODUCTOS */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            height: 100%;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        .product-category {
            font-size: 12px;
            color: #999;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .product-description {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 15px;
        }

        .btn-add-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-add-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .filters {
                padding: 15px;
            }

            .category-btn {
                margin: 3px;
                font-size: 12px;
                padding: 6px 12px;
            }

            .product-info {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/petspa/public/index.php">
                <i class="fas fa-spa"></i> Pet Spa
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/dashboard.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/petspa/public/cliente/tienda.php">
                            <i class="fas fa-shopping-bag"></i> Tienda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/servicios.php">
                            <i class="fas fa-cut"></i> Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/citas.php">
                            <i class="fas fa-calendar-check"></i> Mis Citas
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn-user" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/petspa/public/cliente/perfil.php">
                                <i class="fas fa-user-edit"></i> Mi Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="/petspa/public/cliente/carrito.php">
                                <i class="fas fa-shopping-cart"></i> Mi Carrito
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/petspa/api/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-fluid py-4">
        <div class="container">
            <!-- HEADER -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="display-5 fw-bold text-center mb-3">
                        <i class="fas fa-shopping-bag text-primary"></i> Nuestra Tienda
                    </h1>
                    <p class="text-center text-muted">Descubre productos de calidad para el cuidado de tu mascota</p>
                </div>
            </div>

            <!-- FILTROS -->
            <div class="filters">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="busqueda" class="form-control search-input me-2"
                                   placeholder="Buscar productos..."
                                   value="<?php echo htmlspecialchars($busqueda); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap justify-content-end">
                            <a href="/petspa/public/cliente/tienda.php"
                               class="btn category-btn <?php echo empty($categoriaFiltro) ? 'active' : ''; ?>">
                                Todos
                            </a>
                            <?php foreach ($categorias as $categoria): ?>
                                <a href="/petspa/public/cliente/tienda.php?categoria=<?php echo $categoria['id_categoria']; ?>"
                                   class="btn category-btn <?php echo $categoriaFiltro == $categoria['id_categoria'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PRODUCTOS -->
            <?php if (!empty($productos)): ?>
                <div class="row">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="product-card h-100">
                                <div class="product-image">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info d-flex flex-column">
                                    <div class="product-category">
                                        <?php echo htmlspecialchars($producto['categoria_nombre'] ?? 'Sin categoría'); ?>
                                    </div>
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($producto['nombre']); ?>
                                    </div>
                                    <div class="product-description">
                                        <?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?>
                                    </div>
                                    <div class="product-price mt-auto">
                                        <?php echo formatCurrency($producto['precio_base']); ?>
                                    </div>
                                    <button class="btn btn-add-cart mt-2" onclick="addToCart(<?php echo $producto['id_producto']; ?>)">
                                        <i class="fas fa-cart-plus"></i> Añadir al Carrito
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>No se encontraron productos</h3>
                    <p>Intenta con otros filtros de búsqueda</p>
                    <a href="/petspa/public/cliente/tienda.php" class="btn btn-primary mt-3">
                        <i class="fas fa-refresh"></i> Ver Todos los Productos
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALERTS -->
    <div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <!-- Alerts will be inserted here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para añadir al carrito
        function addToCart(productoId) {
            fetch('/petspa/api/cliente/carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    cantidad: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Producto añadido al carrito exitosamente', 'success');
                } else {
                    showAlert(data.message || 'Error al añadir producto', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Función para mostrar alertas
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();

            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            alertContainer.insertAdjacentHTML('beforeend', alertHTML);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        // Limpiar filtros
        function clearFilters() {
            window.location.href = '/petspa/public/cliente/tienda.php';
        }
    </script>
</body>
</html>