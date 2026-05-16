<?php
require_once "../../../config/database.php";

$sql = "SELECT c.*, m.nombre AS mascota
        FROM cita c
        JOIN mascota m ON c.id_mascota = m.id_mascota
        ORDER BY c.fecha_inicio DESC";

$citas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Gestión de Citas</h2>

    <table class="table table-bordered">
        <tr>
            <th>ID</th>
            <th>Mascota</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>

        <?php foreach ($citas as $c): ?>
        <tr>
            <td><?= $c['id_cita'] ?></td>
            <td><?= $c['mascota'] ?></td>
            <td><?= $c['fecha_inicio'] ?></td>
            <td><?= $c['estado'] ?></td>
            <td>
                <a href="/petspa/api/empleado/citas.php?id=<?= $c['id_cita'] ?>&estado=cancelado"
                   class="btn btn-danger btn-sm">Cancelar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div>

</body>
</html>