<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";
require_once "../../core/Logger.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$action = trim($_REQUEST['action'] ?? '');
$id = intval($_REQUEST['id'] ?? 0);

function redirectBack($message = null, $type = 'success') {
    $url = '/petspa/public/empleado/recepcionista/citas.php';
    if ($message) {
        $url .= '?' . ($type === 'error' ? 'error=' : 'success=') . urlencode($message);
    }
    header('Location: ' . $url);
    exit();
}

function getDayIndex($dateString) {
    $dt = new DateTime($dateString);
    return intval($dt->format('w'));
}

function isGroomerBlocked($conn, $groomerId, $start, $end) {
    $stmt = $conn->prepare("SELECT 1 FROM bloqueo_agenda WHERE (id_groomer = :groomer OR id_groomer IS NULL) AND fecha_inicio <= :end AND fecha_fin >= :start LIMIT 1");
    $stmt->execute([':groomer' => $groomerId, ':start' => $start, ':end' => $end]);
    return (bool)$stmt->fetch();
}

function hasSchedule($conn, $groomerId, $start, $end) {
    $day = getDayIndex($start);
    $stmt = $conn->prepare("SELECT hora_inicio, hora_fin FROM disponibilidad WHERE id_groomer = :groomer AND dia_semana = :dia");
    $stmt->execute([':groomer' => $groomerId, ':dia' => $day]);
    $intervals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!$intervals) {
        return false;
    }

    $startDate = new DateTime($start);
    $endDate = new DateTime($end);

    foreach ($intervals as $interval) {
        $intervalStart = new DateTime(substr($start, 0, 10) . ' ' . $interval['hora_inicio']);
        $intervalEnd = new DateTime(substr($start, 0, 10) . ' ' . $interval['hora_fin']);
        if ($startDate >= $intervalStart && $endDate <= $intervalEnd) {
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
    if ($excludeId) {
        $params[':exclude'] = $excludeId;
    }
    $stmt->execute($params);
    return (bool)$stmt->fetch();
}

try {
    if (!$id) {
        throw new Exception('ID de cita inválido.');
    }

    if ($action === 'confirmar') {
        $stmt = $conn->prepare("UPDATE cita SET estado = 'confirmada' WHERE id_cita = :id AND estado IN ('pendiente', 'agendada')");
        $stmt->execute([':id' => $id]);
        Logger::log('info', 'Cita Confirmada', "Cita ID: $id confirmada por {$currentUser['id']}", $currentUser['id']);
        redirectBack('La cita ha sido confirmada.');
    }

    if ($action === 'cancelar') {
        $motivo = trim($_REQUEST['motivo'] ?? 'Cancelada por recepcion');
        $stmt = $conn->prepare("UPDATE cita SET estado = 'cancelada', motivo_cancelacion = :motivo WHERE id_cita = :id AND estado NOT IN ('cancelada', 'completada')");
        $stmt->execute([':motivo' => $motivo, ':id' => $id]);
        Logger::log('info', 'Cita Cancelada', "Cita ID: $id cancelada por {$currentUser['id']}", $currentUser['id']);
        redirectBack('La cita ha sido cancelada.');
    }

    if ($action === 'reprogramar') {
        $fecha = trim($_REQUEST['fecha'] ?? '');
        $hora = trim($_REQUEST['hora'] ?? '');
        $groomerId = intval($_REQUEST['groomer_id'] ?? 0);
        if (!$fecha || !$hora) {
            throw new Exception('Fecha y hora son obligatorias para reprogramar.');
        }

        $stmt = $conn->prepare("SELECT id_groomer, duracion_real FROM cita WHERE id_cita = :id");
        $stmt->execute([':id' => $id]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cita) {
            throw new Exception('Cita no encontrada.');
        }

        $targetGroomer = $groomerId ?: intval($cita['id_groomer']);
        $duracion = intval($cita['duracion_real'] ?: 60);
        $inicio = $fecha . ' ' . $hora . ':00';
        $fin = (new DateTime($inicio))->modify("+{$duracion} minutes")->format('Y-m-d H:i:s');

        if (new DateTime($inicio) <= new DateTime()) {
            throw new Exception('La fecha de reprogramación debe ser futura.');
        }

        if (!hasSchedule($conn, $targetGroomer, $inicio, $fin)) {
            throw new Exception('El groomer no trabaja en ese horario.');
        }
        if (isGroomerBlocked($conn, $targetGroomer, $inicio, $fin)) {
            throw new Exception('El groomer tiene un bloqueo en ese horario.');
        }
        if (hasConflict($conn, $targetGroomer, $inicio, $fin, $id)) {
            throw new Exception('Ya existen citas en ese horario.');
        }

        $stmt = $conn->prepare("UPDATE cita SET fecha_inicio = :inicio, fecha_fin = :fin, id_groomer = :groomer, fecha_reprogramacion = NOW(), usuario_reprogramo = :usuario WHERE id_cita = :id");
        $stmt->execute([':inicio' => $inicio, ':fin' => $fin, ':groomer' => $targetGroomer, ':usuario' => $currentUser['id'], ':id' => $id]);
        Logger::log('info', 'Cita Reprogramada', "Cita ID: $id reprogramada por {$currentUser['id']}", $currentUser['id']);
        redirectBack('La cita ha sido reprogramada.');
    }

    throw new Exception('Acción no válida.');
} catch (Exception $e) {
    Logger::log('error', 'API Citas Empleado', 'Error en operación de cita: ' . $e->getMessage(), $currentUser['id']);
    redirectBack($e->getMessage(), 'error');
}
