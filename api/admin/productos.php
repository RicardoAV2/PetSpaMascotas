<?php
require_once "../../config/database.php";

$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);
$stockMinimo = intval($_POST['stock_minimo'] ?? 0);
$categoria = !empty($_POST['categoria']) ? intval($_POST['categoria']) : null;
$estado = isset($_POST['estado']) && $_POST['estado'] === '1' ? 1 : 0;

$sql = "INSERT INTO producto (nombre, descripcion, precio_base, stock_actual, stock_minimo, id_categoria, estado_activo)
        VALUES (:n, :d, :p, :s, :min, :c, :estado)";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":n", $nombre);
$stmt->bindParam(":d", $descripcion);
$stmt->bindParam(":p", $precio);
$stmt->bindParam(":s", $stock);
$stmt->bindParam(":min", $stockMinimo);
if ($categoria === null) {
    $stmt->bindValue(":c", null, PDO::PARAM_NULL);
} else {
    $stmt->bindValue(":c", $categoria, PDO::PARAM_INT);
}
$stmt->bindParam(":estado", $estado, PDO::PARAM_INT);
$stmt->execute();

header("Location: /petspa/public/empleado/admin/productos.php");