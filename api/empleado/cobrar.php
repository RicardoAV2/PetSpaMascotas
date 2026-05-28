<?php
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";
require_once "../../core/Logger.php";

session_start();
Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $citaId = intval($_POST['cita_id'] ?? 0);
    $pedidoId = intval($_POST['pedido_id'] ?? 0);
    $facturaId = intval($_POST['factura_id'] ?? 0);
    $monto = floatval($_POST['monto'] ?? 0);
    $metodoPago = trim($_POST['metodo_pago'] ?? '');
    $referencia = trim($_POST['referencia_transaccion'] ?? '');

    if ($monto <= 0 || !$metodoPago) {
        throw new Exception('Datos incompletos para procesar el pago.');
    }

    if (!$facturaId && !$citaId && !$pedidoId) {
        throw new Exception('Debe seleccionar una cita, un pedido o una factura existente.');
    }

    $conn->beginTransaction();

    if (!$facturaId && $citaId) {
        $stmt = $conn->prepare('SELECT id_factura FROM factura WHERE id_cita = ? LIMIT 1');
        $stmt->execute([$citaId]);
        $existingFactura = $stmt->fetchColumn();
        if ($existingFactura) {
            $facturaId = intval($existingFactura);
        }
    }

    if (!$facturaId && $pedidoId) {
        $stmt = $conn->prepare('SELECT id_factura FROM factura WHERE id_pedido = ? LIMIT 1');
        $stmt->execute([$pedidoId]);
        $existingFactura = $stmt->fetchColumn();
        if ($existingFactura) {
            $facturaId = intval($existingFactura);
        }
    }

    if ($facturaId > 0) {
        $stmt = $conn->prepare('SELECT f.*, c.estado AS cita_estado FROM factura f LEFT JOIN cita c ON f.id_cita = c.id_cita WHERE f.id_factura = ? LIMIT 1');
        $stmt->execute([$facturaId]);
        $factura = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$factura) {
            throw new Exception('Factura no encontrada.');
        }
        if ($factura['estado_factura'] === 'cancelada') {
            throw new Exception('No se puede pagar una factura cancelada.');
        }

        $stmt = $conn->prepare('SELECT COALESCE(SUM(monto), 0) FROM pago WHERE id_factura = ?');
        $stmt->execute([$facturaId]);
        $pagado = floatval($stmt->fetchColumn());
        $saldo = max(0.00, floatval($factura['total']) - $pagado);

        if ($monto > $saldo) {
            throw new Exception('El monto excede el saldo pendiente de la factura.');
        }

        $stmt = $conn->prepare(
            'INSERT INTO pago (monto, metodo_pago, referencia_transaccion, estado_pago, id_factura) VALUES (:monto, :metodo, :referencia, "completado", :factura)'
        );
        $stmt->execute([
            ':monto' => $monto,
            ':metodo' => $metodoPago,
            ':referencia' => $referencia,
            ':factura' => $facturaId
        ]);

        $pagado += $monto;
        $nuevoEstado = $pagado >= floatval($factura['total']) ? 'pagada' : 'pendiente';
        if ($nuevoEstado !== $factura['estado_factura']) {
            $stmt = $conn->prepare('UPDATE factura SET estado_factura = ? WHERE id_factura = ?');
            $stmt->execute([$nuevoEstado, $facturaId]);
        }

        if ($nuevoEstado === 'pagada' && $factura['id_cita'] && $factura['cita_estado'] === 'agendada') {
            $stmt = $conn->prepare('UPDATE cita SET estado = "confirmada" WHERE id_cita = ?');
            $stmt->execute([$factura['id_cita']]);
        }

        if ($nuevoEstado === 'pagada' && $factura['id_pedido']) {
            $stmt = $conn->prepare('UPDATE carrito SET estado_pedido = "pagado" WHERE id_carrito = ?');
            $stmt->execute([$factura['id_pedido']]);
        }

        $conn->commit();
        Logger::logSale($currentUser['id'], $monto, ['factura' => $facturaId, 'cita' => $factura['id_cita'] ?? null, 'pedido' => $factura['id_pedido'] ?? null]);
        header('Location: /petspa/public/empleado/recepcionista/cobrar.php?success=1');
        exit();
    }

    $lineItems = [];
    $subtotal = 0.00;
    $impuesto = 0.00;
    $total = 0.00;
    $facturaCitaId = null;
    $facturaPedidoId = null;

    if ($citaId) {
        $stmt = $conn->prepare(
            'SELECT cs.id_servicio, s.nombre AS servicio_nombre, s.precio_base, cs.cantidad FROM cita_servicio cs JOIN servicio s ON cs.id_servicio = s.id_servicio WHERE cs.id_cita = ?'
        );
        $stmt->execute([$citaId]);
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($servicios)) {
            $stmt = $conn->prepare(
                'SELECT c.id_servicio, s.nombre AS servicio_nombre, s.precio_base FROM cita c JOIN servicio s ON c.id_servicio = s.id_servicio WHERE c.id_cita = ?'
            );
            $stmt->execute([$citaId]);
            $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$servicio) {
                throw new Exception('No se encontró el servicio para la cita.');
            }
            $servicios = [
                ['id_servicio' => $servicio['id_servicio'], 'servicio_nombre' => $servicio['servicio_nombre'], 'precio_base' => $servicio['precio_base'], 'cantidad' => 1]
            ];
        }

        foreach ($servicios as $item) {
            $cantidad = intval($item['cantidad'] ?? 1);
            $precioUnitario = floatval($item['precio_base']);
            $subtotalLinea = round($precioUnitario * $cantidad, 2);
            $subtotal += $subtotalLinea;
            $lineItems[] = [
                'concepto' => 'Servicio: ' . $item['servicio_nombre'],
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalLinea,
                'id_servicio' => intval($item['id_servicio']),
                'id_producto' => null
            ];
        }

        $facturaCitaId = $citaId;
    } elseif ($pedidoId) {
        $stmt = $conn->prepare('SELECT id_carrito, subtotal, descuento, total, estado_pedido FROM carrito WHERE id_carrito = ? LIMIT 1');
        $stmt->execute([$pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) {
            throw new Exception('Pedido no encontrado.');
        }
        if ($pedido['estado_pedido'] === 'cancelado') {
            throw new Exception('No se puede facturar un pedido cancelado.');
        }

        $stmt = $conn->prepare(
            'SELECT dc.cantidad, dc.precio_unitario, p.nombre AS producto_nombre, v.atributo, v.valor FROM detalle_carrito dc JOIN producto p ON dc.id_producto = p.id_producto LEFT JOIN variante_producto v ON dc.id_variante = v.id_variante WHERE dc.id_carrito = ?'
        );
        $stmt->execute([$pedidoId]);
        $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($productos)) {
            throw new Exception('El pedido no contiene productos para facturar.');
        }

        foreach ($productos as $item) {
            $cantidad = intval($item['cantidad']);
            $precioUnitario = floatval($item['precio_unitario']);
            $concepto = 'Producto: ' . $item['producto_nombre'];
            if (!empty($item['atributo']) && !empty($item['valor'])) {
                $concepto .= ' (' . $item['atributo'] . ': ' . $item['valor'] . ')';
            }
            $subtotalLinea = round($precioUnitario * $cantidad, 2);
            $subtotal += $subtotalLinea;
            $lineItems[] = [
                'concepto' => $concepto,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'subtotal' => $subtotalLinea,
                'id_servicio' => null,
                'id_producto' => null
            ];
        }

        $impuesto = round(floatval($pedido['total']) - floatval($pedido['subtotal']), 2);
        $facturaPedidoId = $pedidoId;
    }

    $subtotal = round($subtotal, 2);
    if ($pedidoId) {
        $total = round($subtotal + $impuesto, 2);
    } else {
        $total = $subtotal;
        $impuesto = 0.00;
    }

    if ($total <= 0) {
        throw new Exception('Total de factura inválido.');
    }

    $estadoFactura = $monto >= $total ? 'pagada' : 'pendiente';
    do {
        $numeroFactura = 'F' . date('YmdHis') . rand(100, 999);
        $stmt = $conn->prepare('SELECT COUNT(*) FROM factura WHERE numero_factura = ?');
        $stmt->execute([$numeroFactura]);
    } while ($stmt->fetchColumn() > 0);

    $stmt = $conn->prepare(
        'INSERT INTO factura (numero_factura, subtotal, impuesto, total, estado_factura, id_cita, id_pedido) VALUES (:numero, :subtotal, :impuesto, :total, :estado, :id_cita, :id_pedido)'
    );
    $stmt->execute([
        ':numero' => $numeroFactura,
        ':subtotal' => $subtotal,
        ':impuesto' => $impuesto,
        ':total' => $total,
        ':estado' => $estadoFactura,
        ':id_cita' => $facturaCitaId,
        ':id_pedido' => $facturaPedidoId
    ]);

    $facturaId = intval($conn->lastInsertId());
    $stmtDetalle = $conn->prepare(
        'INSERT INTO detalle_factura (id_factura, concepto, cantidad, precio_unitario, subtotal, id_producto, id_servicio) VALUES (:factura, :concepto, :cantidad, :precio, :subtotal, :id_producto, :id_servicio)'
    );

    foreach ($lineItems as $item) {
        $stmtDetalle->execute([
            ':factura' => $facturaId,
            ':concepto' => $item['concepto'],
            ':cantidad' => $item['cantidad'],
            ':precio' => $item['precio_unitario'],
            ':subtotal' => $item['subtotal'],
            ':id_producto' => $item['id_producto'],
            ':id_servicio' => $item['id_servicio']
        ]);
    }

    $stmt = $conn->prepare(
        'INSERT INTO pago (monto, metodo_pago, referencia_transaccion, estado_pago, id_factura) VALUES (:monto, :metodo, :referencia, "completado", :factura)'
    );
    $stmt->execute([
        ':monto' => $monto,
        ':metodo' => $metodoPago,
        ':referencia' => $referencia,
        ':factura' => $facturaId
    ]);

    $stmt = $conn->prepare('SELECT COALESCE(SUM(monto), 0) FROM pago WHERE id_factura = ?');
    $stmt->execute([$facturaId]);
    $pagado = floatval($stmt->fetchColumn());
    $nuevoEstado = $pagado >= $total ? 'pagada' : 'pendiente';
    if ($nuevoEstado !== $estadoFactura) {
        $stmt = $conn->prepare('UPDATE factura SET estado_factura = ? WHERE id_factura = ?');
        $stmt->execute([$nuevoEstado, $facturaId]);
    }

    if ($nuevoEstado === 'pagada') {
        if ($facturaCitaId) {
            $stmt = $conn->prepare('UPDATE cita SET estado = "confirmada" WHERE id_cita = ? AND estado = "agendada"');
            $stmt->execute([$facturaCitaId]);
        }
        if ($facturaPedidoId) {
            $stmt = $conn->prepare('UPDATE carrito SET estado_pedido = "pagado" WHERE id_carrito = ?');
            $stmt->execute([$facturaPedidoId]);
        }
    }

    $conn->commit();
    Logger::logSale($currentUser['id'], $monto, ['factura' => $facturaId, 'cita' => $facturaCitaId, 'pedido' => $facturaPedidoId]);

    header('Location: /petspa/public/empleado/recepcionista/cobrar.php?success=1');
    exit();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $error = urlencode($e->getMessage());
    header("Location: /petspa/public/empleado/recepcionista/cobrar.php?error={$error}");
    exit();
}
