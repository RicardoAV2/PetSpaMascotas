<?php
/**
 * CARRITO DE COMPRAS
 * ===================
 * Página para gestionar el carrito de compras del cliente
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

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$carrito = $_SESSION['carrito'];
$productosCarrito = [];
$total = 0;
$subtotal = 0;

// Obtener detalles de productos en el carrito
if (!empty($carrito)) {
    $placeholders = str_repeat('?,', count($carrito) - 1) . '?';
    $stmt = $conn->prepare("SELECT * FROM producto WHERE id_producto IN ($placeholders) AND estado = 1");
    $stmt->execute(array_keys($carrito));
    $productosBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear array con productos del carrito
    foreach ($productosBD as $producto) {
        $id = $producto['id_producto'];
        if (isset($carrito[$id])) {
            $cantidad = $carrito[$id];
            $precio = $producto['precio'];
            $subtotalProducto = $precio * $cantidad;

            $productosCarrito[] = [
                'id' => $id,
                'nombre' => $producto['nombre'],
                'descripcion' => $producto['descripcion'],
                'precio' => $precio,
                'cantidad' => $cantidad,
                'subtotal' => $subtotalProducto,
                'imagen' => $producto['imagen'] ?? null
            ];

            $subtotal += $subtotalProducto;
        }
    }
}

// Calcular totales
$iva = $subtotal * 0.13; // IVA 13%
$total = $subtotal + $iva;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - Pet Spa</title>
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

        /* CARRITO */
        .cart-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .cart-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }

        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 20px;
            transition: all 0.3s;
        }

        .cart-item:hover {
            background: #f8f9fa;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .product-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: white;
        }

        .product-info h5 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .product-info p {
            font-size: 14px;
            color: #666;
            margin-bottom: 0;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quantity-btn:hover {
            background: #667eea;
            color: white;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            padding: 5px;
        }

        .price-info {
            text-align: right;
        }

        .price-info .price {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        .price-info .subtotal {
            font-size: 16px;
            color: #666;
        }

        .remove-btn {
            color: #dc3545;
            cursor: pointer;
            transition: all 0.3s;
        }

        .remove-btn:hover {
            color: #c82333;
        }

        /* RESUMEN */
        .cart-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-row.total {
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        .btn-checkout {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            width: 100%;
        }

        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        /* EMPTY CART */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }

        .empty-cart i {
            font-size: 80px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-cart h3 {
            margin-bottom: 15px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .quantity-controls {
                justify-content: center;
                margin-top: 15px;
            }

            .price-info {
                text-align: center;
                margin-top: 15px;
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
                        <a class="nav-link" href="/petspa/public/cliente/tienda.php">
                            <i class="fas fa-shopping-bag"></i> Tienda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/petspa/public/cliente/carrito.php">
                            <i class="fas fa-shopping-cart"></i> Carrito
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
                            <li><a class="dropdown-item" href="/petspa/public/cliente/pedidos.php">
                                <i class="fas fa-list"></i> Mis Pedidos
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
                        <i class="fas fa-shopping-cart text-primary"></i> Mi Carrito de Compras
                    </h1>
                    <p class="text-center text-muted">
                        <?php echo count($productosCarrito); ?> producto(s) en tu carrito
                    </p>
                </div>
            </div>

            <?php if (!empty($productosCarrito)): ?>
                <div class="row">
                    <!-- CARRITO -->
                    <div class="col-lg-8">
                        <div class="cart-container">
                            <div class="cart-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-shopping-cart"></i> Productos en tu Carrito
                                </h4>
                            </div>

                            <?php foreach ($productosCarrito as $item): ?>
                                <div class="cart-item d-flex align-items-center">
                                    <div class="product-image me-3">
                                        <i class="fas fa-box"></i>
                                    </div>

                                    <div class="product-info flex-grow-1">
                                        <h5><?php echo htmlspecialchars($item['nombre']); ?></h5>
                                        <p><?php echo htmlspecialchars(substr($item['descripcion'] ?? '', 0, 100)); ?>...</p>
                                    </div>

                                    <div class="quantity-controls me-3">
                                        <div class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['cantidad'] - 1; ?>)">
                                            <i class="fas fa-minus"></i>
                                        </div>
                                        <input type="number" class="quantity-input" value="<?php echo $item['cantidad']; ?>"
                                               min="1" onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                        <div class="quantity-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['cantidad'] + 1; ?>)">
                                            <i class="fas fa-plus"></i>
                                        </div>
                                    </div>

                                    <div class="price-info me-3">
                                        <div class="price"><?php echo formatCurrency($item['precio']); ?></div>
                                        <div class="subtotal">Subtotal: <?php echo formatCurrency($item['subtotal']); ?></div>
                                    </div>

                                    <div class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash-alt fa-lg"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- RESUMEN -->
                    <div class="col-lg-4">
                        <div class="cart-summary">
                            <h4 class="mb-3">
                                <i class="fas fa-receipt"></i> Resumen del Pedido
                            </h4>

                            <div class="summary-row">
                                <span>Subtotal:</span>
                                <span><?php echo formatCurrency($subtotal); ?></span>
                            </div>

                            <div class="summary-row">
                                <span>IVA (13%):</span>
                                <span><?php echo formatCurrency($iva); ?></span>
                            </div>

                            <div class="summary-row total">
                                <span>Total:</span>
                                <span><?php echo formatCurrency($total); ?></span>
                            </div>

                            <button class="btn btn-checkout mt-4" onclick="proceedToCheckout()">
                                <i class="fas fa-credit-card"></i> Proceder al Pago
                            </button>

                            <a href="/petspa/public/cliente/tienda.php" class="btn btn-outline-primary mt-2 w-100">
                                <i class="fas fa-arrow-left"></i> Continuar Comprando
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- CARRITO VACÍO -->
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Tu carrito está vacío</h3>
                    <p>¡Agrega algunos productos para comenzar!</p>
                    <a href="/petspa/public/cliente/tienda.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-shopping-bag"></i> Ir a la Tienda
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
        // Actualizar cantidad
        function updateQuantity(productoId, nuevaCantidad) {
            if (nuevaCantidad < 1) {
                removeItem(productoId);
                return;
            }

            fetch('/petspa/api/cliente/actualizar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    producto_id: productoId,
                    cantidad: parseInt(nuevaCantidad)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert(data.message || 'Error al actualizar cantidad', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Eliminar item
        function removeItem(productoId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este producto del carrito?')) {
                return;
            }

            fetch('/petspa/api/cliente/eliminar_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    producto_id: productoId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert(data.message || 'Error al eliminar producto', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Proceder al checkout
        function proceedToCheckout() {
            // TODO: Implementar checkout
            showAlert('Funcionalidad de checkout próximamente', 'info');
        }

        // Mostrar alertas
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
    </script>
</body>
</html>