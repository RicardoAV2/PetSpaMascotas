<?php
require_once "../../../config/database.php";
require_once "../../../config/constants.php";
require_once "../../../core/Auth.php";
require_once "../../../core/middleware.php";
require_once "../../../core/helpers.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$id_cita = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

if ($id_cita <= 0) {
    header('Location: /petspa/public/empleado/groomer/agenda.php');
    exit();
}

$stmt = $conn->prepare("SELECT id_cita FROM cita WHERE id_cita = ? AND id_groomer = (SELECT id_groomer FROM groomer WHERE id_usuario = ?)");
$stmt->execute([$id_cita, $userId]);
$citaValida = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$citaValida) {
    header('Location: /petspa/public/empleado/groomer/agenda.php');
    exit();
}
?>

<form action="/petspa/api/empleado/grooming.php" method="POST">
    <input type="hidden" name="id_cita" value="<?= $id_cita ?>">

    <textarea name="observaciones" class="form-control mb-2" placeholder="Observaciones"></textarea>

    <textarea name="estado_mascota" class="form-control mb-2" placeholder="Estado de la mascota"></textarea>

    <button class="btn btn-success">Finalizar Grooming</button>
</form>