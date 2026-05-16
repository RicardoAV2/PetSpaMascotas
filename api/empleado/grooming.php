<?php
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);

$id_cita = isset($_POST['id_cita']) ? intval($_POST['id_cita']) : 0;
$obs = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
$estado = isset($_POST['estado_mascota']) ? trim($_POST['estado_mascota']) : '';

if ($id_cita <= 0) {
    header('Location: /petspa/public/empleado/groomer/agenda.php?error=invalid_cita');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT id_cita FROM cita WHERE id_cita = ? AND id_groomer = (SELECT id_groomer FROM groomer WHERE id_usuario = ?)");
    $stmt->execute([$id_cita, Auth::getCurrentUser()['id']]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        header('Location: /petspa/public/empleado/groomer/agenda.php?error=not_allowed');
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO ficha_grooming (id_cita, observaciones, estado_mascota) VALUES (:cita, :obs, :estado)");
    $stmt->bindParam(":cita", $id_cita, PDO::PARAM_INT);
    $stmt->bindParam(":obs", $obs);
    $stmt->bindParam(":estado", $estado);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE cita SET estado='finalizado' WHERE id_cita = :id");
    $stmt->bindParam(":id", $id_cita, PDO::PARAM_INT);
    $stmt->execute();

    header('Location: /petspa/public/empleado/groomer/agenda.php?success=1');
    exit();
} catch (Exception $e) {
    error_log('Error en API grooming: ' . $e->getMessage());
    header('Location: /petspa/public/empleado/groomer/agenda.php?error=server');
    exit();
}