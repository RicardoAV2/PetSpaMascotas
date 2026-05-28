<?php
/**
 * API para reservar productos en un servicio específico de una cita
 * POST /petspa/api/empleado/reservar_productos_servicio.php
 */

require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/Security.php";
require_once "helpers_grooming.php";

header('Content-Type: application/json');

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    $id_cita = isset($_POST['id_cita']) ? intval($_POST['id_cita']) : 0;
    $id_servicio = isset($_POST['id_servicio']) ? intval($_POST['id_servicio']) : 0;
    $tamano_mascota = isset($_POST['tamano_mascota']) ? trim($_POST['tamano_mascota']) : 'mediano';

    if ($id_cita <= 0 || $id_servicio <= 0) {
        $response['message'] = 'Parámetros inválidos';
        echo json_encode($response);
        exit();
    }

    // Verificar que la cita pertenece al groomer actual
    $currentUser = Auth::getCurrentUser();
    $stmt = $conn->prepare("
        SELECT 1 FROM cita 
        WHERE id_cita = ? AND id_groomer = ?
    ");
    $stmt->execute([$id_cita, $currentUser['id']]);
    
    if (!$stmt->fetch()) {
        $response['message'] = 'No tienes permiso para esta cita';
        echo json_encode($response);
        exit();
    }

    // Validar que la cita tiene este servicio
    $stmt = $conn->prepare("
        SELECT 1 FROM cita_servicio
        WHERE id_cita = ? AND id_servicio = ?
    ");
    $stmt->execute([$id_cita, $id_servicio]);
    
    if (!$stmt->fetch()) {
        $response['message'] = 'El servicio no está asignado a esta cita';
        echo json_encode($response);
        exit();
    }

    // Procesar productos
    $productos = isset($_POST['productos']) && is_array($_POST['productos']) ? $_POST['productos'] : [];
    $productosFiltered = [];

    foreach ($productos as $prodId => $data) {
        $id_prod = intval($prodId);
        if ($id_prod <= 0) continue;

        // Solo incluir si está checked
        if (isset($data['id_producto']) && $data['id_producto'] == $id_prod) {
            $cantidad = max(1, intval($data['cantidad'] ?? 1));
            $productosFiltered[] = [
                'id_producto' => $id_prod,
                'cantidad' => $cantidad
            ];
        }
    }

    // Reservar productos
    if (reservarProductosServicio($conn, $id_cita, $id_servicio, $productosFiltered, $tamano_mascota)) {
        $response['success'] = true;
        $response['message'] = 'Productos reservados correctamente';
    } else {
        $response['message'] = 'Error al reservar productos';
    }

} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
?>
