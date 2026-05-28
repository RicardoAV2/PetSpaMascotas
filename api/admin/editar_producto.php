<?php
require_once "../../config/database.php";

$id = intval($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$precio = floatval($_POST['precio'] ?? 0);
$stock = intval($_POST['stock'] ?? 0);
$stockMinimo = intval($_POST['stock_minimo'] ?? 0);
$categoria = !empty($_POST['categoria']) ? intval($_POST['categoria']) : null;
$estado = isset($_POST['estado']) && $_POST['estado'] === '1' ? 1 : 0;

$sql = "UPDATE producto
        SET nombre = :n,
            descripcion = :d,
            precio_base = :p,
            stock_actual = :s,
            stock_minimo = :min,
            id_categoria = :c,
            estado_activo = :estado
        WHERE id_producto = :id";

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
$stmt->bindParam(":id", $id, PDO::PARAM_INT);
$stmt->execute();

header("Location: /petspa/public/empleado/admin/productos.php");