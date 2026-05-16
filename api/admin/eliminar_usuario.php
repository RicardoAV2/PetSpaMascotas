<?php

require_once "../../config/database.php";

$id = $_GET['id'];

$sql = "
UPDATE usuario
SET estado = 0
WHERE id_usuario = :id
";

$stmt = $conn->prepare($sql);

$stmt->bindParam(":id", $id);

$stmt->execute();

header("Location: /petspa/public/empleado/admin/empleados.php");