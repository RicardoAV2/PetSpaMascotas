<?php
/**
 * API - AÑADIR AL CARRITO
 * =======================
 * Endpoint para añadir productos al carrito de compras
 * POST: producto_id, cantidad (opcional, default 1)
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
$cantidad = $input['cantidad'] ?? 1;

// Validar entrada
if (!$productoId || !is_numeric($productoId) || $productoId <= 0) {
    Middleware::sendError('ID de producto inválido', 400);
}

if (!is_numeric($cantidad) || $cantidad < 1) {
    Middleware::sendError('Cantidad debe ser un número positivo', 400);
}

try {
    // Verificar que el producto existe y está activo
    $stmt = $conn->prepare("SELECT id_producto, nombre, precio_base, stock_actual FROM producto WHERE id_producto = ? AND estado_activo = 1");
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        Middleware::sendError('Producto no encontrado', 404);
    }

    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Verificar stock disponible
    $cantidadActual = $_SESSION['carrito'][$productoId] ?? 0;
    $nuevaCantidad = $cantidadActual + $cantidad;

    if ($nuevaCantidad > $producto['stock_actual']) {
        Middleware::sendError('No hay suficiente stock disponible. Stock actual: ' . $producto['stock_actual'], 400);
    }

    // Añadir al carrito
    $_SESSION['carrito'][$productoId] = $nuevaCantidad;

    // Calcular totales del carrito
    $totalItems = 0;
    $totalPrecio = 0;

    if (!empty($_SESSION['carrito'])) {
        $placeholders = str_repeat('?,', count($_SESSION['carrito']) - 1) . '?';
        $stmt = $conn->prepare("SELECT id_producto, precio_base FROM producto WHERE id_producto IN ($placeholders)");
        $stmt->execute(array_keys($_SESSION['carrito']));
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productos as $prod) {
            $id = $prod['id_producto'];
            if (isset($_SESSION['carrito'][$id])) {
                $cant = $_SESSION['carrito'][$id];
                $totalItems += $cant;
                $totalPrecio += $prod['precio_base'] * $cant;
            }
        }
    }

    Middleware::sendSuccess([
        'message' => 'Producto añadido al carrito exitosamente',
        'producto' => [
            'id' => $productoId,
            'nombre' => $producto['nombre'],
            'cantidad_anadida' => $cantidad,
            'cantidad_total' => $nuevaCantidad,
            'precio_unitario' => $producto['precio_base']
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