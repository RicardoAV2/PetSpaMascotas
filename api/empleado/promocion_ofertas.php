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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $servicioId = intval($_POST['servicio_id'] ?? 0);
    $productoId = intval($_POST['producto_id'] ?? 0);
    $valor = floatval($_POST['valor'] ?? 0);
    $precioOf = floatval($_POST['precio_oferta'] ?? 0);
    $cantidadRequerida = max(1, intval($_POST['cantidad_requerida'] ?? 1));
    $cantidadRegalo = max(0, intval($_POST['cantidad_regalo'] ?? 0));
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
    $fechaFin = trim($_POST['fecha_fin'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if ($nombre === '') {
        throw new Exception('El nombre de la promoción es obligatorio.');
    }

    $allowedTypes = [
        'descuento_servicio',
        'descuento_producto',
        'pack_servicio',
        'pack_producto',
        '2x1_servicio',
        '2x1_producto',
    ];
    if (!in_array($tipo, $allowedTypes, true)) {
        throw new Exception('Tipo de promoción inválido.');
    }

    if ($fechaInicio === '' || $fechaFin === '') {
        throw new Exception('La fecha de inicio y fin son obligatorias.');
    }

    $inicio = new DateTime($fechaInicio);
    $fin = new DateTime($fechaFin);
    if ($fin < $inicio) {
        throw new Exception('La fecha de fin debe ser igual o posterior a la fecha de inicio.');
    }

    if (str_ends_with($tipo, '_servicio') && $servicioId <= 0) {
        throw new Exception('Selecciona un servicio para esta promoción.');
    }
    if (str_ends_with($tipo, '_producto') && $productoId <= 0) {
        throw new Exception('Selecciona un producto para esta promoción.');
    }

    if (str_starts_with($tipo, 'descuento') && $valor <= 0) {
        throw new Exception('Debes indicar un valor de descuento en porcentaje.');
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'INSERT INTO promocion (nombre, descripcion, tipo, valor, cantidad_requerida, cantidad_regalo, precio_oferta, fecha_inicio, fecha_fin, activo) VALUES (:nombre, :descripcion, :tipo, :valor, :cantidad_requerida, :cantidad_regalo, :precio_oferta, :fecha_inicio, :fecha_fin, :activo)'
    );
    $stmt->execute([
        ':nombre' => $nombre,
        ':descripcion' => $descripcion,
        ':tipo' => $tipo,
        ':valor' => $valor,
        ':cantidad_requerida' => $cantidadRequerida,
        ':cantidad_regalo' => $cantidadRegalo,
        ':precio_oferta' => $precioOf > 0 ? $precioOf : null,
        ':fecha_inicio' => $inicio->format('Y-m-d'),
        ':fecha_fin' => $fin->format('Y-m-d'),
        ':activo' => $activo,
    ]);

    $promocionId = intval($conn->lastInsertId());

    if (str_ends_with($tipo, '_servicio')) {
        $stmt = $conn->prepare(
            'INSERT INTO promocion_servicio (id_promocion, id_servicio, cantidad, precio_oferta) VALUES (:promo, :servicio, :cantidad, :precio_oferta)'
        );
        $stmt->execute([
            ':promo' => $promocionId,
            ':servicio' => $servicioId,
            ':cantidad' => $cantidadRequerida,
            ':precio_oferta' => $precioOf > 0 ? $precioOf : null,
        ]);
    }

    if (str_ends_with($tipo, '_producto')) {
        $stmt = $conn->prepare(
            'INSERT INTO promocion_producto (id_promocion, id_producto) VALUES (:promo, :producto)'
        );
        $stmt->execute([
            ':promo' => $promocionId,
            ':producto' => $productoId,
        ]);
    }

    $conn->commit();
    Logger::log('crear_oferta_promocion', Auth::getCurrentUser()['id'], Auth::getCurrentUser()['rol'], "Oferta creada: {$nombre} ({$tipo})");
    header('Location: /petspa/public/empleado/promociones.php?success=1');
    exit();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: /petspa/public/empleado/promociones.php?error=' . urlencode($e->getMessage()));
    exit();
}
