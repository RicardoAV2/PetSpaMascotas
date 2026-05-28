<?php
/**
 * API PARA OBTENER SERVICIOS DE UN GROOMER
 * ========================================
 * Retorna servicios que puede realizar un groomer específico
 */
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);

header('Content-Type: application/json');

$groomerId = intval($_GET['groomer_id'] ?? 0);

if (!$groomerId) {
    echo json_encode(['success' => false, 'message' => 'Groomer ID requerido']);
    exit;
}

// Verificar que el groomer existe
$stmt = $conn->prepare("SELECT id_groomer FROM groomer WHERE id_groomer = ? AND estado_activo = 1");
$stmt->execute([$groomerId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Groomer no disponible']);
    exit;
}

// Obtener servicios activos (inicialmente todos, puedes extender para servicios específicos por groomer)
try {
    $stmt = $conn->prepare("
        SELECT id_servicio, nombre, duracion_base_minutos, descripcion
        FROM servicio
        WHERE estado_activo = 1
        ORDER BY nombre
    ");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'servicios' => $servicios
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error obteniendo servicios']);
    exit;
}
?>
