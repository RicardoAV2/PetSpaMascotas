<?php

require_once "../../../config/database.php";

$id = $_GET['id'];

$sql = "
SELECT 
    u.*,
    r.nombre AS rol

FROM usuario u

JOIN rol r
ON u.id_rol = r.id_rol

WHERE u.id_usuario = :id
";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":id", $id);

$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="container mt-5">

<div class="card shadow">

<div class="card-header bg-dark text-white">

<h4>Editar Usuario</h4>

</div>

<div class="card-body">

<form action="/petspa/api/admin/editar_usuario.php" method="POST">

<input type="hidden" name="id"
value="<?= $user['id_usuario'] ?>">

<div class="row">

<div class="col-md-6 mb-3">

<label>Nombre</label>

<input
type="text"
name="nombre"
value="<?= $user['nombre'] ?>"
class="form-control"
required>

</div>

<div class="col-md-6 mb-3">

<label>Apellido</label>

<input
type="text"
name="apellido"
value="<?= $user['apellido'] ?>"
class="form-control"
required>

</div>

</div>

<div class="mb-3">

<label>Email</label>

<input
type="email"
name="email"
value="<?= $user['email'] ?>"
class="form-control"
required>

</div>

<div class="mb-3">

<label>Teléfono</label>

<input
type="text"
name="telefono"
value="<?= $user['telefono'] ?>"
class="form-control">

</div>

<div class="mb-3">

<label>Rol</label>

<select name="rol" class="form-select">

<option value="1"
<?= $user['id_rol'] == 1 ? 'selected' : '' ?>>
Admin
</option>

<option value="2"
<?= $user['id_rol'] == 2 ? 'selected' : '' ?>>
Recepción
</option>

<option value="3"
<?= $user['id_rol'] == 3 ? 'selected' : '' ?>>
Groomer
</option>

<option value="4"
<?= $user['id_rol'] == 4 ? 'selected' : '' ?>>
Cliente
</option>

</select>

</div>

<div class="mb-3">

<label>Estado</label>

<select name="estado" class="form-select">

<option value="1"
<?= $user['estado'] ? 'selected' : '' ?>>
Activo
</option>

<option value="0"
<?= !$user['estado'] ? 'selected' : '' ?>>
Inactivo
</option>

</select>

</div>

<div class="mb-3">

<label>Nueva contraseña</label>

<input
type="password"
name="password"
class="form-control">

<small class="text-muted">
Dejar vacío para no cambiar
</small>

</div>

<button class="btn btn-primary">

Actualizar Usuario

</button>

<a href="empleados.php"
class="btn btn-secondary">

Volver

</a>

</form>

</div>

</div>

</div>