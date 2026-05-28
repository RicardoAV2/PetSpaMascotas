<?php
/**
 * FUNCIONES AUXILIARES GLOBALES
 * ==============================
 */

require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

/**
 * Renderizar error bootstrap
 */
function showAlert($message, $type = 'danger') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Validar que el usuario pertenezca a un rol específico
 */
function hasRole($role) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $role;
}

/**
 * Obtener nombre de rol en español
 */
function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrador',
        'groomer' => 'Groomer/Estilista',
        'recepcion' => 'Recepción',
        'cliente' => 'Cliente'
    ];
    return $labels[$role] ?? ucfirst($role);
}

/**
 * Formatear fecha a formato legible
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatear dinero
 */
function formatCurrency($amount, $currency = 'Bs.') {
    return $currency . ' ' . number_format($amount, 2, ',', '.');
}

/**
 * Convertir array a opciones HTML
 */
function arrayToOptions($array, $selected = null) {
    $options = '';
    foreach ($array as $key => $value) {
        $sel = ($key === $selected) ? 'selected' : '';
        $options .= "<option value='$key' $sel>$value</option>";
    }
    return $options;
}

/**
 * Obtener badge de estado
 */
function getStatusBadge($status) {
    if ($status === true || $status === '1' || $status === 1) {
        return "<span class='badge bg-success'>Activo</span>";
    } else if ($status === false || $status === '0' || $status === 0) {
        return "<span class='badge bg-danger'>Inactivo</span>";
    } else if ($status === 'pending') {
        return "<span class='badge bg-warning'>Pendiente</span>";
    }
    return "<span class='badge bg-secondary'>$status</span>";
}

/**
 * Generar saludo según la hora
 */
function getGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return 'Buenos días';
    } else if ($hour >= 12 && $hour < 17) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
}

/**
 * Verificar si una URL es activa (para menús)
 */
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return ($currentPage === $page) ? 'active' : '';
}

/**
 * Redirigir a una página
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Obtener dato de formulario de forma segura
 */
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? Security::sanitizeInput($_POST[$key]) : $default;
}

function getGet($key, $default = '') {
    return isset($_GET[$key]) ? Security::sanitizeInput($_GET[$key]) : $default;
}

/**
 * Verifica si el cliente posee una mascota (como dueño principal o adicional)
 */
