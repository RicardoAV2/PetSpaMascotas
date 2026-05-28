<?php
/**
 * Helpers para manejo de productos, servicios y reservas en grooming
 */

/**
 * Obtener servicios de una cita con sus detalles
 */
function getServiciosCita($conn, $id_cita) {
    $stmt = $conn->prepare("
        SELECT cs.id_servicio, s.nombre, s.descripcion, s.duracion_base_minutos, s.precio_base
        FROM cita_servicio cs
        JOIN servicio s ON cs.id_servicio = s.id_servicio
        WHERE cs.id_cita = ?
        ORDER BY s.nombre
    ");
    $stmt->execute([$id_cita]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener productos disponibles en inventario
 */
function getProductosDisponibles($conn) {
    $stmt = $conn->prepare("
        SELECT 
            p.id_producto,
            p.nombre,
            p.descripcion,
            p.precio_base,
            COALESCE(i.cantidad_disponible, 0) as cantidad_disponible,
            COALESCE(i.cantidad_fisica, 0) as cantidad_fisica,
            p.stock_minimo
        FROM producto p
        LEFT JOIN inventario i ON p.id_producto = i.id_producto
        WHERE p.estado_activo = 1
        ORDER BY p.nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Reservar productos para un servicio específico de una cita
 * 
 * @param $conn PDO connection
 * @param $id_cita int ID de la cita
 * @param $id_servicio int ID del servicio
 * @param $productos array Array de ['id_producto' => int, 'cantidad' => int]
 * @param $tamano_mascota string (pequeno|mediano|grande)
 * @return bool true si se reservó exitosamente
 */
function reservarProductosServicio($conn, $id_cita, $id_servicio, $productos, $tamano_mascota = 'mediano') {
    try {
        // Verificar que la cita y servicio existan
        $stmt = $conn->prepare("
            SELECT 1 FROM cita_servicio 
            WHERE id_cita = ? AND id_servicio = ?
        ");
        $stmt->execute([$id_cita, $id_servicio]);
        if (!$stmt->fetch()) {
            return false;
        }

        // Limpiar reservas anteriores para este servicio
        $stmt = $conn->prepare("
            DELETE FROM servicio_producto_reserva
            WHERE id_cita = ? AND id_servicio = ? AND estado = 'reservado'
        ");
        $stmt->execute([$id_cita, $id_servicio]);

        // Insertar nuevas reservas
        $stmt = $conn->prepare("
            INSERT INTO servicio_producto_reserva 
            (id_cita, id_servicio, id_producto, cantidad_reservada, tamano_mascota, estado)
            VALUES (?, ?, ?, ?, ?, 'reservado')
        ");

        foreach ($productos as $producto) {
            $id_prod = intval($producto['id_producto'] ?? 0);
            $cantidad = max(1, intval($producto['cantidad'] ?? 1));

            if ($id_prod <= 0) continue;

            // Verificar disponibilidad
            $checkStmt = $conn->prepare("
                SELECT COALESCE(cantidad_disponible, 0) as cantidad_disponible FROM inventario
                WHERE id_producto = ?
            ");
            $checkStmt->execute([$id_prod]);
            $inv = $checkStmt->fetch(PDO::FETCH_ASSOC);

            // Si hay suficiente, reservar (o si no hay inventario, permitir igual)
            if ($inv === false || $inv['cantidad_disponible'] >= $cantidad || $inv['cantidad_disponible'] == 0) {
                $stmt->execute([$id_cita, $id_servicio, $id_prod, $cantidad, $tamano_mascota]);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log('Error en reservarProductosServicio: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtener productos reservados para un servicio
 */
function getProductosReservadosServicio($conn, $id_cita, $id_servicio) {
    $stmt = $conn->prepare("
        SELECT 
            spr.id_reserva_servicio,
            spr.id_producto,
            p.nombre AS producto_nombre,
            p.precio_base,
            spr.cantidad_reservada,
            spr.tamano_mascota,
            COALESCE(i.cantidad_disponible, 0) as cantidad_disponible
        FROM servicio_producto_reserva spr
        JOIN producto p ON spr.id_producto = p.id_producto
        LEFT JOIN inventario i ON p.id_producto = i.id_producto
        WHERE spr.id_cita = ? AND spr.id_servicio = ? AND spr.estado = 'reservado'
        ORDER BY p.nombre
    ");
    $stmt->execute([$id_cita, $id_servicio]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Registrar consumo de productos para un servicio durante el grooming
 * 
 * @param $conn PDO connection
 * @param $id_ficha int ID de la ficha de grooming
 * @param $id_servicio int ID del servicio
 * @param $productos_usados array Array de ['id_reserva_servicio' => int, 'cantidad_usada' => int]
 * @return bool true si se registró exitosamente
 */
function registrarConsumoServicio($conn, $id_ficha, $id_servicio, $productos_usados) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO ficha_servicio_productos_usados 
            (id_ficha, id_servicio, id_producto, cantidad_usada, cantidad_reservada)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($productos_usados as $reservaId => $cantidadUsada) {
            $reservaId = intval($reservaId);
            $cantidadUsada = max(0, intval($cantidadUsada));

            if ($reservaId <= 0) continue;

            // Obtener datos de la reserva
            $reservaStmt = $conn->prepare("
                SELECT spr.id_producto, spr.cantidad_reservada
                FROM servicio_producto_reserva spr
                WHERE spr.id_reserva_servicio = ?
            ");
            $reservaStmt->execute([$reservaId]);
            $reserva = $reservaStmt->fetch(PDO::FETCH_ASSOC);

            if ($reserva && $cantidadUsada > 0) {
                $stmt->execute([
                    $id_ficha,
                    $id_servicio,
                    $reserva['id_producto'],
                    $cantidadUsada,
                    $reserva['cantidad_reservada']
                ]);
            }
        }

        return true;
    } catch (Exception $e) {
        error_log('Error en registrarConsumoServicio: ' . $e->getMessage());
        return false;
    }
}

/**
 * Marcar servicio como completado (total o parcial)
 */
function registrarServicioCompletado($conn, $id_ficha, $id_servicio, $completado = true, $porcentaje = 100) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO ficha_servicio_registro 
            (id_ficha, id_servicio, completado, porcentaje_avance)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            completado = VALUES(completado),
            porcentaje_avance = VALUES(porcentaje_avance)
        ");
        return $stmt->execute([$id_ficha, $id_servicio, $completado ? 1 : 0, $porcentaje]);
    } catch (Exception $e) {
        error_log('Error en registrarServicioCompletado: ' . $e->getMessage());
        return false;
    }
}

/**
 * Obtener servicios completados y pendientes de una ficha
 */
function getServiciosCompletados($conn, $id_ficha) {
    $stmt = $conn->prepare("
        SELECT 
            fsr.id_servicio,
            s.nombre,
            fsr.completado,
            fsr.porcentaje_avance,
            fsr.notas
        FROM ficha_servicio_registro fsr
        JOIN servicio s ON fsr.id_servicio = s.id_servicio
        WHERE fsr.id_ficha = ?
        ORDER BY s.nombre
    ");
    $stmt->execute([$id_ficha]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Obtener estado general de la cita (qué está pendiente)
 */
function getEstadoCita($conn, $id_cita) {
    $stmt = $conn->prepare("
        SELECT 
            cs.id_servicio,
            s.nombre,
            COALESCE(fsr.completado, 0) as completado,
            COALESCE(fsr.porcentaje_avance, 0) as porcentaje
        FROM cita_servicio cs
        JOIN servicio s ON cs.id_servicio = s.id_servicio
        LEFT JOIN ficha_servicio_registro fsr ON cs.id_cita = fsr.id_ficha AND cs.id_servicio = fsr.id_servicio
        WHERE cs.id_cita = ?
    ");
    $stmt->execute([$id_cita]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Guardar cambios de inventario cuando se completa un servicio
 * Descontar productos usados del inventario
 */
function aplicarConsumoProductosServicio($conn, $id_ficha, $id_cita, $id_servicio, $usuario_id) {
    try {
        // Obtener productos usados en este servicio
        $stmt = $conn->prepare("
            SELECT fsp.id_producto, fsp.cantidad_usada
            FROM ficha_servicio_productos_usados fsp
            WHERE fsp.id_ficha = ? AND fsp.id_servicio = ?
        ");
        $stmt->execute([$id_ficha, $id_servicio]);
        $productosUsados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($productosUsados as $producto) {
            $id_prod = $producto['id_producto'];
            $cantidad = $producto['cantidad_usada'];

            // Obtener inventario
            $invStmt = $conn->prepare("
                SELECT id_inventario, cantidad_fisica, cantidad_reservada
                FROM inventario WHERE id_producto = ?
            ");
            $invStmt->execute([$id_prod]);
            $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

            if (!$inv) continue;

            $antesFisica = $inv['cantidad_fisica'];
            $antesReservada = $inv['cantidad_reservada'];
            $despuesFisica = max(0, $antesFisica - $cantidad);
            $despuesReservada = max(0, $antesReservada - $cantidad);

            // Actualizar inventario
            $updateStmt = $conn->prepare("
                UPDATE inventario 
                SET cantidad_fisica = ?, cantidad_reservada = ?
                WHERE id_inventario = ?
            ");
            $updateStmt->execute([$despuesFisica, $despuesReservada, $inv['id_inventario']]);

            // Registrar movimiento
            $movStmt = $conn->prepare("
                INSERT INTO movimiento_inventario 
                (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues,
                 cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, 
                 motivo, id_usuario_registra)
                VALUES (?, 'salida_consumo', ?, ?, ?, ?, ?, 'cita', ?, 'Consumo en servicio de grooming', ?)
            ");
            $movStmt->execute([
                $inv['id_inventario'],
                $cantidad,
                $antesFisica,
                $despuesFisica,
                $antesReservada,
                $despuesReservada,
                $id_cita,
                $usuario_id
            ]);

            // Marcar reservas como consumidas
            $reservaStmt = $conn->prepare("
                UPDATE servicio_producto_reserva
                SET estado = 'usado'
                WHERE id_cita = ? AND id_servicio = ? AND id_producto = ? AND estado = 'reservado'
            ");
            $reservaStmt->execute([$id_cita, $id_servicio, $id_prod]);
        }

        return true;
    } catch (Exception $e) {
        error_log('Error en aplicarConsumoProductosServicio: ' . $e->getMessage());
        return false;
    }
}

/**
 * Calcular cantidad recomendada de producto basado en tamaño de mascota
 */
function calcularCantidadPorTamano($cantidad_base, $tamano) {
    $multiplicadores = [
        'pequeno' => 0.7,
        'mediano' => 1.0,
        'grande' => 1.4
    ];
    
    $mult = $multiplicadores[strtolower($tamano)] ?? 1.0;
    return ceil($cantidad_base * $mult);
}
?>
