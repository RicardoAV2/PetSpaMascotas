<?php
/**
 * API para guardar datos del grooming
 * POST /petspa/api/empleado/grooming.php
 * 
 * Maneja:
 * - Crear/actualizar ficha de grooming
 * - Registrar servicios completados/parciales
 * - Registrar consumo de productos por servicio
 * - Guardar datos de la mascota
 * - Opción de guardado parcial o completada
 */

require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";
require_once "../../core/Security.php";
require_once "helpers_grooming.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

$id_cita = isset($_POST['id_cita']) ? intval($_POST['id_cita']) : 0;
$id_ficha = isset($_POST['id_ficha']) && !empty($_POST['id_ficha']) ? intval($_POST['id_ficha']) : null;
$csrf = $_POST['csrf_token'] ?? '';
$tipo_guardado = isset($_POST['tipo_guardado']) ? trim($_POST['tipo_guardado']) : 'parcial';

// Validaciones
if (!Security::validateCSRFToken($csrf)) {
    header('Location: /petspa/public/empleado/groomer/agenda.php?error=csrf');
    exit();
}

if ($id_cita <= 0) {
    header('Location: /petspa/public/empleado/groomer/agenda.php?error=invalid_cita');
    exit();
}

try {
    // Verificar permisos
    $stmt = $conn->prepare("SELECT id_cita FROM cita WHERE id_cita = ? AND id_groomer = ?");
    $stmt->execute([$id_cita, $userId]);
    if (!$stmt->fetch()) {
        header('Location: /petspa/public/empleado/groomer/agenda.php?error=not_allowed');
        exit();
    }

    // Recolectar datos del formulario
    $temperatura_animal = isset($_POST['temperatura_animal']) ? floatval($_POST['temperatura_animal']) : null;
    $peso_kg = isset($_POST['peso_kg']) ? floatval($_POST['peso_kg']) : null;
    $raza_talla = isset($_POST['raza_talla']) ? trim($_POST['raza_talla']) : '';
    $estado_mascota = isset($_POST['estado_mascota']) ? trim($_POST['estado_mascota']) : '';
    $observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';
    $notas_internas = isset($_POST['notas_internas']) ? trim($_POST['notas_internas']) : '';
    
    // Servicios completados
    $servicios_completados = isset($_POST['servicios_completados']) && is_array($_POST['servicios_completados']) 
        ? array_keys($_POST['servicios_completados']) 
        : [];
    
    // Productos usados por servicio
    $productos_usados_por_servicio = isset($_POST['productos_usados']) && is_array($_POST['productos_usados']) 
        ? $_POST['productos_usados'] 
        : [];

    // Determinar estado de la ficha
    $estado_cierre = ($tipo_guardado === 'completada') ? 'completada' : 'parcial';

    // Crear o actualizar ficha de grooming
    if (!$id_ficha) {
        // Crear nueva ficha
        $stmt = $conn->prepare("
            INSERT INTO ficha_grooming 
            (hora_inicio, temperatura_animal, peso_kg, raza_talla, estado_mascota, observaciones, 
             notas_internas, estado_cierre, id_cita)
            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $temperatura_animal,
            $peso_kg,
            $raza_talla,
            $estado_mascota,
            $observaciones,
            $notas_internas,
            $estado_cierre,
            $id_cita
        ]);
        $id_ficha = $conn->lastInsertId();
    } else {
        // Actualizar ficha existente
        $stmt = $conn->prepare("
            UPDATE ficha_grooming 
            SET temperatura_animal = ?, peso_kg = ?, raza_talla = ?, estado_mascota = ?, 
                observaciones = ?, notas_internas = ?, estado_cierre = ?
            WHERE id_ficha = ?
        ");
        $stmt->execute([
            $temperatura_animal,
            $peso_kg,
            $raza_talla,
            $estado_mascota,
            $observaciones,
            $notas_internas,
            $estado_cierre,
            $id_ficha
        ]);
    }

    // Obtener servicios de la cita para procesar cada uno
    $stmt = $conn->prepare("
        SELECT cs.id_servicio FROM cita_servicio cs WHERE cs.id_cita = ?
    ");
    $stmt->execute([$id_cita]);
    $serviciosCita = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar cada servicio
    foreach ($serviciosCita as $servicioRow) {
        $id_servicio = $servicioRow['id_servicio'];
        $completado = in_array($id_servicio, $servicios_completados) ? 1 : 0;

        // Registrar estado del servicio en la ficha
        registrarServicioCompletado($conn, $id_ficha, $id_servicio, (bool)$completado, $completado ? 100 : 50);

        // Si el servicio está completado, procesar productos usados
        if ($completado && isset($productos_usados_por_servicio[$id_servicio])) {
            // Registrar consumo de productos
            registrarConsumoServicio($conn, $id_ficha, $id_servicio, $productos_usados_por_servicio[$id_servicio]);

            // Aplicar cambios en inventario
            aplicarConsumoProductosServicio($conn, $id_ficha, $id_cita, $id_servicio, $userId);
        }
    }

    // Si la cita está completamente finalizada, actualizar estado de la cita
    if ($tipo_guardado === 'completada') {
        $stmt = $conn->prepare("
            UPDATE cita 
            SET estado = 'completada', fecha_reprogramacion = NOW()
            WHERE id_cita = ?
        ");
        $stmt->execute([$id_cita]);

        // Actualizar hora_fin_real de la ficha
        $stmt = $conn->prepare("
            UPDATE ficha_grooming 
            SET hora_fin_real = NOW(), fecha_cierre = NOW()
            WHERE id_ficha = ?
        ");
        $stmt->execute([$id_ficha]);

        // Subir foto si existe
        if (isset($_FILES['foto_final']) && $_FILES['foto_final']['error'] === UPLOAD_ERR_OK) {
            $uploads_dir = __DIR__ . '/../../uploads/grooming/';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0755, true);
            }

            $file_ext = pathinfo($_FILES['foto_final']['name'], PATHINFO_EXTENSION);
            $file_name = 'grooming_' . $id_ficha . '_' . time() . '.' . $file_ext;
            $file_path = $uploads_dir . $file_name;

            if (move_uploaded_file($_FILES['foto_final']['tmp_name'], $file_path)) {
                $stmt = $conn->prepare("
                    INSERT INTO foto (url, tipo, descripcion, id_ficha)
                    VALUES (?, 'despues', 'Foto final del grooming', ?)
                ");
                $stmt->execute(['uploads/grooming/' . $file_name, $id_ficha]);
            }
        }

        $redirectUrl = '/petspa/public/empleado/groomer/agenda.php?success=completed';
    } else {
        // Guardado parcial
        $redirectUrl = '/petspa/public/empleado/groomer/grooming.php?id=' . $id_cita . '&success=saved_partial';
    }

    header('Location: ' . $redirectUrl);
    exit();

} catch (Exception $e) {
    error_log('Error en API grooming: ' . $e->getMessage());
    header('Location: /petspa/public/empleado/groomer/grooming.php?id=' . $id_cita . '&error=server_error');
    exit();
}
?>


try {
    $stmt = $conn->prepare("SELECT c.id_cita, c.id_mascota, c.id_servicio, c.fecha_inicio, m.id_cliente_principal, u.email AS cliente_email
        FROM cita c
        JOIN mascota m ON c.id_mascota = m.id_mascota
        JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
        JOIN usuario u ON cl.id_cliente = u.id_usuario
        WHERE c.id_cita = ? AND c.id_groomer = (SELECT id_groomer FROM groomer WHERE id_usuario = ?)");
    $stmt->execute([$id_cita, Auth::getCurrentUser()['id']]);
    $cita = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cita) {
        header('Location: /petspa/public/empleado/groomer/agenda.php?error=not_allowed');
        exit();
    }

    $stmt = $conn->prepare("SELECT id_servicio FROM cita_servicio WHERE id_cita = ?");
    $stmt->execute([$id_cita]);
    $servicioIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $temperaturaAnimal = trim($_POST['temperatura_animal'] ?? '');
    $pesoKg = trim($_POST['peso_kg'] ?? '');
    $razaTalla = trim($_POST['raza_talla'] ?? '');
    $notasInternas = trim($_POST['notas_internas'] ?? '');
    $insumosUsados = isset($_POST['insumos']) && is_array($_POST['insumos']) ? $_POST['insumos'] : [];

    if (count($checklistItems) < 4) {
        header('Location: /petspa/public/empleado/groomer/grooming.php?id=' . $id_cita . '&error=checklist');
        exit();
    }

    $fotoPath = null;
    if (!empty($_FILES['foto_final']['name']) && is_uploaded_file($_FILES['foto_final']['tmp_name'])) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($_FILES['foto_final']['type'], $allowed, true)) {
            throw new Exception('Formato de imagen no válido.');
        }
        $uploadDir = __DIR__ . '/../../uploads/grooming/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $extension = pathinfo($_FILES['foto_final']['name'], PATHINFO_EXTENSION);
        $filename = 'grooming_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $extension;
        $destination = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['foto_final']['tmp_name'], $destination)) {
            throw new Exception('No se pudo guardar la imagen.');
        }
        $fotoPath = '/petspa/uploads/grooming/' . $filename;
    }

    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO ficha_grooming (id_cita, observaciones, notas_internas, estado_mascota, temperatura_animal, peso_kg, raza_talla, hora_inicio, hora_fin_real, consumido_inventario, fecha_cierre) VALUES (:id_cita, :observaciones, :notas_internas, :estado_mascota, :temperatura, :peso, :raza_talla, :hora_inicio, NOW(), :consumido, NOW())");
    $consumido = 1;
    $stmt->execute([
        ':id_cita' => $id_cita,
        ':observaciones' => $observaciones,
        ':notas_internas' => $notasInternas,
        ':estado_mascota' => $estado_mascota,
        ':temperatura' => $temperaturaAnimal !== '' ? $temperaturaAnimal : null,
        ':peso' => $pesoKg !== '' ? $pesoKg : null,
        ':raza_talla' => $razaTalla,
        ':hora_inicio' => $cita['fecha_inicio'],
        ':consumido' => $consumido
    ]);
    $id_ficha = $conn->lastInsertId();

    $stmt = $conn->prepare("INSERT INTO ficha_checklist (id_ficha, id_item, estado) VALUES (:id_ficha, :id_item, 'completado')");
    foreach ($checklistItems as $item) {
        $stmt->execute([':id_ficha' => $id_ficha, ':id_item' => $item]);
    }

    if ($fotoPath) {
        $stmt = $conn->prepare("INSERT INTO foto (url, tipo, descripcion, id_ficha) VALUES (:url, 'despues', :descripcion, :id_ficha)");
        $stmt->execute([
            ':url' => $fotoPath,
            ':descripcion' => 'Foto final de grooming',
            ':id_ficha' => $id_ficha
        ]);
    }

    if (!empty($insumosUsados)) {
        aplicarConsumoReservas($conn, $id_ficha, $id_cita, $insumosUsados, Auth::getCurrentUser()['id']);
    } else {
        if (!empty($servicioIds)) {
            consumeServicesProducts($conn, $id_ficha, $id_cita, $servicioIds, $cita['id_mascota'], Auth::getCurrentUser()['id']);
        } else {
            consumeServiceProducts($conn, $id_ficha, $id_cita, $cita['id_servicio'], $cita['id_mascota'], Auth::getCurrentUser()['id']);
        }
    }

    $stmt = $conn->prepare("UPDATE cita SET estado = 'completada' WHERE id_cita = :id_cita");
    $stmt->execute([':id_cita' => $id_cita]);

    if (!empty($cita['cliente_email'])) {
        $mensaje = sprintf('Tu mascota ya está lista. Cita programada para %s.', $cita['fecha_inicio']);
        $stmt = $conn->prepare("INSERT INTO notificacion (tipo_evento, canal, mensaje, destino, fecha_programacion, estado_envio, id_cliente, id_cita) VALUES ('listo_recoger', 'email', :mensaje, :destino, NOW(), 'pendiente', :id_cliente, :id_cita)");
        $stmt->execute([
            ':mensaje' => $mensaje,
            ':destino' => $cita['cliente_email'],
            ':id_cliente' => $cita['id_cliente_principal'],
            ':id_cita' => $id_cita
        ]);
    }

    $conn->commit();
    header('Location: /petspa/public/empleado/groomer/agenda.php?success=grooming');
    exit();
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('Error en API grooming: ' . $e->getMessage());
    header('Location: /petspa/public/empleado/groomer/grooming.php?id=' . $id_cita . '&error=server');
    exit();
}
