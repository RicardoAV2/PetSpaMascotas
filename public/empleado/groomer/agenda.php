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

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

// citas del groomer
$sql = "SELECT c.*, m.nombre AS mascota, s.nombre AS servicio
        FROM cita c
        JOIN mascota m ON c.id_mascota = m.id_mascota
        JOIN servicio s ON c.id_servicio = s.id_servicio
        WHERE c.id_groomer = :id
        ORDER BY c.fecha_inicio";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":id", $id_groomer);
$stmt->execute();
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agenda Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Mi Agenda</h2>

    <table class="table table-bordered">
        <tr>
            <th>Mascota</th>
            <th>Servicio</th>
            <th>Fecha</th>
            <th>Acción</th>
        </tr>

        <?php foreach ($citas as $c): ?>
        <tr>
            <td><?= $c['mascota'] ?></td>
            <td><?= $c['servicio'] ?></td>
            <td><?= $c['fecha_inicio'] ?></td>
            <td>
                <a href="grooming.php?id=<?= $c['id_cita'] ?>" class="btn btn-primary btn-sm">
                    Atender
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>

</body>
</html>