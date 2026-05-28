<?php
/**
 * API - ELIMINAR ITEM DEL CARRITO
 * ===============================
 * Endpoint para eliminar un producto del carrito
 * POST: producto_id
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

// Validar entrada
if (!$productoId || !is_numeric($productoId) || $productoId <= 0) {
    Middleware::sendError('ID de producto inválido', 400);
}

try {
    // Inicializar carrito si no existe
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }

    // Verificar que el producto está en el carrito
    if (!isset($_SESSION['carrito'][$productoId])) {
        Middleware::sendError('Producto no encontrado en el carrito', 404);
    }

    // Obtener información del producto antes de eliminar
    $stmt = $conn->prepare("SELECT nombre FROM producto WHERE id_producto = ?");
    $stmt->execute([$productoId]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    $productoNombre = $producto ? $producto['nombre'] : 'Producto desconocido';

    // Eliminar del carrito
    unset($_SESSION['carrito'][$productoId]);

    // Calcular totales actualizados del carrito
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
        'message' => 'Producto eliminado del carrito',
        'producto_eliminado' => [
            'id' => $productoId,
            'nombre' => $productoNombre
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