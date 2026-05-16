<?php
require_once "../../config/database.php";

session_start();

$carrito = $_SESSION['carrito'] ?? [];
$id_usuario = $_SESSION['usuario_id'];

$total = 0;

// calcular total
foreach ($carrito as $id => $cantidad) {
    $stmt = $conn->prepare("SELECT precio_base FROM producto WHERE id_producto = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $p = $stmt->fetch();

    $total += $p['precio_base'] * $cantidad;
}

// crear pedido
$sql = "INSERT INTO pedido (id_usuario, total) VALUES (:u, :t)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(":u", $id_usuario);
$stmt->bindParam(":t", $total);
$stmt->execute();

$id_pedido = $conn->lastInsertId();

// insertar detalle
foreach ($carrito as $id => $cantidad) {

    $stmt = $conn->prepare("SELECT precio_base FROM producto WHERE id_producto = :id");
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $p = $stmt->fetch();

    $sql = "INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio)
            VALUES (:p, :prod, :cant, :precio)";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ":p" => $id_pedido,
        ":prod" => $id,
        ":cant" => $cantidad,
        ":precio" => $p['precio_base']
    ]);
}

// limpiar carrito
unset($_SESSION['carrito']);

echo "Pedido realizado correctamente";