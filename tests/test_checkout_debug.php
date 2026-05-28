<?php
/**
 * Test de checkout para debug
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/helpers.php';

session_start();

try {
    echo "1. Verificando conexión a BD...\n";
    if (!$conn) {
        throw new Exception("Conexión a BD es null");
    }
    echo "   ✓ Conexión OK\n";

    echo "2. Verificando que Auth está disponible...\n";
    Auth::setConnection($conn);
    echo "   ✓ Auth OK\n";

    echo "3. Verificando función generateOrderMessage...\n";
    $testItems = [
        ['cantidad' => 1, 'nombre' => 'Test', 'precio' => '100']
    ];
    $msg = generateOrderMessage($testItems, '100.00', '13.00', '113.00');
    echo "   ✓ Función OK\n";
    echo "   Mensaje: \n$msg\n";

    echo "4. Creando usuario cliente de prueba...\n";
    $stmt = $conn->prepare("SELECT id_rol FROM rol WHERE nombre = 'cliente' LIMIT 1");
    $stmt->execute();
    $clienteRol = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$clienteRol) {
        throw new Exception("Rol cliente no encontrado");
    }

    $testEmail = 'checkout_test_' . uniqid() . '@example.local';
    $stmt = $conn->prepare("INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $testEmail,
        password_hash('Password123!', PASSWORD_BCRYPT),
        'Test',
        'Cliente',
        '77777777',
        $clienteRol['id_rol']
    ]);
    $userId = intval($conn->lastInsertId());
    
    $stmt = $conn->prepare("INSERT INTO cliente (id_cliente, direccion, ci, canal_notificacion_preferido) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, 'Calle 123', '12345678', 'email']);
    echo "   ✓ Usuario cliente creado: $userId\n";

    echo "5. Creando producto de prueba...\n";
    $productName = 'Test Producto ' . uniqid();
    $stmt = $conn->prepare("INSERT INTO producto (nombre, precio_base, stock_actual, stock_minimo, estado_activo) VALUES (?, ?, ?, 1, 1)");
    $stmt->execute([$productName, 50.00, 100]);
    $productId = intval($conn->lastInsertId());
    echo "   ✓ Producto creado: $productId\n";

    echo "6. Simulando carrito en sesión...\n";
    $_SESSION['carrito'] = [$productId => 2];
    echo "   ✓ Carrito simulado\n";

    echo "7. Simulando usuario autenticado...\n";
    $_SESSION['id'] = $userId;
    $_SESSION['rol'] = 'cliente';
    $_SESSION['email'] = $testEmail;
    echo "   ✓ Sesión simulada\n";

    echo "8. Ejecutando lógica de checkout...\n";
    
    // Simulación de código checkout
    $carrito = $_SESSION['carrito'] ?? [];
    if (empty($carrito)) {
        throw new Exception("El carrito está vacío");
    }

    $productIds = array_keys($carrito);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $conn->prepare("SELECT id_producto, nombre, precio_base, stock_actual FROM producto WHERE id_producto IN ($placeholders) AND estado_activo = 1");
    $stmt->execute($productIds);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Productos recuperados\n";

    $productosPorId = [];
    foreach ($productos as $producto) {
        $productosPorId[$producto['id_producto']] = $producto;
    }

    $subtotal = 0;
    $cartItems = [];
    foreach ($carrito as $productoId => $cantidad) {
        if (!isset($productosPorId[$productoId])) {
            throw new Exception("Producto en carrito no disponible: $productoId");
        }
        $producto = $productosPorId[$productoId];
        if ($cantidad > intval($producto['stock_actual'])) {
            throw new Exception("No hay stock para: " . $producto['nombre']);
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
    echo "   ✓ Carrito validado\n";

    $iva = round($subtotal * 0.13, 2);
    $total = round($subtotal + $iva, 2);
    echo "   Subtotal: $subtotal, IVA: $iva, Total: $total\n";

    $stmt = $conn->prepare("SELECT cl.id_cliente, u.telefono, u.email FROM cliente cl JOIN usuario u ON cl.id_cliente = u.id_usuario WHERE cl.id_cliente = ? LIMIT 1");
    $stmt->execute([$userId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cliente) {
        throw new Exception("Cliente no encontrado en BD");
    }
    echo "   ✓ Cliente recuperado\n";

    $contactMethod = !empty($cliente['telefono']) ? 'whatsapp' : 'email';
    $contactDestination = !empty($cliente['telefono']) ? $cliente['telefono'] : $cliente['email'];
    $sessionToken = session_id() ?: bin2hex(random_bytes(16));
    $expiresAt = (new DateTime())->modify('+7 days')->format('Y-m-d H:i:s');

    echo "9. Iniciando transacción...\n";
    if (!$conn->beginTransaction()) {
        throw new Exception("No se pudo iniciar la transacción");
    }
    echo "   ✓ Transacción iniciada\n";

    echo "10. Insertando carrito...\n";
    $stmt = $conn->prepare("INSERT INTO carrito (session_token, id_cliente, fecha_creacion, expires_at, metodo_contacto, contacto_destino, estado_pedido, subtotal, descuento, total) VALUES (:token, :cliente, NOW(), :expires, :metodo, :destino, 'pendiente', :subtotal, 0, :total)");
    $result = $stmt->execute([
        ':token' => $sessionToken,
        ':cliente' => $cliente['id_cliente'],
        ':expires' => $expiresAt,
        ':metodo' => $contactMethod,
        ':destino' => $contactDestination,
        ':subtotal' => $subtotal,
        ':total' => $total
    ]);
    if (!$result) {
        throw new Exception("Fallo INSERT carrito: " . json_encode($stmt->errorInfo()));
    }
    $carritoId = intval($conn->lastInsertId());
    echo "   ✓ Carrito insertado: $carritoId\n";

    echo "11. Insertando detalles carrito...\n";
    $stmtDetalle = $conn->prepare("INSERT INTO detalle_carrito (id_carrito, id_producto, id_variante, cantidad, precio_unitario) VALUES (:carrito, :producto, NULL, :cantidad, :precio)");
    $stmtUpdateStock = $conn->prepare("UPDATE producto SET stock_actual = GREATEST(stock_actual - :cantidad, 0) WHERE id_producto = :producto");

    foreach ($cartItems as $item) {
        $resultDet = $stmtDetalle->execute([
            ':carrito' => $carritoId,
            ':producto' => $item['id'],
            ':cantidad' => $item['cantidad'],
            ':precio' => floatval(str_replace(',', '.', str_replace('.', '', $item['precio'])))
        ]);
        if (!$resultDet) {
            throw new Exception("Fallo INSERT detalle: " . json_encode($stmtDetalle->errorInfo()));
        }

        $resultUpd = $stmtUpdateStock->execute([':cantidad' => $item['cantidad'], ':producto' => $item['id']]);
        if (!$resultUpd) {
            throw new Exception("Fallo UPDATE stock: " . json_encode($stmtUpdateStock->errorInfo()));
        }
    }
    echo "   ✓ Detalles insertados\n";

    echo "12. Confirmando transacción...\n";
    $conn->commit();
    echo "   ✓ Transacción confirmada\n";

    echo "\n✅ CHECKOUT EXITOSO\n";
    echo "   Carrito ID: $carritoId\n";
    echo "   Total: " . number_format($total, 2, ',', '.') . "\n";

    // Cleanup
    echo "\n13. Limpiando datos de prueba...\n";
    $conn->beginTransaction();
    $stmt = $conn->prepare("DELETE FROM detalle_carrito WHERE id_carrito = ?");
    $stmt->execute([$carritoId]);
    $stmt = $conn->prepare("DELETE FROM carrito WHERE id_carrito = ?");
    $stmt->execute([$carritoId]);
    $stmt = $conn->prepare("DELETE FROM cliente WHERE id_cliente = ?");
    $stmt->execute([$userId]);
    $stmt = $conn->prepare("DELETE FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$userId]);
    $stmt = $conn->prepare("DELETE FROM producto WHERE id_producto = ?");
    $stmt->execute([$productId]);
    $conn->commit();
    echo "   ✓ Datos limpios\n";

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
