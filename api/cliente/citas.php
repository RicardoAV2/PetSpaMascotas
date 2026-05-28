<?php
/**
 * API PARA GESTIÓN DE CITAS DE CLIENTES
 * ====================================
 * Endpoint para crear, leer, actualizar y eliminar citas
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);
//Logger::setConnection($conn);
//Security::setConnection($conn);

header('Content-Type: application/json');
//
ini_set('display_errors',1);
error_reporting(E_ALL);
//
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$userId = Auth::getCurrentUser()['id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetCitas($conn, $userId);
            break;
        case 'POST':
            handleCreateCita($conn, $userId);
            break;
        case 'PUT':
            handleUpdateCita($conn, $userId);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {

    http_response_code(500);

    echo json_encode([
        'success'=>false,
        'message'=>$e->getMessage()
    ]);

}/*catch (Exception $e) {
    Logger::log('error', 'API Citas', 'Error en API de citas: ' . $e->getMessage(), $userId);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}*/

function handleGetCitas($conn, $userId) {
    try {
        $stmt = $conn->prepare("SELECT c.id_cita, c.fecha_inicio, c.fecha_fin, c.estado, c.nota, c.fecha_creacion,
                   g.id_groomer, u.nombre AS groomer_nombre, g.especialidad,
                   m.nombre AS mascota_nombre, m.raza, m.especie,
                   c.duracion_real,
                   cal.puntuacion AS calificacion,
                   cal.comentario AS comentario
            FROM cita c
            JOIN groomer g ON c.id_groomer = g.id_groomer
            JOIN usuario u ON g.id_groomer = u.id_usuario
            JOIN mascota m ON c.id_mascota = m.id_mascota
            LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota AND md.id_cliente = ?
            LEFT JOIN calificacion cal ON cal.id_cita = c.id_cita
            WHERE (m.id_cliente_principal = ? OR md.id_cliente = ?)
            ORDER BY c.fecha_inicio DESC");
        $stmt->execute([$userId, $userId, $userId]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($citas as &$cita) {
            $stmtServicios = $conn->prepare("SELECT s.id_servicio, s.nombre, s.precio_base FROM cita_servicio cs JOIN servicio s ON cs.id_servicio = s.id_servicio WHERE cs.id_cita = ? ORDER BY s.nombre");
            $stmtServicios->execute([$cita['id_cita']]);
            $servicios = $stmtServicios->fetchAll(PDO::FETCH_ASSOC);
            $cita['servicios'] = json_encode(array_map(function($s) {
                return $s['nombre'];
            }, $servicios));
            $cita['servicios_detalle'] = $servicios;
            $cita['fecha_formateada'] = date('d/m/Y', strtotime($cita['fecha_inicio']));
            $cita['hora_inicio_formateada'] = date('H:i', strtotime($cita['fecha_inicio']));
            $cita['hora_fin_formateada'] = date('H:i', strtotime($cita['fecha_fin']));
            $cita['fecha_creacion_formateada'] = date('d/m/Y H:i', strtotime($cita['fecha_creacion']));
            $cita['calificacion'] = isset($cita['calificacion']) ? intval($cita['calificacion']) : null;
            $cita['comentario'] = $cita['comentario'] ?? null;
            $estados = [
                'pendiente' => 'Pendiente',
                'agendada' => 'Agendada',
                'confirmada' => 'Confirmada',
                'en_progreso' => 'En progreso',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada',
                'no_asistio' => 'No asistió'
            ];
            $cita['estado_texto'] = $estados[$cita['estado']] ?? $cita['estado'];
        }

        echo json_encode(['success' => true, 'citas' => $citas]);
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error obteniendo citas: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al obtener citas']);
    }
}

function handleCreateCita($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }

    $groomerId = intval($input['groomer_id'] ?? 0);
    $mascotaId = intval($input['mascota_id'] ?? 0);
    $servicioIds = [];
    if (!empty($input['servicio_ids']) && is_array($input['servicio_ids'])) {
        $servicioIds = array_map('intval', $input['servicio_ids']);
    } elseif (!empty($input['servicio_id'])) {
        $servicioIds = [intval($input['servicio_id'])];
    }
    $fecha = $input['fecha'] ?? null;
    $hora = $input['hora'] ?? null;
    $notas = trim($input['nota'] ?? $input['notas'] ?? '');

    $servicioIds = array_filter($servicioIds, fn($id) => $id > 0);
    if (!$groomerId || !$mascotaId || empty($servicioIds) || !$fecha || !$hora) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        return;
    }

    try {
        $stmt = $conn->prepare(
            "SELECT 1 FROM mascota m
             LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
             WHERE m.id_mascota = ?
               AND (m.id_cliente_principal = ? OR md.id_cliente = ?)
             LIMIT 1"
        );
        $stmt->execute([$mascotaId, $userId, $userId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Mascota no encontrada o no pertenece al usuario']);
            return;
        }
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error validando mascota: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de validación']);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT id_groomer FROM groomer WHERE id_groomer = ? AND estado_activo = 1");
        $stmt->execute([$groomerId]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Groomer no encontrado o inactivo']);
            return;
        }
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error validando groomer: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de validación']);
        return;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($servicioIds), '?'));
        $stmt = $conn->prepare("SELECT id_servicio, duracion_base_minutos FROM servicio WHERE id_servicio IN ($placeholders) AND estado_activo = 1");
        $stmt->execute($servicioIds);
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($servicios) !== count($servicioIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Alguno de los servicios seleccionados es inválido o está inactivo']);
            return;
        }
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error validando servicio: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de validación de servicio']);
        return;
    }

    $fechaCompleta = $fecha . ' ' . $hora . ':00';
    $fechaObj = DateTime::createFromFormat('Y-m-d H:i:s', $fechaCompleta);
    if (!$fechaObj) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Formato de fecha/hora inválido']);
        return;
    }

    $ahora = new DateTime();
    if ($fechaObj <= $ahora) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La cita debe ser en el futuro']);
        return;
    }

    $horaInt = (int)$fechaObj->format('H');
    if ($horaInt < 9 || $horaInt > 17) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Horario fuera del horario de atención (9:00 - 18:00)']);
        return;
    }

    $duracionMinutos = calculateTotalDurationForServices($conn, $servicioIds, $mascotaId);
    if (!isScheduleAvailable($conn, $groomerId, $fecha, $hora, $duracionMinutos, $mascotaId)) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'El groomer no está disponible en el horario seleccionado']);
        return;
    }

    $fechaFin = clone $fechaObj;
    $fechaFin->modify("+{$duracionMinutos} minutes");
    $fechaInicioStr = $fechaObj->format('Y-m-d H:i:s');
    $fechaFinStr = $fechaFin->format('Y-m-d H:i:s');

    try {
        $stmt = $conn->prepare("SELECT id_cita FROM cita WHERE id_groomer = ? AND ((fecha_inicio <= ? AND fecha_fin > ?) OR (fecha_inicio < ? AND fecha_fin >= ?)) AND estado NOT IN ('cancelada', 'completada')");
        $stmt->execute([$groomerId, $fechaInicioStr, $fechaInicioStr, $fechaFinStr, $fechaFinStr]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El groomer ya tiene una cita en ese rango horario']);
            return;
        }
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error verificando conflicto de horario: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error verificando disponibilidad']);
        return;
    }

    $notas = Security::sanitizeInput($notas);

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("INSERT INTO cita (fecha_inicio, fecha_fin, duracion_real, estado, creado_por, fecha_creacion, id_mascota, id_groomer, id_servicio, nota) VALUES (?, ?, ?, 'pendiente', ?, NOW(), ?, ?, ?, ?)");
        $stmt->execute([$fechaInicioStr, $fechaFinStr, $duracionMinutos, $userId, $mascotaId, $groomerId, $servicioIds[0], $notas]);
        $citaId = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO cita_servicio (id_cita, id_servicio) VALUES (?, ?)");
        foreach ($servicioIds as $servicioId) {
            $stmt->execute([$citaId, $servicioId]);
        }

        reserveInventoryForServices($conn, $citaId, $servicioIds, $userId);
        $conn->commit();
        Logger::log('info', 'Cita Creada', "Nueva cita creada ID: $citaId para cliente $userId", $userId);
        // Programar recordatorios (24h y 2h antes) si el cliente tiene email
        try {
            $fechaInicio = new DateTime($fechaInicioStr);
            $clienteEmail = Auth::getCurrentUser()['email'] ?? null;
            if ($clienteEmail) {
                $prog24 = (clone $fechaInicio)->modify('-24 hours')->format('Y-m-d H:i:s');
                $prog2 = (clone $fechaInicio)->modify('-2 hours')->format('Y-m-d H:i:s');
                $msg24 = "Recordatorio: tienes una cita el " . $fechaInicio->format('d/m/Y H:i') . ".";
                $msg2 = "Recordatorio: tu cita es en 2 horas (" . $fechaInicio->format('d/m/Y H:i') . ").";
                $stmt = $conn->prepare("INSERT INTO notificacion (tipo_evento, canal, mensaje, destino, fecha_programacion, estado_envio, id_cliente, id_cita) VALUES (?, 'email', ?, ?, ?, 'pendiente', ?, ?)");
                $stmt->execute(['recordatorio_24h', $msg24, $clienteEmail, $prog24, $userId, $citaId]);
                $stmt->execute(['recordatorio_2h', $msg2, $clienteEmail, $prog2, $userId, $citaId]);
            }
        } catch (Exception $e) {
            // no bloquear creación de cita por fallos en notificaciones
            error_log('No se pudieron programar recordatorios: ' . $e->getMessage());
        }
        echo json_encode(['success' => true, 'message' => 'Cita agendada exitosamente', 'cita_id' => $citaId]);
    } catch (Exception $e) {
        $conn->rollBack();

        echo json_encode([
            'success'=>false,
            'message'=>$e->getMessage()
        ]);
    }
}
function isBreakTime($interval,$current,$slotEnd){

    if(empty($interval['intervalo_descanso'])){
        return false;
    }

    $descanso=json_decode(
        $interval['intervalo_descanso'],
        true
    );

    if(!$descanso){
        return false;
    }

    $fecha=$current->format('Y-m-d');

    $inicio=new DateTime(
        "$fecha ".$descanso['inicio']
    );

    $fin=new DateTime(
        "$fecha ".$descanso['fin']
    );

    return (
        $current < $fin &&
        $slotEnd > $inicio
    );
}

