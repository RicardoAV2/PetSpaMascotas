<?php

require_once "../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit("Acceso inválido");
}

$nombre = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$email = trim($_POST['email']);
$telefono = trim($_POST['telefono']);
$password = $_POST['password'];
$rol = $_POST['rol'];

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {

    $conn->beginTransaction();

    // Crear usuario principal
    $sql = "
    INSERT INTO usuario (
        nombre,
        apellido,
        email,
        telefono,
        password_hash,
        id_rol,
        estado
    )
    VALUES (
        :nombre,
        :apellido,
        :email,
        :telefono,
        :password,
        :rol,
        1
    )
    ";

    $stmt = $conn->prepare($sql);

    $stmt->execute([

        ':nombre' => $nombre,
        ':apellido' => $apellido,
        ':email' => $email,
        ':telefono' => $telefono,
        ':password' => $passwordHash,
        ':rol' => $rol

    ]);

    $idUsuario = $conn->lastInsertId();

    /*
    ==========================================
    CREAR SEGÚN ROL
    ==========================================
    */

    // ADMIN
    if ($rol == 1) {

        $sqlAdmin = "
        INSERT INTO administrador (
            id_administrador,
            nivel_acceso
        )
        VALUES (
            :id,
            1
        )
        ";

        $stmtAdmin = $conn->prepare($sqlAdmin);

        $stmtAdmin->execute([
            ':id' => $idUsuario
        ]);
    }

    // RECEPCIONISTA
    if ($rol == 2) {

        $sqlRecep = "
        INSERT INTO recepcionista (
            id_recepcionista,
            turno
        )
        VALUES (
            :id,
            'Mañana'
        )
        ";

        $stmtRecep = $conn->prepare($sqlRecep);

        $stmtRecep->execute([
            ':id' => $idUsuario
        ]);
    }

    // GROOMER
    if ($rol == 3) {

        $sqlGroomer = "
        INSERT INTO groomer (
            id_groomer,
            especialidad
        )
        VALUES (
            :id,
            'General'
        )
        ";

        $stmtGroomer = $conn->prepare($sqlGroomer);

        $stmtGroomer->execute([
            ':id' => $idUsuario
        ]);
    }

    // CLIENTE
    if ($rol == 4) {

        $sqlCliente = "
        INSERT INTO cliente (
            id_cliente
        )
        VALUES (
            :id
        )
        ";

        $stmtCliente = $conn->prepare($sqlCliente);

        $stmtCliente->execute([
            ':id' => $idUsuario
        ]);
    }

    $conn->commit();

    header("Location: /petspa/public/empleado/admin/empleados.php");

} catch (Exception $e) {

    $conn->rollBack();

    die("Error: " . $e->getMessage());
}