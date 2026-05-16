<?php
require_once "../../config/database.php";

$id = $_GET['id'];
$estado = $_GET['estado'];

$sql = "UPDATE cita SET estado=:estado WHERE id_cita=:id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(":estado", $estado);
$stmt->bindParam(":id", $id);
$stmt->execute();

header("Location: /petspa/public/empleado/recepcionista/citas.php");