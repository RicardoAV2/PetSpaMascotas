<?php
require_once "../../config/database.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/Logger.php";
require_once "../../core/helpers.php";

session_start();
Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');

function getDayWeek($date) {
    $dt = new DateTime($date);
    $dow = intval($dt->format('w')); // 0=domingo
    return $dow;
}

function isGroomerBlocked($conn, $groomerId, $start, $end) {
    $stmt = $conn->prepare("SELECT 1 FROM bloqueo_agenda WHERE (id_groomer = :groomer OR id_groomer IS NULL) AND fecha_inicio <= :end AND fecha_fin >= :start LIMIT 1");
    $stmt->execute([':groomer' => $groomerId, ':start' => $start, ':end' => $end]);
    return (bool) $stmt->fetch();
}

function hasSchedule($conn, $groomerId, $start, $end) {

    $day = getDayWeek($start);

    $stmt = $conn->prepare("
        SELECT hora_inicio, hora_fin
        FROM disponibilidad
        WHERE id_groomer = :groomer
        AND dia_semana = :dia
    ");

    $stmt->execute([
        ':groomer' => $groomerId,
        ':dia' => $day
    ]);

    $intervals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $startDate = new DateTime($start);
    $endDate = new DateTime($end);

    foreach ($intervals as $interval) {

        $intervalStart = new DateTime(
            substr($start,0,10) . ' ' . $interval['hora_inicio']
        );

        $intervalEnd = new DateTime(
            substr($start,0,10) . ' ' . $interval['hora_fin']
        );

        if (
            $startDate >= $intervalStart &&
            $endDate <= $intervalEnd
        ) {
            return true;
        }
    }

    return false;
}

function hasConflict($conn, $groomerId, $start, $end, $excludeId = null) {
    $sql = "SELECT 1 FROM cita WHERE id_groomer = :groomer AND estado NOT IN ('cancelada', 'completada') AND ((fecha_inicio <= :start AND fecha_fin > :start) OR (fecha_inicio < :end AND fecha_fin >= :end))";
    if ($excludeId) {
        $sql .= " AND id_cita <> :exclude";
    }
    $stmt = $conn->prepare($sql);
    $params = [':groomer' => $groomerId, ':start' => $start, ':end' => $end];
    if ($excludeId) $params[':exclude'] = $excludeId;
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

function getServiceDuration($conn, $servicioIds, $mascotaId) {
    if (is_array($servicioIds)) {
        return calculateTotalDurationForServices($conn, $servicioIds, $mascotaId);
    }

    $stmt = $conn->prepare("SELECT duracion_base_minutos FROM servicio WHERE id_servicio = ?");
    $stmt->execute([$servicioIds]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    $multiplier = 1.0;
    if ($mascotaId) {
        $stmt = $conn->prepare("SELECT peso, tamano FROM mascota WHERE id_mascota = ?");
        $stmt->execute([$mascotaId]);
        $mascota = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mascota) {
            $peso = floatval($mascota['peso'] ?? 0);
            $tamano = strtolower(trim($mascota['tamano'] ?? ''));
            if ($tamano === 'grande' || $peso >= 20) $multiplier = 1.15;
            elseif ($tamano === 'mediano' || ($peso >= 10 && $peso < 20)) $multiplier = 1.1;
            elseif ($tamano === 'gigante' || $peso >= 35) $multiplier = 1.3;
        }
    }

    return intval(max(15, ceil(($servicio['duracion_base_minutos'] ?? 30) * $multiplier + 10)));
}

function serviceAllowsDoubleBooking($conn, $servicioIds) {
    if (!is_array($servicioIds)) {
        $servicioIds = [$servicioIds];
    }

    $placeholders = implode(',', array_fill(0, count($servicioIds), '?'));
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM servicio WHERE id_servicio IN ($placeholders) AND permite_doble_booking = 1");
    $stmt->execute($servicioIds);
    $allowedCount = intval($stmt->fetchColumn());
    return $allowedCount === count($servicioIds);
}

function countAppointmentsForDay($conn, $groomerId, $fecha) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cita WHERE id_groomer = :groomer AND DATE(fecha_inicio) = :fecha AND estado NOT IN ('cancelada', 'completada')");
    $stmt->execute([':groomer' => $groomerId, ':fecha' => $fecha]);
    return intval($stmt->fetchColumn());
}

function countAppointmentsForDayExcluding($conn, $groomerId, $fecha, $excludeId = null) {
    $sql = "SELECT COUNT(*) AS total FROM cita WHERE id_groomer = :groomer AND DATE(fecha_inicio) = :fecha AND estado NOT IN ('cancelada', 'completada')";
    if ($excludeId) {
        $sql .= " AND id_cita <> :exclude";
    }
    $stmt = $conn->prepare($sql);
    $params = [':groomer' => $groomerId, ':fecha' => $fecha];
    if ($excludeId) $params[':exclude'] = $excludeId;
    $stmt->execute($params);
    return intval($stmt->fetchColumn());
}

try {
    if ($action === 'crear_bloqueo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $groomerId = intval($_POST['groomer_id'] ?? 0) ?: null;
        $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
        $fechaFin = trim($_POST['fecha_fin'] ?? '');
        $motivo = trim($_POST['motivo'] ?? '');
        $tipo = trim($_POST['tipo'] ?? 'ausencia');

        if (!$fechaInicio || !$fechaFin || new DateTime($fechaInicio) > new DateTime($fechaFin)) {
            throw new Exception('Rango de fechas inválido.');
        }

        $stmt = $conn->prepare("INSERT INTO bloqueo_agenda (id_groomer, fecha_inicio, fecha_fin, motivo, tipo_bloqueo) VALUES (:groomer, :inicio, :fin, :motivo, :tipo)");
        $stmt->execute([':groomer' => $groomerId, ':inicio' => $fechaInicio, ':fin' => $fechaFin, ':motivo' => $motivo, ':tipo' => $tipo]);
        Logger::log('crear_usuario', $currentUser['id'], $currentUser['rol'], "Bloqueo de agenda creado para groomer {$groomerId}");
    }

    if ($action === 'reprogramar_cita' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $citaId = intval($_POST['cita_id'] ?? 0);
        $groomerId = intval($_POST['groomer_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? '');
        $hora = trim($_POST['hora'] ?? '');

        if (!$citaId || !$groomerId || !$fecha || !$hora) {
            throw new Exception('Datos incompletos para reprogramar.');
        }

        $fechaInicio = $fecha . ' ' . $hora . ':00';
        $stmt = $conn->prepare("SELECT c.id_cita, c.id_servicio, c.duracion_real FROM cita c WHERE c.id_cita = ?");
        $stmt->execute([$citaId]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cita) {
            throw new Exception('Cita no encontrada.');
        }

        $duracion = intval($cita['duracion_real'] ?: 60);
        $fechaFin = (new DateTime($fechaInicio))->modify("+{$duracion} minutes")->format('Y-m-d H:i:s');

        if (new DateTime($fechaInicio) <= new DateTime()) {
            throw new Exception('La nueva fecha debe ser futura.');
        }

        if (isGroomerBlocked($conn, $groomerId, $fechaInicio, $fechaFin)) {
            throw new Exception('El groomer tiene un bloqueo en ese horario.');
        }

        if (!hasSchedule($conn, $groomerId, $fechaInicio, $fechaFin)) {
            throw new Exception('El groomer no trabaja en ese horario.');
        }

        // Contar citas en el día excluyendo la cita que se está reprogramando
        if (countAppointmentsForDayExcluding($conn, $groomerId, substr($fechaInicio, 0, 10), $citaId) >= 4) {
            throw new Exception('El groomer ya tiene la capacidad máxima el día seleccionado.');
        }

        if (hasConflict($conn, $groomerId, $fechaInicio, $fechaFin, $citaId)) {
            throw new Exception('Ya existe una cita en ese horario.');
        }

        $stmt = $conn->prepare("UPDATE cita SET fecha_inicio = :inicio, fecha_fin = :fin, fecha_reprogramacion = NOW(), usuario_reprogramo = :usuario WHERE id_cita = :id");
        $stmt->execute([':inicio' => $fechaInicio, ':fin' => $fechaFin, ':usuario' => $currentUser['id'], ':id' => $citaId]);
        Logger::log('editar_usuario', $currentUser['id'], $currentUser['rol'], "Cita reprogramada ID: $citaId");
    }

    if ($action === 'crear_cita' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $mascotaId = intval($_POST['mascota_id'] ?? 0);
        $servicioIds = [];
        if (!empty($_POST['servicio_ids']) && is_array($_POST['servicio_ids'])) {
            $servicioIds = array_map('intval', $_POST['servicio_ids']);
        } elseif (!empty($_POST['servicio_id'])) {
            $servicioIds = [intval($_POST['servicio_id'])];
        }
        $servicioIds = array_filter($servicioIds, fn($id) => $id > 0);
        $groomerId = intval($_POST['groomer_id'] ?? 0);
        $fecha = trim($_POST['fecha'] ?? '');
        $hora = trim($_POST['hora'] ?? '');
        $nota = trim($_POST['nota'] ?? '');

        if (!$mascotaId || empty($servicioIds) || !$groomerId || !$fecha || !$hora) {
            throw new Exception('Datos incompletos para crear la cita.');
        }

        $placeholders = implode(',', array_fill(0, count($servicioIds), '?'));
        $stmt = $conn->prepare("SELECT id_servicio FROM servicio WHERE id_servicio IN ($placeholders) AND estado_activo = 1");
        $stmt->execute($servicioIds);
        if (count($stmt->fetchAll(PDO::FETCH_COLUMN)) !== count($servicioIds)) {
            throw new Exception('Alguno de los servicios seleccionados no está disponible.');
        }

        $fechaInicio = $fecha . ' ' . $hora . ':00';
        $duracion = getServiceDuration($conn, $servicioIds, $mascotaId);
        $fechaFin = (new DateTime($fechaInicio))->modify("+{$duracion} minutes")->format('Y-m-d H:i:s');

        if (new DateTime($fechaInicio) <= new DateTime()) {
            throw new Exception('La fecha de la cita debe ser futura.');
        }

        if (isGroomerBlocked($conn, $groomerId, $fechaInicio, $fechaFin)) {
            throw new Exception('El groomer tiene un bloqueo en ese horario.');
        }

        if (!hasSchedule($conn, $groomerId, $fechaInicio, $fechaFin)) {
            throw new Exception('El groomer no trabaja en ese horario.');
        }

        $capacidad = $conn->prepare("SELECT capacidad_simultanea FROM groomer WHERE id_groomer = ?");
        $capacidad->execute([$groomerId]);
        $capacidadDiaria = intval($capacidad->fetchColumn() ?: 1) * 8;
        if (countAppointmentsForDay($conn, $groomerId, substr($fechaInicio, 0, 10)) >= $capacidadDiaria) {
            throw new Exception('El groomer ya tiene la capacidad máxima el día seleccionado.');
        }

        if (hasConflict($conn, $groomerId, $fechaInicio, $fechaFin) && !serviceAllowsDoubleBooking($conn, $servicioIds)) {
            throw new Exception('Ya existe una cita en ese horario.');
        }

        $stmt = $conn->prepare("INSERT INTO cita (fecha_inicio, fecha_fin, duracion_real, estado, creado_por, fecha_creacion, id_mascota, id_groomer, id_servicio, nota) VALUES (:inicio, :fin, :duracion, 'agendada', :usuario, NOW(), :mascota, :groomer, :servicio, :nota)");
        $stmt->execute([
            ':inicio' => $fechaInicio,
            ':fin' => $fechaFin,
            ':duracion' => $duracion,
            ':usuario' => $currentUser['id'],
            ':mascota' => $mascotaId,
            ':groomer' => $groomerId,
            ':servicio' => $servicioIds[0],
            ':nota' => $nota
        ]);
        $citaId = intval($conn->lastInsertId());

        $stmt = $conn->prepare("INSERT INTO cita_servicio (id_cita, id_servicio) VALUES (:cita, :servicio)");
        foreach ($servicioIds as $servicioId) {
            $stmt->execute([':cita' => $citaId, ':servicio' => $servicioId]);
        }

        reserveInventoryForServices($conn, $citaId, $servicioIds, $currentUser['id']);
        Logger::log('crear_usuario', $currentUser['id'], $currentUser['rol'], "Cita creada ID: {$citaId}");
    }

    if ($action === 'delete_bloqueo' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM bloqueo_agenda WHERE id_bloqueo = :id");
        $stmt->execute([':id' => $id]);
        Logger::log('editar_usuario', $currentUser['id'], $currentUser['rol'], "Bloqueo de agenda eliminado ID: $id");
    }
} catch (Exception $e) {
    $error = urlencode($e->getMessage());
    header('Location: /petspa/public/empleado/agenda.php?error=' . $error);
    exit();
}

header('Location: /petspa/public/empleado/agenda.php?success=1');
exit();