function clientOwnsMascota(PDO $conn, int $mascotaId, int $clienteId): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM mascota m
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_mascota = :mascota
           AND (m.id_cliente_principal = :cliente OR md.id_cliente = :cliente)
         LIMIT 1"
    );
    $stmt->execute([':mascota' => $mascotaId, ':cliente' => $clienteId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Devuelve las mascotas asociadas a un cliente, ya sea como dueño principal o secundario.
 */
function fetchClientMascotas(PDO $conn, int $clienteId): array {
    $stmt = $conn->prepare(
        "SELECT DISTINCT m.* FROM mascota m
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_cliente_principal = :cliente OR md.id_cliente = :cliente
         ORDER BY m.nombre"
    );
    $stmt->execute([':cliente' => $clienteId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Devuelve una mascota asociada al cliente.
 */
function fetchClientMascota(PDO $conn, int $mascotaId, int $clienteId): ?array {
    $stmt = $conn->prepare(
        "SELECT DISTINCT m.* FROM mascota m
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_mascota = :mascota
           AND (m.id_cliente_principal = :cliente OR md.id_cliente = :cliente)
         LIMIT 1"
    );
    $stmt->execute([':mascota' => $mascotaId, ':cliente' => $clienteId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Validar CI (cédula de identidad)
 */
function isValidCI($ci) {
    return Security::isValidCI($ci);
}

/**
 * Validar teléfono
 */
function isValidPhone($phone) {
    return Security::isValidPhone($phone);
}

/**
 * Generar contraseña fuerte sugerida
 */
function suggestPassword() {
    return Security::generateStrongPassword();
}

/**
 * Calcular fortaleza de contraseña
 */
function getPasswordStrength($password) {
    $validation = Security::validatePassword($password);
    
    if (!$validation['valid']) {
        return ['strength' => 'weak', 'label' => 'Débil', 'color' => 'danger'];
    }
    
    $score = 0;
    
    // Longitud
    if (strlen($password) >= 12) $score++;
    if (strlen($password) >= 16) $score++;
    
    // Caracteres especiales
    if (preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/u', $password)) $score++;
    
    // Números y letras
    if (preg_match('/[0-9]/', $password) && preg_match('/[a-z]/', $password)) $score++;
    
    if ($score <= 1) {
        return ['strength' => 'weak', 'label' => 'Débil', 'color' => 'danger'];
    } else if ($score <= 2) {
        return ['strength' => 'medium', 'label' => 'Medio', 'color' => 'warning'];
    } else if ($score <= 3) {
        return ['strength' => 'strong', 'label' => 'Fuerte', 'color' => 'success'];
    } else {
        return ['strength' => 'very-strong', 'label' => 'Muy Fuerte', 'color' => 'success'];
    }
}

/**
 * Obtener initiales del nombre
 */
function getInitials($nombre, $apellido = '') {
    $initials = substr($nombre, 0, 1);
    if ($apellido) {
        $initials .= substr($apellido, 0, 1);
    }
    return strtoupper($initials);
}

/**
 * Truncar texto
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Obtener lista de roles para admin
 */
function getRolesList() {
    return [
        'admin' => 'Administrador',
        'groomer' => 'Groomer/Estilista',
        'recepcion' => 'Personal de Recepción',
        'cliente' => 'Cliente'
    ];
}

/**
 * Obtener lista de especializaciones para groomers
 */
function getGroomerSpecialties() {
    return [
        'baño' => 'Baño y Secado',
        'corte' => 'Corte de Pelo',
        'uñas' => 'Corte de Uñas',
        'oidos' => 'Limpieza de Oídos',
        'completo' => 'Servicio Completo',
        'spa' => 'Spa y Masajes'
    ];
}

/**
 * Obtener lista de especies de mascotas
 */
function getPetSpecies() {
    return [
        'perro' => 'Perro',
        'gato' => 'Gato',
        'conejo' => 'Conejo',
        'pajaro' => 'Pájaro',
        'hamster' => 'Hámster',
        'otro' => 'Otro'
    ];
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Obtener avatar placeholder
 */
function getAvatarURL($nombre = 'Usuario', $apellido = '') {
    $initials = getInitials($nombre, $apellido);
    return "https://ui-avatars.com/api/?name=$initials&background=random";
}

/**
 * Validar permisos para acción
 */
function canPerformAction($action, $userRole = null) {
    if (!$userRole && isset($_SESSION['rol'])) {
        $userRole = $_SESSION['rol'];
    }

    $permissions = [
        'crear_usuario' => ['admin'],
        'editar_usuario' => ['admin'],
        'eliminar_usuario' => ['admin'],
        'ver_reportes' => ['admin'],
        'gestionar_inventario' => ['admin', 'recepcion', 'groomer'],
        'crear_cita' => ['cliente', 'recepcion'],
        'calificar_servicio' => ['cliente'],
    ];

    return in_array($userRole, $permissions[$action] ?? []);
}

/**
 * Crear token CSRF y devolverlo en HTML
 */
function getCSRFField() {
    $token = Security::generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='$token'>";
}

/**
 * Obtener IP del cliente
 */
function getClientIP() {
    return Security::getClientIP();
}

/**
 * Log simple (para debugging)
 */
function logDebug($message, $data = null) {
    $msg = "[" . date('Y-m-d H:i:s') . "] $message";
    if ($data) {
        $msg .= " | " . json_encode($data);
    }
    error_log($msg);
}

function normalizeInventoryTerm($term) {
    $normalized = strtolower(trim($term));
    $normalized = str_replace(['_', '-', '.', ','], ' ', $normalized);
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    return $normalized;
}

function findInventoryProductByLabel($conn, $label) {
    $label = normalizeInventoryTerm($label);
    if (!$label) {
        return null;
    }

    $stmt = $conn->prepare("SELECT id_producto, nombre, stock_actual FROM producto WHERE estado_activo = 1 AND LOWER(nombre) LIKE :term ORDER BY stock_actual DESC LIMIT 1");
    $stmt->execute([':term' => "%$label%"]); 
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        return $product;
    }

    $tokens = array_filter(explode(' ', $label));
    if (!empty($tokens)) {
        $clauses = [];
        $params = [];
        foreach ($tokens as $token) {
            $clauses[] = "LOWER(nombre) LIKE ?";
            $params[] = "%$token%";
        }
        $sql = "SELECT id_producto, nombre, stock_actual FROM producto WHERE estado_activo = 1 AND " . implode(' AND ', $clauses) . " ORDER BY stock_actual DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            return $product;
        }
    }

    $stmt = $conn->prepare("SELECT p.id_producto, p.nombre, p.stock_actual FROM producto p JOIN variante_producto v ON v.id_producto = p.id_producto WHERE p.estado_activo = 1 AND LOWER(v.sku_variante) LIKE :term ORDER BY p.stock_actual DESC LIMIT 1");
    $stmt->execute([':term' => "%$label%"]); 
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        return $product;
    }

    foreach ($tokens as $token) {
        $stmt = $conn->prepare("SELECT p.id_producto, p.nombre, p.stock_actual FROM producto p JOIN variante_producto v ON v.id_producto = p.id_producto WHERE p.estado_activo = 1 AND LOWER(v.sku_variante) LIKE ? ORDER BY p.stock_actual DESC LIMIT 1");
        $stmt->execute(["%$token%"]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            return $product;
        }
    }

    return null;
}

function reserveInventoryForService($conn, $citaId, $servicioId, $usuarioId = null) {
    if (!$citaId || !$servicioId) {
        return;
    }

    $stmt = $conn->prepare("SELECT consumo_insumos FROM servicio WHERE id_servicio = ?");
    $stmt->execute([$servicioId]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$servicio || empty($servicio['consumo_insumos'])) {
        return;
    }

    $items = json_decode($servicio['consumo_insumos'], true);
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $label => $cantidadRaw) {
        $cantidad = max(1, intval(ceil(floatval($cantidadRaw))));
        if ($cantidad <= 0) {
            continue;
        }

        $product = findInventoryProductByLabel($conn, $label);
        if (!$product) {
            $stmt = $conn->prepare("INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje) VALUES (NULL, 'stock_critico', CONCAT('Producto no encontrado para reserva de cita #', ?, ' llave: ', ?))");
            $stmt->execute([$citaId, $label]);
            continue;
        }

        $remaining = $cantidad;
        while ($remaining > 0) {
            $stmt = $conn->prepare("SELECT id_inventario, cantidad_fisica, cantidad_reservada, cantidad_disponible FROM inventario WHERE id_producto = :producto AND estado_lote = 'activo' AND cantidad_disponible > 0 ORDER BY cantidad_disponible DESC LIMIT 1");
            $stmt->execute([':producto' => $product['id_producto']]);
            $lote = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lote) {
                break;
            }

            $take = min($remaining, intval($lote['cantidad_disponible']));
            $antesFisica = intval($lote['cantidad_fisica']);
            $antesReservada = intval($lote['cantidad_reservada']);
            $despuesReservada = $antesReservada + $take;

            $update = $conn->prepare("UPDATE inventario SET cantidad_reservada = cantidad_reservada + :cantidad WHERE id_inventario = :inv");
            $update->execute([':cantidad' => $take, ':inv' => $lote['id_inventario']]);

            $insertReserva = $conn->prepare("INSERT INTO reserva_inventario (id_inventario, id_cita, cantidad, estado_reserva) VALUES (:inv, :cita, :cantidad, 'activa')");
            $insertReserva->execute([':inv' => $lote['id_inventario'], ':cita' => $citaId, ':cantidad' => $take]);

            $stmtMov = $conn->prepare("INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues, cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, motivo, id_usuario_registra) VALUES (:inv, 'reserva', :cantidad, :antesFisica, :despuesFisica, :antesReservada, :despuesReservada, 'cita', :cita, 'Reserva de insumos para cita', :usuario)");
            $stmtMov->execute([
                ':inv' => $lote['id_inventario'],
                ':cantidad' => $take,
                ':antesFisica' => $antesFisica,
                ':despuesFisica' => $antesFisica,
                ':antesReservada' => $antesReservada,
                ':despuesReservada' => $despuesReservada,
                ':cita' => $citaId,
                ':usuario' => $usuarioId ?? 0
            ]);

            $remaining -= $take;
        }

        if ($remaining > 0) {
            $stmt = $conn->prepare("INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje) VALUES (NULL, 'stock_critico', CONCAT('Stock insuficiente para cita #', ?, ' producto: ', ?))");
            $stmt->execute([$citaId, $product['nombre']]);
        }
    }
}

function getPetDurationFactor($conn, $mascotaId) {
    $stmt = $conn->prepare("SELECT peso, tamano, temperamento, comportamiento FROM mascota WHERE id_mascota = ?");
    $stmt->execute([$mascotaId]);
    $mascota = $stmt->fetch(PDO::FETCH_ASSOC);
    $factor = 1.0;
    $extra = 0;

    if ($mascota) {
        $tamano = strtolower(trim($mascota['tamano'] ?? ''));
        $peso = floatval($mascota['peso'] ?? 0);
        if ($tamano === 'gigante' || $peso >= 35) {
            $factor = 1.3;
        } elseif ($tamano === 'grande' || $peso >= 20) {
            $factor = 1.15;
        } elseif ($tamano === 'mediano' || ($peso >= 10 && $peso < 20)) {
            $factor = 1.1;
        } else {
            $factor = 1.0;
        }

        $temperamento = strtolower(trim($mascota['temperamento'] ?? ''));
        $comportamiento = strtolower(trim($mascota['comportamiento'] ?? ''));
        if (str_contains($temperamento, 'nervioso') || str_contains($temperamento, 'agresivo') || str_contains($comportamiento, 'nervioso') || str_contains($comportamiento, 'agresivo')) {
            $extra += 15;
        }
    }

    return ['factor' => $factor, 'extra' => $extra];
}

function calculateServiceDuration($conn, $servicioId, $mascotaId) {
    $stmt = $conn->prepare("SELECT duracion_base_minutos FROM servicio WHERE id_servicio = ?");
    $stmt->execute([$servicioId]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$servicio) {
        return 30;
    }
    $info = getPetDurationFactor($conn, $mascotaId);
    $duracion = intval(ceil(($servicio['duracion_base_minutos'] ?? 30) * $info['factor'])) + $info['extra'];
    return max(15, $duracion);
}

function calculateTotalDurationForServices($conn, array $servicioIds, $mascotaId) {
    $total = 0;
    foreach ($servicioIds as $servicioId) {
        $total += calculateServiceDuration($conn, intval($servicioId), $mascotaId);
    }
    if (count($servicioIds) > 1) {
        $total += 10; // tiempo logístico adicional por múltiples servicios
    }
    return $total;
}

function reserveInventoryForServices($conn, $citaId, array $servicioIds, $usuarioId = null) {
    foreach ($servicioIds as $servicioId) {
        reserveInventoryForService($conn, $citaId, intval($servicioId), $usuarioId);
    }
}

function consumeServicesProducts($conn, $idFicha, $idCita, array $servicioIds, $mascotaId = null, $usuarioId = null) {
    if (!$idFicha || !$idCita || empty($servicioIds)) {
        return;
    }

    $factorInfo = ['factor' => 1.0, 'extra' => 0];
    if ($mascotaId) {
        $factorInfo = getPetDurationFactor($conn, $mascotaId);
    }

    $items = [];
    foreach ($servicioIds as $servicioId) {
        $stmt = $conn->prepare("SELECT consumo_insumos FROM servicio WHERE id_servicio = ?");
        $stmt->execute([intval($servicioId)]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$servicio || empty($servicio['consumo_insumos'])) {
            continue;
        }

        $servicioItems = json_decode($servicio['consumo_insumos'], true);
        if (!is_array($servicioItems)) {
            continue;
        }

        foreach ($servicioItems as $label => $cantidadRaw) {
            $cantidad = max(1, intval(ceil(floatval($cantidadRaw) * $factorInfo['factor'])));
            if ($cantidad <= 0) {
                continue;
            }
            if (!isset($items[$label])) {
                $items[$label] = 0;
            }
            $items[$label] += $cantidad;
        }
    }

    if (empty($items)) {
        releaseReservationsForCita($conn, $idCita, $usuarioId);
        return;
    }

    foreach ($items as $label => $cantidad) {
        $product = findInventoryProductByLabel($conn, $label);
        if (!$product) {
            $stmt = $conn->prepare("INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje) VALUES (NULL, 'stock_critico', CONCAT('No se encontró producto de consumo para ficha #', ?, ' etiqueta: ', ?))");
            $stmt->execute([$idFicha, $label]);
            continue;
        }

        if ($usuarioId !== null) {
            try { $conn->exec("SET @current_user = " . intval($usuarioId)); } catch (Exception $e) { }
        } else {
            try { $conn->exec("SET @current_user = NULL"); } catch (Exception $e) { }
        }

        $stmt = $conn->prepare("INSERT INTO uso_producto (id_ficha, id_producto, cantidad) VALUES (:ficha, :producto, :cantidad)");
        $stmt->execute([':ficha' => $idFicha, ':producto' => $product['id_producto'], ':cantidad' => $cantidad]);

        $updateProduct = $conn->prepare("UPDATE producto SET stock_actual = GREATEST(stock_actual - :cantidad, 0) WHERE id_producto = :producto");
        $updateProduct->execute([':cantidad' => $cantidad, ':producto' => $product['id_producto']]);
    }

    releaseReservationsForCita($conn, $idCita, $usuarioId);
}

function releaseReservationsForCita($conn, $idCita, $usuarioId = null) {
    $stmt = $conn->prepare("SELECT id_reserva, id_inventario, cantidad FROM reserva_inventario WHERE id_cita = :cita AND estado_reserva = 'activa'");
    $stmt->execute([':cita' => $idCita]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($reservas as $reserva) {
        // Obtener valores antes
        $stmtInv = $conn->prepare("SELECT cantidad_fisica, cantidad_reservada FROM inventario WHERE id_inventario = :inv");
        $stmtInv->execute([':inv' => $reserva['id_inventario']]);
        $before = $stmtInv->fetch(PDO::FETCH_ASSOC);
        $antesFisica = intval($before['cantidad_fisica'] ?? 0);
        $antesReservada = intval($before['cantidad_reservada'] ?? 0);

        $updateInv = $conn->prepare("UPDATE inventario SET cantidad_reservada = GREATEST(cantidad_reservada - :cantidad, 0) WHERE id_inventario = :inv");
        $updateInv->execute([':cantidad' => $reserva['cantidad'], ':inv' => $reserva['id_inventario']]);

        // Valores después
        $stmtInv2 = $conn->prepare("SELECT cantidad_fisica, cantidad_reservada FROM inventario WHERE id_inventario = :inv");
        $stmtInv2->execute([':inv' => $reserva['id_inventario']]);
        $after = $stmtInv2->fetch(PDO::FETCH_ASSOC);
        $despuesFisica = intval($after['cantidad_fisica'] ?? 0);
        $despuesReservada = intval($after['cantidad_reservada'] ?? 0);

        // Insertar movimiento documentando cambio de reserva (consumo de reserva)
        $stmtMov = $conn->prepare("INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues, cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, motivo, id_usuario_registra) VALUES (:inv, 'consumo_reserva', :cantidad, :antesFisica, :despuesFisica, :antesReservada, :despuesReservada, 'cita', :cita, 'Consumo de reserva para cierre de ficha', :user)");
        $stmtMov->execute([
            ':inv' => $reserva['id_inventario'],
            ':cantidad' => $reserva['cantidad'],
            ':antesFisica' => $antesFisica,
            ':despuesFisica' => $despuesFisica,
            ':antesReservada' => $antesReservada,
            ':despuesReservada' => $despuesReservada,
            ':cita' => $idCita,
            ':user' => $usuarioId ?? 0
        ]);

        $updateReserva = $conn->prepare("UPDATE reserva_inventario SET estado_reserva = 'consumida', fecha_liberacion = NOW() WHERE id_reserva = :id");
        $updateReserva->execute([':id' => $reserva['id_reserva']]);
    }
}

function consumeServiceProducts($conn, $idFicha, $idCita, $servicioId, $mascotaId = null, $usuarioId = null) {
    if (!$idFicha || !$servicioId) {
        return;
    }

    $stmt = $conn->prepare("SELECT consumo_insumos, factor_tamaño_raza FROM servicio WHERE id_servicio = ?");
    $stmt->execute([$servicioId]);
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$servicio || empty($servicio['consumo_insumos'])) {
        releaseReservationsForCita($conn, $idCita, $usuarioId);
        return;
    }

    $factor = 1.0;
    if ($mascotaId) {
        $stmt = $conn->prepare("SELECT peso, tamano FROM mascota WHERE id_mascota = ?");
        $stmt->execute([$mascotaId]);
        $mascota = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mascota) {
            $peso = floatval($mascota['peso'] ?? 0);
            $tamano = strtolower(trim($mascota['tamano'] ?? ''));
            if ($tamano === 'grande' || $peso >= 20) {
                $factor = 1.15;
            } elseif ($tamano === 'mediano' || ($peso >= 10 && $peso < 20)) {
                $factor = 1.1;
            } elseif ($tamano === 'gigante' || $peso >= 35) {
                $factor = 1.3;
            }
        }
    }

    $items = json_decode($servicio['consumo_insumos'], true);
    if (!is_array($items)) {
        releaseReservationsForCita($conn, $idCita, $usuarioId);
        return;
    }

    foreach ($items as $label => $cantidadRaw) {
        $cantidad = max(1, intval(ceil(floatval($cantidadRaw) * $factor)));
        if ($cantidad <= 0) {
            continue;
        }

        $product = findInventoryProductByLabel($conn, $label);
        if (!$product) {
            $stmt = $conn->prepare("INSERT INTO alerta_inventario (id_inventario, tipo_alerta, mensaje) VALUES (NULL, 'stock_critico', CONCAT('No se encontró producto de consumo para ficha #', ?, ' etiqueta: ', ?))");
            $stmt->execute([$idFicha, $label]);
            continue;
        }

        // Propagar usuario actual a la sesión MySQL para que triggers lo recojan si están preparados
        if ($usuarioId !== null) {
            $safeUser = intval($usuarioId);
            try { $conn->exec("SET @current_user = $safeUser"); } catch (Exception $e) { /* ignore */ }
        } else {
            try { $conn->exec("SET @current_user = NULL"); } catch (Exception $e) { /* ignore */ }
        }

        $stmt = $conn->prepare("INSERT INTO uso_producto (id_ficha, id_producto, cantidad) VALUES (:ficha, :producto, :cantidad)");
        $stmt->execute([':ficha' => $idFicha, ':producto' => $product['id_producto'], ':cantidad' => $cantidad]);

        $updateProduct = $conn->prepare("UPDATE producto SET stock_actual = GREATEST(stock_actual - :cantidad, 0) WHERE id_producto = :producto");
        $updateProduct->execute([':cantidad' => $cantidad, ':producto' => $product['id_producto']]);
    }

    // Liberar las reservas relacionadas con la cita y registrar los movimientos; pasar usuario para atribuir acciones
    releaseReservationsForCita($conn, $idCita, $usuarioId);
}

function generateOrderMessage($cartItems, $subtotal, $iva, $total) {
    $lines = ["Pedido Pet Spa:"];
    foreach ($cartItems as $item) {
        $lines[] = "- {$item['cantidad']} x {$item['nombre']} ({$item['precio']} c/u)";
    }
    $lines[] = "";
    $lines[] = "Subtotal: {$subtotal}";
    $lines[] = "IVA: {$iva}";
    $lines[] = "Total: {$total}";
    $lines[] = "Gracias por tu compra.";
    return implode("\n", $lines);
}
?>
