<?php
require_once "../../config/database.php";

$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM producto WHERE id_producto = :id");
$stmt->bindParam(":id", $id);
$stmt->execute();

header("Location: /petspa/public/empleado/admin/productos.php");