function getCapacity($conn, $groomerId) {
    $stmt = $conn->prepare("SELECT capacidad_simultanea FROM groomer WHERE id_groomer = ?");
    $stmt->execute([$groomerId]);
    return intval($stmt->fetchColumn() ?: 1);
}

function isScheduleAvailable($conn, $groomerId, $fecha, $hora, $duracionMinutos, $mascotaId) {
    $fechaCompleta = DateTime::createFromFormat('Y-m-d H:i:s', "$fecha $hora:00");
    if (!$fechaCompleta) {
        return false;
    }

    $fechaFin = clone $fechaCompleta;
    $fechaFin->modify("+{$duracionMinutos} minutes");

    $diaSemana = intval($fechaCompleta->format('w'));
    $stmt = $conn->prepare(
    "SELECT hora_inicio,
            hora_fin,
            intervalo_descanso
    FROM disponibilidad
    WHERE id_groomer=? 
    AND dia_semana=?"
    );    
    $stmt->execute([$groomerId, $diaSemana]);
    $intervals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($intervals)) {
        return false;
    }

    $isWithinSchedule = false;

    foreach ($intervals as $interval) {
        $inicioIntervalo = new DateTime(
            "$fecha {$interval['hora_inicio']}"
        );

        $finIntervalo = new DateTime(
            "$fecha {$interval['hora_fin']}"
        );

        if(
            $fechaCompleta >= $inicioIntervalo &&
            $fechaFin <= $finIntervalo &&
            !isBreakTime(
                $interval,
                $fechaCompleta,
                $fechaFin
            )
        ){
            $isWithinSchedule=true;
            break;
        }
    }

    if(!$isWithinSchedule){
        return false;
    }

    $stmt = $conn->prepare("SELECT 1 FROM bloqueo_agenda WHERE (id_groomer = :groomer OR id_groomer IS NULL) AND fecha_inicio <= :end AND fecha_fin >= :start LIMIT 1");
    $stmt->execute([':groomer' => $groomerId, ':start' => $fechaCompleta->format('Y-m-d H:i:s'), ':end' => $fechaFin->format('Y-m-d H:i:s')]);
    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cita WHERE id_groomer = ? AND estado NOT IN ('cancelada', 'completada') AND ((fecha_inicio <= ? AND fecha_fin > ?) OR (fecha_inicio < ? AND fecha_fin >= ?))");
    $stmt->execute([$groomerId, $fechaCompleta->format('Y-m-d H:i:s'), $fechaCompleta->format('Y-m-d H:i:s'), $fechaFin->format('Y-m-d H:i:s'), $fechaFin->format('Y-m-d H:i:s')]);
    $existing = intval($stmt->fetchColumn() ?: 0);
    if ($existing >= getCapacity($conn, $groomerId)) {
        return false;
    }

    $stmt = $conn->prepare("SELECT capacidad_simultanea FROM groomer WHERE id_groomer = ?");
    $stmt->execute([$groomerId]);
    $capacidad = intval($stmt->fetchColumn() ?: 1);

    $stmt = $conn->prepare("SELECT COUNT(*) FROM cita WHERE id_groomer = ? AND DATE(fecha_inicio) = ? AND estado NOT IN ('cancelada','completada')");
    $stmt->execute([$groomerId, $fecha]);
    $total = intval($stmt->fetchColumn());

    if ($total >= ($capacidad * 8)) {
        return false;
    }

    return true;
}

function handleUpdateCita($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['cita_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de cita requerido']);
        return;
    }

    $citaId = intval($input['cita_id']);
    $motivo = trim($input['motivo'] ?? '');

    try {
        $stmt = $conn->prepare(
            "SELECT c.id_cita FROM cita c
             JOIN mascota m ON c.id_mascota = m.id_mascota
             LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota AND md.id_cliente = ?
             WHERE c.id_cita = ?
               AND (m.id_cliente_principal = ? OR md.id_cliente = ?)
               AND c.estado NOT IN ('cancelada', 'completada')"
        );
        $stmt->execute([$userId, $citaId, $userId, $userId]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Cita no encontrada o no modificable']);
            return;
        }

        $stmt = $conn->prepare("UPDATE cita SET estado = 'cancelada', motivo_cancelacion = ?, fecha_reprogramacion = NULL WHERE id_cita = ?");
        $stmt->execute([$motivo, $citaId]);

        Logger::log('info', 'Cita Cancelada', "Cita ID: $citaId cancelada por cliente $userId", $userId);
        echo json_encode(['success' => true, 'message' => 'Cita cancelada correctamente']);
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error cancelando cita: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cancelar la cita']);
    }
}
