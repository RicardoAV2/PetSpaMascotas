<?php
/**
 * API PARA GESTIÓN DE CITAS DE CLIENTES
 * ====================================
 * Endpoint para crear, leer, actualizar y eliminar citas
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/Security.php';

Auth::setConnection($conn);
Logger::setConnection($conn);
Security::setConnection($conn);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Solo clientes pueden acceder
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$userId = $_SESSION['usuario_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener citas del cliente
            handleGetCitas($conn, $userId);
            break;

        case 'POST':
            // Crear nueva cita
            handleCreateCita($conn, $userId);
            break;

        case 'PUT':
            // Actualizar cita (cancelar)
            handleUpdateCita($conn, $userId);
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            break;
    }
} catch (Exception $e) {
    Logger::log('error', 'API Citas', 'Error en API de citas: ' . $e->getMessage(), $userId);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Obtener citas del cliente
 */
function handleGetCitas($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT c.id_cita, c.fecha, c.hora, c.estado, c.notas, c.fecha_creacion,
                   g.id_groomer, u.nombre as groomer_nombre, g.especialidad,
                   m.nombre as mascota_nombre, m.raza, m.especie,
                   c.calificacion, c.comentario
            FROM cita c
            JOIN groomer g ON c.id_groomer = g.id_groomer
            JOIN usuario u ON g.id_groomer = u.id_usuario
            JOIN mascota m ON c.id_mascota = m.id_mascota
            WHERE m.id_cliente_principal = ?
            ORDER BY c.fecha DESC, c.hora DESC
        ");
        $stmt->execute([$userId]);
        $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatear datos
        foreach ($citas as &$cita) {
            $cita['fecha_formateada'] = date('d/m/Y', strtotime($cita['fecha']));
            $cita['hora_formateada'] = date('H:i', strtotime($cita['hora']));
            $cita['fecha_creacion_formateada'] = date('d/m/Y H:i', strtotime($cita['fecha_creacion']));

            // Estado en español
            $estados = [
                'pendiente' => 'Pendiente',
                'confirmada' => 'Confirmada',
                'en_progreso' => 'En Progreso',
                'completada' => 'Completada',
                'cancelada' => 'Cancelada'
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

/**
 * Crear nueva cita
 */
function handleCreateCita($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }

    $groomerId = $input['groomer_id'] ?? null;
    $mascotaId = $input['mascota_id'] ?? null;
    $servicios = $input['servicios'] ?? [];
    $fecha = $input['fecha'] ?? null;
    $hora = $input['hora'] ?? null;
    $notas = $input['notas'] ?? '';

    // Validaciones
    if (!$groomerId || !$mascotaId || empty($servicios) || !$fecha || !$hora) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        return;
    }

    // Validar que la mascota pertenece al cliente
    try {
        $stmt = $conn->prepare("SELECT id_mascota FROM mascota WHERE id_mascota = ? AND id_cliente_principal = ?");
        $stmt->execute([$mascotaId, $userId]);
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

    // Validar que el groomer existe y está activo
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

    // Validar fecha y hora
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

    // Validar horario de atención (9:00 AM - 6:00 PM)
    $horaInt = (int)$fechaObj->format('H');
    if ($horaInt < 9 || $horaInt > 17) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Horario fuera del horario de atención (9:00 AM - 6:00 PM)']);
        return;
    }

    // Verificar que no haya conflicto de horario para el groomer
    try {
        $stmt = $conn->prepare("
            SELECT id_cita FROM cita
            WHERE id_groomer = ? AND fecha = ? AND hora = ? AND estado NOT IN ('cancelada', 'completada')
        ");
        $stmt->execute([$groomerId, $fecha, $hora]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'El groomer ya tiene una cita en ese horario']);
            return;
        }
    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error verificando conflictos: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error verificando disponibilidad']);
        return;
    }

    // Sanitizar notas
    $notas = Security::sanitizeInput($notas);

    // Convertir servicios a JSON
    $serviciosJson = json_encode($servicios);

    // Crear la cita
    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("
            INSERT INTO cita (id_groomer, id_mascota, fecha, hora, servicios, notas, estado, fecha_creacion)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NOW())
        ");
        $stmt->execute([$groomerId, $mascotaId, $fecha, $hora, $serviciosJson, $notas]);

        $citaId = $conn->lastInsertId();

        $conn->commit();

        // Log de la acción
        Logger::log('info', 'Cita Creada', "Nueva cita creada ID: $citaId para cliente $userId", $userId);

        echo json_encode([
            'success' => true,
            'message' => 'Cita agendada exitosamente',
            'cita_id' => $citaId
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        Logger::log('error', 'API Citas', 'Error creando cita: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al crear la cita']);
    }
}

/**
 * Actualizar cita (solo cancelar)
 */
function handleUpdateCita($conn, $userId) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['cita_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de cita requerido']);
        return;
    }

    $citaId = $input['cita_id'];
    $accion = $input['accion'] ?? 'cancelar';

    try {
        // Verificar que la cita pertenece al cliente
        $stmt = $conn->prepare("
            SELECT c.id_cita, c.estado, c.fecha, c.hora, m.nombre as mascota_nombre
            FROM cita c
            JOIN mascota m ON c.id_mascota = m.id_mascota
            WHERE c.id_cita = ? AND m.id_cliente_principal = ?
        ");
        $stmt->execute([$citaId, $userId]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
            return;
        }

        // Solo permitir cancelar citas pendientes o confirmadas
        if (!in_array($cita['estado'], ['pendiente', 'confirmada'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se puede cancelar esta cita']);
            return;
        }

        // Verificar que la cita no sea en menos de 24 horas
        $fechaCita = new DateTime($cita['fecha'] . ' ' . $cita['hora']);
        $ahora = new DateTime();
        $diferencia = $ahora->diff($fechaCita);

        if ($diferencia->days == 0 && $diferencia->h < 24) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No se puede cancelar con menos de 24 horas de anticipación']);
            return;
        }

        // Cancelar la cita
        $stmt = $conn->prepare("UPDATE cita SET estado = 'cancelada' WHERE id_cita = ?");
        $stmt->execute([$citaId]);

        Logger::log('info', 'Cita Cancelada', "Cita $citaId cancelada por cliente $userId", $userId);

        echo json_encode([
            'success' => true,
            'message' => 'Cita cancelada exitosamente'
        ]);

    } catch (Exception $e) {
        Logger::log('error', 'API Citas', 'Error cancelando cita: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al cancelar la cita']);
    }
}
?>