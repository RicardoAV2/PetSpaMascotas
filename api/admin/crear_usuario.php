<?php

require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/Logger.php";
require_once "../../core/Security.php";

Auth::setConnection($conn);
Logger::setConnection($conn);
Middleware::requireAdmin();
Middleware::checkSessionTimeout();

function redirectWithError($message) {
    header('Location: /petspa/public/empleado/admin/crear.php?error=' . urlencode($message));
    exit();
}

$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$password = $_POST['password'] ?? '';
$rol = $_POST['rol'] ?? null;
$estado = isset($_POST['estado']) && $_POST['estado'] === '0' ? 0 : 1;

if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || empty($rol)) {
    redirectWithError('Todos los campos obligatorios deben ser completados.');
}

if (!Security::isValidEmail($email)) {
    redirectWithError('Correo electrónico inválido.');
}

if (strlen($password) < MIN_PASSWORD_LENGTH) {
    redirectWithError('La contraseña debe tener al menos ' . MIN_PASSWORD_LENGTH . ' caracteres.');
}

try {
    $stmt = $conn->prepare('SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        redirectWithError('El correo electrónico ya está registrado.');
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$email, $passwordHash, $nombre, $apellido, $telefono, $rol, $estado]);

    $userId = $conn->lastInsertId();

    switch ($rol) {
        case '1':
            $nivelAcceso = trim($_POST['nivel_acceso'] ?? '1');
            $area = trim($_POST['area_responsabilidad'] ?? 'Administración');
            $puedeContratar = isset($_POST['puede_contratar']) ? 1 : 0;

            $stmt = $conn->prepare(
                'INSERT INTO administrador (id_administrador, nivel_acceso, area_responsabilidad, puede_contratar) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $nivelAcceso, $area, $puedeContratar]);
            break;

        case '2':
            $turno = trim($_POST['turno'] ?? '');
            $idiomas = trim($_POST['idiomas'] ?? '');
            $experiencia = intval($_POST['experiencia'] ?? 0);

            $stmt = $conn->prepare(
                'INSERT INTO recepcionista (id_recepcionista, turno, idiomas, experiencia) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $turno, $idiomas, $experiencia]);
            break;

        case '3':
            $especialidad = trim($_POST['especialidad'] ?? '');
            $capacidad = intval($_POST['capacidad_simultanea'] ?? 1);
            $horarioTrabajo = trim($_POST['horario_trabajo'] ?? '');

            $stmt = $conn->prepare(
                'INSERT INTO groomer (id_groomer, especialidad, capacidad_simultanea, horario_trabajo, estado_activo) VALUES (?, ?, ?, ?, 1)'
            );
            $stmt->execute([$userId, $especialidad, $capacidad, $horarioTrabajo]);
            break;

        case '4':
            $direccion = trim($_POST['direccion'] ?? '');
            $ci = trim($_POST['ci'] ?? '');
            $canal = trim($_POST['canal_notificacion_preferido'] ?? 'email');
            $horarioPreferido = trim($_POST['horario_preferido'] ?? '');
            $recibePromo = isset($_POST['recibe_promociones']) ? 1 : 0;

            $stmt = $conn->prepare(
                'INSERT INTO cliente (id_cliente, direccion, ci, canal_notificacion_preferido, horario_preferido, recibe_promociones) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $direccion, $ci, $canal, $horarioPreferido, $recibePromo]);
            break;

        default:
            // No action for unknown roles
            break;
    }

    $conn->commit();

    Logger::log(LOG_EVENT_USER_CREATE, $userId, null, "Usuario creado con rol ID $rol");

    header('Location: /petspa/public/empleado/admin/empleados.php');
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    error_log('Error creando usuario: ' . $e->getMessage());
    redirectWithError('Error al crear el usuario. Por favor revise los datos e intente nuevamente.');
}
