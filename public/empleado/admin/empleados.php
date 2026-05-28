<?php
require_once "../../../config/database.php";
require_once "../../../core/middleware.php";
require_once "../../../core/Auth.php";

// Inicializar Auth
Auth::setConnection($conn);

Middleware::requireAdmin();

$sql = "
SELECT 
    u.id_usuario,
    u.nombre,
    u.apellido,
    u.email,
    u.telefono,
    u.estado,
    u.ultimo_acceso,
    r.nombre AS rol,

    g.especialidad,
    rec.turno,
    adm.nivel_acceso

FROM usuario u

JOIN rol r 
ON u.id_rol = r.id_rol

LEFT JOIN groomer g
ON g.id_groomer = u.id_usuario

LEFT JOIN recepcionista rec
ON rec.id_recepcionista = u.id_usuario

LEFT JOIN administrador adm
ON adm.id_administrador = u.id_usuario

WHERE r.nombre IN ('admin','recepcion','groomer')

ORDER BY u.id_usuario DESC
";
$empleados = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
$sql2 = "SELECT u.id_usuario, u.email, u.nombre, u.apellido, r.nombre AS rol
        FROM usuario u
        JOIN rol r 
        ON u.id_rol = r.id_rol
        WHERE r.nombre = 'cliente'";
$clientes = $conn->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Empleados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Gestión de Empleados</h2>

    <table class="table table-hover align-middle">
<a href="crear.php" class="btn btn-success">

+ Nuevo Usuario

</a>
<a href="/petspa/public/empleado/admin/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>

<thead class="table-dark">

<tr>
    <th>Usuario</th>
    <th>Contacto</th>
    <th>Rol</th>
    <th>Estado</th>
    <th>Información</th>
    <th>Acciones</th>
</tr>

</thead>

<tbody>

<?php foreach ($empleados as $e): ?>

<tr>

<td>

    <div class="fw-bold">
        <?= $e['nombre'] . ' ' . $e['apellido'] ?>
    </div>

    <small class="text-muted">
        ID: <?= $e['id_usuario'] ?>
    </small>

</td>

<td>

    <div><?= $e['email'] ?></div>

    <small class="text-muted">
        <?= $e['telefono'] ?>
    </small>

</td>

<td>

<?php

$badge = "bg-secondary";

if($e['rol'] == 'admin'){
    $badge = "bg-danger";
}

if($e['rol'] == 'groomer'){
    $badge = "bg-primary";
}

if($e['rol'] == 'recepcion'){
    $badge = "bg-warning text-dark";
}

?>

<span class="badge <?= $badge ?>">
    <?= strtoupper($e['rol']) ?>
</span>

</td>

<td>

<?php if($e['estado']): ?>

<span class="badge bg-success">
    Activo
</span>

<?php else: ?>

<span class="badge bg-danger">
    Inactivo
</span>

<?php endif; ?>

</td>

<td>

<?php if($e['rol'] == 'groomer'): ?>

    Especialidad:
    <?= $e['especialidad'] ?? 'No definida' ?>

<?php endif; ?>

<?php if($e['rol'] == 'recepcion'): ?>

    Turno:
    <?= $e['turno'] ?? 'No definido' ?>

<?php endif; ?>

<?php if($e['rol'] == 'admin'): ?>

    Nivel:
    <?= $e['nivel_acceso'] ?? 1 ?>

<?php endif; ?>

</td>

<td>

<div class="d-flex gap-2">

<a href="editar.php?id=<?= $e['id_usuario'] ?>"
class="btn btn-warning btn-sm">

Editar

</a>

<a href="/petspa/api/admin/eliminar_usuario.php?id=<?= $e['id_usuario'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar usuario?')">

Eliminar

</a>

</div>

</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

<h3 class="mt-5">Clientes registrados</h3>
<table class="table table-hover align-middle">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($clientes as $cliente): ?>
            <tr>
                <td><?= $cliente['id_usuario'] ?></td>
                <td><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></td>
                <td><?= htmlspecialchars($cliente['email']) ?></td>
                <td><span class="badge bg-success"><?= strtoupper($cliente['rol']) ?></span></td>
                <td>
                    <a href="editar.php?id=<?= $cliente['id_usuario'] ?>" class="btn btn-sm btn-warning">Editar</a>
                    <a href="/petspa/api/admin/eliminar_usuario.php?id=<?= $cliente['id_usuario'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar cliente?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<div class="col-md-4">
        <a href="dashboard.php" class="card p-3 bg-warning text-dark d-block text-decoration-none">
            Volver al Panel Admin
        </a>
    </div>
</body>
</html>