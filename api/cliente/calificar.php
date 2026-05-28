<?php
/**
 * API PARA CALIFICAR CITAS
 * ========================
 * Endpoint para que clientes califiquen citas completadas
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
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Autenticación/rol: devolver JSON en lugar de redirecciones para APIs
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

if (empty($_SESSION['rol']) || $_SESSION['rol'] !== ROLE_CLIENTE) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo clientes pueden calificar.']);
    exit;
}

Middleware::checkSessionTimeout();

$userId = $_SESSION['usuario_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        return;
    }

    $citaId = $input['cita_id'] ?? null;
    $calificacion = $input['calificacion'] ?? null;
    $comentario = $input['comentario'] ?? '';

    // Validaciones
    if (!$citaId || !$calificacion) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
        return;
    }

    // Validar calificación (1-5)
    $calificacion = (int)$calificacion;
    if ($calificacion < 1 || $calificacion > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'La calificación debe estar entre 1 y 5']);
        return;
    }

    // Verificar que la cita existe y pertenece al cliente
    try {
        $stmt = $conn->prepare("
            SELECT c.id_cita, c.estado, c.id_groomer, m.nombre as mascota_nombre
            FROM cita c
            JOIN mascota m ON c.id_mascota = m.id_mascota
            LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota AND md.id_cliente = :cliente
            WHERE c.id_cita = :cita
              AND (m.id_cliente_principal = :cliente OR md.id_cliente = :cliente)
            LIMIT 1
        ");
        $stmt->execute([':cita' => $citaId, ':cliente' => $userId]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cita) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Cita no encontrada']);
            return;
        }

        // Verificar que la cita esté completada
        if ($cita['estado'] !== 'completada') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Solo se pueden calificar citas completadas']);
            return;
        }

        // Verificar que no haya sido calificada antes
        $stmt = $conn->prepare("SELECT id_calificacion FROM calificacion WHERE id_cita = ?");
        $stmt->execute([$citaId]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Esta cita ya ha sido calificada']);
            return;
        }

    } catch (Exception $e) {
        Logger::log('error', 'API Calificar', 'Error verificando cita: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de validación']);
        return;
    }

    // Sanitizar comentario
    $comentario = Security::sanitizeInput($comentario);

    // Insertar calificación en tabla calificacion (no en cita)
    try {
        $stmt = $conn->prepare("
            INSERT INTO calificacion (puntuacion, comentario, id_cliente, id_groomer, id_cita, fecha)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$calificacion, $comentario, $cita['id_cliente'], $cita['id_groomer'], $citaId]);

        // Log de la acción
        Logger::log('info', 'Cita Calificada', "Cita $citaId calificada con $calificacion estrellas por cliente $userId", $userId);

        echo json_encode([
            'success' => true,
            'message' => 'Calificación enviada exitosamente'
        ]);

    } catch (Exception $e) {
        Logger::log('error', 'API Calificar', 'Error guardando calificación: ' . $e->getMessage(), $userId);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar la calificación']);
    }

} catch (Exception $e) {
    Logger::log('error', 'API Calificar', 'Error general: ' . $e->getMessage(), $userId);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}
?>