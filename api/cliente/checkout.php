<?php
/**
 * API - CHECKOUT / GENERACIÓN DE PEDIDO
 * ====================================
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

session_start();
Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Middleware::sendError('Método inválido.', 405);
}

$carrito = $_SESSION['carrito'] ?? [];
if (empty($carrito)) {
    Middleware::sendError('El carrito está vacío.', 400);
}

try {
    $productIds = array_keys($carrito);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $conn->prepare("SELECT id_producto, nombre, precio_base, stock_actual FROM producto WHERE id_producto IN ($placeholders) AND estado_activo = 1");
    $stmt->execute($productIds);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productosPorId = [];
    foreach ($productos as $producto) {
        $productosPorId[$producto['id_producto']] = $producto;
    }

    $subtotal = 0;
    $cartItems = [];
    foreach ($carrito as $productoId => $cantidad) {
        if (!isset($productosPorId[$productoId])) {
            Middleware::sendError('Producto en el carrito no está disponible.', 400);
        }
        $producto = $productosPorId[$productoId];
        if ($cantidad > intval($producto['stock_actual'])) {
            Middleware::sendError('No hay suficiente stock para: ' . $producto['nombre'], 400);
        }
        $precioBase = floatval($producto['precio_base']);
        $subtotal += $precioBase * intval($cantidad);
        $cartItems[] = [
            'id' => $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'cantidad' => intval($cantidad),
            'precio' => number_format($precioBase, 2, ',', '.'),
            'precio_unitario' => $precioBase
        ];
    }

    $iva = round($subtotal * 0.13, 2);
    $total = round($subtotal + $iva, 2);

    $userId = Auth::getCurrentUser()['id'];
    $stmt = $conn->prepare("SELECT cl.id_cliente, u.telefono, u.email FROM cliente cl JOIN usuario u ON cl.id_cliente = u.id_usuario WHERE cl.id_cliente = ? LIMIT 1");
    $stmt->execute([$userId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        Middleware::sendError('Cliente no encontrado.', 400);
    }

    $contactMethod = !empty($cliente['telefono']) ? 'whatsapp' : 'email';
    $contactDestination = !empty($cliente['telefono']) ? $cliente['telefono'] : $cliente['email'];
    $sessionToken = session_id() ?: bin2hex(random_bytes(16));
    $expiresAt = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

    $conn->beginTransaction();
    $stmt = $conn->prepare("INSERT INTO carrito (session_token, id_cliente, fecha_creacion, expires_at, metodo_contacto, contacto_destino, estado_pedido, subtotal, descuento, total) VALUES (:token, :cliente, NOW(), :expires, :metodo, :destino, 'pendiente', :subtotal, 0, :total)");
    $stmt->execute([
        ':token' => $sessionToken,
        ':cliente' => $cliente['id_cliente'],
        ':expires' => $expiresAt,
        ':metodo' => $contactMethod,
        ':destino' => $contactDestination,
        ':subtotal' => $subtotal,
        ':total' => $total
    ]);

    $carritoId = intval($conn->lastInsertId());
    $stmtDetalle = $conn->prepare("INSERT INTO detalle_carrito (id_carrito, id_producto, id_variante, cantidad, precio_unitario) VALUES (:carrito, :producto, NULL, :cantidad, :precio)");
    $stmtUpdateStock = $conn->prepare("UPDATE producto SET stock_actual = GREATEST(stock_actual - :cantidad, 0) WHERE id_producto = :producto");

    foreach ($cartItems as $item) {
        $stmtDetalle->execute([
            ':carrito' => $carritoId,
            ':producto' => $item['id'],
            ':cantidad' => $item['cantidad'],
            ':precio' => floatval(str_replace(',', '.', str_replace('.', '', $item['precio'])))
        ]);
        $stmtUpdateStock->execute([':cantidad' => $item['cantidad'], ':producto' => $item['id']]);
    }

    $conn->commit();
    $_SESSION['carrito'] = [];

    $messageText = generateOrderMessage($cartItems, number_format($subtotal, 2, ',', '.'), number_format($iva, 2, ',', '.'), number_format($total, 2, ',', '.'));
    $payload = ['pedido_id' => $carritoId, 'metodo_contacto' => $contactMethod, 'contacto_destino' => $contactDestination, 'mensaje' => $messageText];

    if ($contactMethod === 'whatsapp') {
        $messageEncoded = rawurlencode($messageText);
        $payload['whatsapp_url'] = "https://wa.me/" . preg_replace('/[^0-9]/', '', $contactDestination) . "?text=$messageEncoded";
    } else {
        $payload['email_url'] = 'mailto:' . rawurlencode($contactDestination) . '?subject=' . rawurlencode('Pedido Pet Spa') . '&body=' . rawurlencode($messageText);
    }

    Middleware::sendSuccess($payload, 'Pedido generado correctamente.');
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error en checkout: ' . $e->getMessage());
    Middleware::sendError('Error al procesar el pedido.', 500);
}
