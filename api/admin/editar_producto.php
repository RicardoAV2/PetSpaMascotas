<?php
require_once "../../config/database.php";

$id = $_POST['id'];
$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];

$sql = "UPDATE producto 
        SET nombre=:n, precio=:p, stock=:s 
        WHERE id_producto=:id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":n", $nombre);
$stmt->bindParam(":p", $precio);
$stmt->bindParam(":s", $stock);
$stmt->bindParam(":id", $id);
$stmt->execute();

header("Location: /petspa/public/empleado/admin/productos.php");