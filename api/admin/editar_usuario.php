<?php

require_once "../../config/database.php";

$id = $_POST['id'];

$nombre = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$email = trim($_POST['email']);
$telefono = trim($_POST['telefono']);

$rol = $_POST['rol'];
$estado = $_POST['estado'];

$password = $_POST['password'];

try {

    if (!empty($password)) {

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "
        UPDATE usuario
        SET
            nombre = :nombre,
            apellido = :apellido,
            email = :email,
            telefono = :telefono,
            id_rol = :rol,
            estado = :estado,
            password_hash = :password
        WHERE id_usuario = :id
        ";

    } else {

        $sql = "
        UPDATE usuario
        SET
            nombre = :nombre,
            apellido = :apellido,
            email = :email,
            telefono = :telefono,
            id_rol = :rol,
            estado = :estado
        WHERE id_usuario = :id
        ";
    }

    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":nombre", $nombre);
    $stmt->bindParam(":apellido", $apellido);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":telefono", $telefono);
    $stmt->bindParam(":rol", $rol);
    $stmt->bindParam(":estado", $estado);
    $stmt->bindParam(":id", $id);

    if (!empty($password)) {
        $stmt->bindParam(":password", $passwordHash);
    }

    $stmt->execute();

    header("Location: /petspa/public/empleado/admin/empleados.php");

} catch (Exception $e) {

    die("Error: " . $e->getMessage());
}