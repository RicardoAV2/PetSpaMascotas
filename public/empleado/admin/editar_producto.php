<?php
require_once "../../../config/database.php";

$id = $_GET['id'];

$stmt = $conn->prepare("SELECT * FROM producto WHERE id_producto = :id");
$stmt->bindParam(":id", $id);
$stmt->execute();
$p = $stmt->fetch();
?>

<form action="/petspa/api/admin/editar_producto.php" method="POST">
    <input type="hidden" name="id" value="<?= $p['id_producto'] ?>">

    <input type="text" name="nombre" value="<?= $p['nombre'] ?>" class="form-control mb-2">
    <input type="number" name="precio" value="<?= $p['precio'] ?>" class="form-control mb-2">
    <input type="number" name="stock" value="<?= $p['stock'] ?>" class="form-control mb-2">

    <button class="btn btn-primary">Actualizar</button>
</form>