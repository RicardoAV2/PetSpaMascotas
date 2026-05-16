<?php
require_once "../../config/database.php";

$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];
$categoria = $_POST['categoria'];

$sql = "INSERT INTO producto (nombre, precio, stock, id_categoria)
        VALUES (:n, :p, :s, :c)";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":n", $nombre);
$stmt->bindParam(":p", $precio);
$stmt->bindParam(":s", $stock);
$stmt->bindParam(":c", $categoria);
$stmt->execute();

header("Location: /petspa/public/empleado/admin/productos.php");