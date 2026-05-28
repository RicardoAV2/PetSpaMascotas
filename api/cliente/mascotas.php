<?php
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";
require_once "../../core/Security.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$clienteId = $currentUser['id'];

function ensureMascotaSchema(PDO $conn) {
    $columns = ['tamano', 'foto'];
    foreach ($columns as $column) {
        $stmt = $conn->prepare("SHOW COLUMNS FROM mascota LIKE ?");
        $stmt->execute([$column]);
        if (!$stmt->fetch()) {
            if ($column === 'tamano') {
                $conn->exec("ALTER TABLE mascota ADD COLUMN tamano VARCHAR(50) NULL AFTER peso");
            } elseif ($column === 'foto') {
                $conn->exec("ALTER TABLE mascota ADD COLUMN foto VARCHAR(255) NULL AFTER tamano");
            }
        }
    }
}

try {
    ensureMascotaSchema($conn);
} catch (Exception $e) {
    // Si no puede modificar el esquema, continuar sin bloquear el flujo.
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!Security::validateCSRFToken($csrf)) {
        header('Location: /petspa/public/cliente/mascotas.php?error=csrf');
        exit();
    }

    $idMascota = isset($_POST['id_mascota']) ? intval($_POST['id_mascota']) : 0;
    $nombre = trim($_POST['nombre'] ?? '');
    $especie = trim($_POST['especie'] ?? '');
    $raza = trim($_POST['raza'] ?? '');
    $peso = isset($_POST['peso']) ? floatval($_POST['peso']) : null;
    $edad = isset($_POST['edad']) ? intval($_POST['edad']) : null;
    $vacunas = trim($_POST['vacunas'] ?? '');
    $temperamento = trim($_POST['temperamento'] ?? '');
    $comportamiento = trim($_POST['notas'] ?? '');
    $tamano = trim($_POST['tamano'] ?? '');

    if ($action === 'delete') {
        if ($idMascota <= 0) {
            header('Location: /petspa/public/cliente/mascotas.php?error=invalid');
            exit();
        }

        try {
            $stmt = $conn->prepare("DELETE FROM mascota WHERE id_mascota = ? AND id_cliente_principal = ?");
            $stmt->execute([$idMascota, $clienteId]);
            header('Location: /petspa/public/cliente/mascotas.php?success=deleted');
            exit();
        } catch (Exception $e) {
            error_log('Error mascota delete: ' . $e->getMessage());
            header('Location: /petspa/public/cliente/mascotas.php?error=server');
            exit();
        }
    }

    $errors = [];
    if ($nombre === '') {
        $errors[] = 'El nombre de la mascota es obligatorio.';
    }
    if ($especie === '') {
        $errors[] = 'La especie es obligatoria.';
    }

    if (!empty($_FILES['foto']['name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = $_FILES['foto']['type'];
        if (!in_array($fileType, $allowed, true)) {
            $errors[] = 'El archivo de la foto debe ser JPG, PNG o WEBP.';
        }
        if ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'La foto no puede superar los 3MB.';
        }
    }

    if ($errors) {
        $_SESSION['mascota_errors'] = $errors;
        header('Location: /petspa/public/cliente/mascotas.php');
        exit();
    }

    $fotoPath = null;
    if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../../uploads/mascotas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $filename = 'mascota_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destination)) {
            $fotoPath = '/petspa/uploads/mascotas/' . $filename;
        }
    }

    try {
        if ($action === 'update' && $idMascota > 0) {
            $stmt = $conn->prepare("SELECT id_mascota FROM mascota WHERE id_mascota = ? AND id_cliente_principal = ?");
            $stmt->execute([$idMascota, $clienteId]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$current) {
                throw new Exception('Mascota no encontrada.');
            }

            $query = "UPDATE mascota SET nombre = :nombre, especie = :especie, raza = :raza, peso = :peso, edad = :edad, vacunas = :vacunas, temperamento = :temperamento, comportamiento = :comportamiento, tamano = :tamano";
            if ($fotoPath) {
                $query .= ", foto = :foto";
            }
            $query .= " WHERE id_mascota = :id";

            $stmt = $conn->prepare($query);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':especie', $especie);
            $stmt->bindParam(':raza', $raza);
            $stmt->bindParam(':peso', $peso);
            $stmt->bindParam(':edad', $edad, PDO::PARAM_INT);
            $stmt->bindParam(':vacunas', $vacunas);
            $stmt->bindParam(':temperamento', $temperamento);
            $stmt->bindParam(':comportamiento', $comportamiento);
            $stmt->bindParam(':tamano', $tamano);
            if ($fotoPath) {
                $stmt->bindParam(':foto', $fotoPath);
            }
            $stmt->bindParam(':id', $idMascota, PDO::PARAM_INT);
            $stmt->execute();

            header('Location: /petspa/public/cliente/editar_mascota.php?id=' . $idMascota . '&success=updated');
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO mascota (nombre, especie, raza, peso, edad, vacunas, temperamento, comportamiento, tamano, foto, fecha_registro, id_cliente_principal)
            VALUES (:nombre, :especie, :raza, :peso, :edad, :vacunas, :temperamento, :comportamiento, :tamano, :foto, NOW(), :cliente)");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':especie', $especie);
        $stmt->bindParam(':raza', $raza);
        $stmt->bindParam(':peso', $peso);
        $stmt->bindParam(':edad', $edad, PDO::PARAM_INT);
        $stmt->bindParam(':vacunas', $vacunas);
        $stmt->bindParam(':temperamento', $temperamento);
        $stmt->bindParam(':comportamiento', $comportamiento);
        $stmt->bindParam(':tamano', $tamano);
        $stmt->bindParam(':foto', $fotoPath);
        $stmt->bindParam(':cliente', $clienteId, PDO::PARAM_INT);
        $stmt->execute();

        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            echo json_encode(['success' => true, 'message' => 'Mascota creada.']);
            exit();
        }

        header('Location: /petspa/public/cliente/mascotas.php?success=created');
        exit();
    } catch (Exception $e) {
        error_log('Error mascota API: ' . $e->getMessage());
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            echo json_encode(['success' => false, 'message' => 'Error interno al guardar la mascota.']);
            exit();
        }
        header('Location: /petspa/public/cliente/mascotas.php?error=server');
        exit();
    }
}

header('Location: /petspa/public/cliente/mascotas.php');
exit();
