<?php
/**
 * API - ACTUALIZAR CARRITO
 * ========================
 * Endpoint para actualizar la cantidad de un producto en el carrito
 * POST: producto_id, cantidad
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);

header('Content-Type: application/json');

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);
$productoId = $input['producto_id'] ?? null;
$cantidad = $input['cantidad'] ?? null;

// Validar entrada
if (!$productoId || !is_numeric($productoId) || $productoId <= 0) {
    Middleware::sendError('ID de producto inválido', 400);
}

if (!$cantidad || !is_numeric($cantidad) || $cantidad < 0) {
    Middleware::sendError('Cantidad inválida', 400);
}

try {
    // Verificar que el producto existe y está activo
    $stmt = $conn->prepare("SELECT id_producto, nombre, precio, stock FROM producto WHERE id_producto = ? AND estado = 1");
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        Middleware::sendError('Producto no encontrado', 404);
    }

    // Verificar stock disponible
    if ($cantidad > $producto['stock']) {
        Middleware::sendError('Cantidad excede el stock disponible (' . $producto['stock'] . ' unidades)', 400);
    }

    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Actualizar cantidad en el carrito
    if ($cantidad == 0) {
        // Si cantidad es 0, eliminar del carrito
        unset($_SESSION['carrito'][$productoId]);
    } else {
        $_SESSION['carrito'][$productoId] = (int)$cantidad;
    }

    // Calcular totales del carrito
    $totalItems = 0;
    $totalPrecio = 0;

    if (!empty($_SESSION['carrito'])) {
        $placeholders = str_repeat('?,', count($_SESSION['carrito']) - 1) . '?';
        $stmt = $conn->prepare("SELECT id_producto, precio FROM producto WHERE id_producto IN ($placeholders)");
        $stmt->execute(array_keys($_SESSION['carrito']));
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as $prod) {
            $id = $prod['id_producto'];
            if (isset($_SESSION['carrito'][$id])) {
                $cant = $_SESSION['carrito'][$id];
                $totalItems += $cant;
                $totalPrecio += $prod['precio'] * $cant;
            }
        }
    }

    Middleware::sendSuccess([
        'message' => 'Carrito actualizado exitosamente',
        'producto' => [
            'id' => $productoId,
            'nombre' => $producto['nombre'],
            'cantidad' => $cantidad,
            'precio_unitario' => $producto['precio'],
            'subtotal' => $producto['precio'] * $cantidad
        ],
        'carrito' => [
            'total_items' => $totalItems,
            'total_precio' => $totalPrecio
        ]
    ]);

} catch (Exception $e) {
    error_log($e->getMessage());
    Middleware::sendError('Error interno del servidor', 500);
}
?>