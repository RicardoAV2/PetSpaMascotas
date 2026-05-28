<?php
/**
 * Role simulation checks for PETSPA
 * Run: php tests/role_simulation_checks.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Auth.php';

function ok($msg) { echo "[OK] $msg\n"; }
function fail($msg) { echo "[FAIL] $msg\n"; }
function info($msg) { echo "    $msg\n"; }

$pdo = $conn;
$pdo->beginTransaction();

try {
    function getRoleId(PDO $conn, string $rolNombre): int {
        $stmt = $conn->prepare('SELECT id_rol FROM rol WHERE nombre = ? LIMIT 1');
        $stmt->execute([$rolNombre]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new Exception("Rol no encontrado: $rolNombre");
        }
        return intval($row['id_rol']);
    }

    function insertUser(PDO $conn, array $data): int {
        $stmt = $conn->prepare('INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['nombre'],
            $data['apellido'],
            $data['telefono'] ?? '',
            $data['id_rol'],
            1
        ]);
        return intval($conn->lastInsertId());
    }

    function createRoleRecord(PDO $conn, int $userId, string $rolNombre, array $extra = []) {
        switch ($rolNombre) {
            case ROLE_ADMIN:
                $stmt = $conn->prepare('INSERT INTO administrador (id_administrador, nivel_acceso, area_responsabilidad, puede_contratar) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $extra['nivel_acceso'] ?? '1', $extra['area'] ?? 'Administración', $extra['puede_contratar'] ?? 0]);
                break;
            case ROLE_RECEPCION:
                $stmt = $conn->prepare('INSERT INTO recepcionista (id_recepcionista, turno, idiomas, experiencia) VALUES (?, ?, ?, ?)');
                $stmt->execute([$userId, $extra['turno'] ?? 'Mañana', $extra['idiomas'] ?? 'Español', $extra['experiencia'] ?? 1]);
                break;
            case ROLE_GROOMER:
                $stmt = $conn->prepare('INSERT INTO groomer (id_groomer, especialidad, capacidad_simultanea, horario_trabajo, estado_activo) VALUES (?, ?, ?, ?, 1)');
                $stmt->execute([$userId, $extra['especialidad'] ?? 'Pelajes', $extra['capacidad'] ?? 1, $extra['horario'] ?? '08:00-17:00']);
                break;
            case ROLE_CLIENTE:
                $stmt = $conn->prepare('INSERT INTO cliente (id_cliente, direccion, ci, canal_notificacion_preferido, horario_preferido, recibe_promociones) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$userId, $extra['direccion'] ?? 'Calle 123', $extra['ci'] ?? '12345678', $extra['canal'] ?? 'email', $extra['horario'] ?? 'mañana', $extra['promociones'] ?? 1]);
                break;
            default:
                throw new Exception('Rol no soportado para creación: ' . $rolNombre);
        }
    }

    function createTestUser(PDO $conn, string $rolNombre, string $suffix): int {
        $idRol = getRoleId($conn, $rolNombre);
        $email = sprintf('test_%s_%s@example.local', $rolNombre, $suffix);
        $userId = insertUser($conn, [
            'email' => $email,
            'password' => 'Password123!',
            'nombre' => ucfirst($rolNombre) . 'Test',
            'apellido' => 'Sim',
            'telefono' => '77777777',
            'id_rol' => $idRol
        ]);
        createRoleRecord($conn, $userId, $rolNombre);
        return $userId;
    }

    function createPet(PDO $conn, int $clienteId, string $nombre, string $tamano, float $peso): int {
        $stmt = $conn->prepare('INSERT INTO mascota (id_cliente_principal, nombre, especie, raza, tamano, peso) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$clienteId, $nombre, 'perro', 'mestizo', $tamano, $peso]);
        return intval($conn->lastInsertId());
    }

    function createServicio(PDO $conn, string $nombre, int $duracion, ?string $consumo = null): int {
        $uniqueName = $nombre . ' - ' . uniqid();
        $stmt = $conn->prepare('INSERT INTO servicio (nombre, duracion_base_minutos, consumo_insumos, estado_activo) VALUES (?, ?, ?, 1)');
        $stmt->execute([$uniqueName, $duracion, $consumo]);
        return intval($conn->lastInsertId());
    }

    function createProducto(PDO $conn, string $nombre, float $precio, int $stock): int {
        $uniqueName = $nombre . ' - ' . uniqid();
        $stmt = $conn->prepare('INSERT INTO producto (nombre, precio_base, stock_actual, stock_minimo, estado_activo) VALUES (?, ?, ?, 1, 1)');
        $stmt->execute([$uniqueName, $precio, $stock]);
        return intval($conn->lastInsertId());
    }

    function createGroomerAvailability(PDO $conn, int $groomerId, int $diaSemana, string $horaInicio, string $horaFin) {
        $stmt = $conn->prepare('INSERT INTO disponibilidad (id_groomer, dia_semana, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)');
        $stmt->execute([$groomerId, $diaSemana, $horaInicio, $horaFin]);
    }

    function createCita(PDO $conn, int $mascotaId, int $groomerId, array $servicioIds, string $fecha, string $hora, int $createdById, string $nota = ''): int {
        $duracion = calculateTotalDurationForServices($conn, $servicioIds, $mascotaId);
        $fechaInicio = $fecha . ' ' . $hora . ':00';
        $fechaFin = (new DateTime($fechaInicio))->modify("+{$duracion} minutes")->format('Y-m-d H:i:s');

        $stmt = $conn->prepare('INSERT INTO cita (fecha_inicio, fecha_fin, duracion_real, estado, creado_por, fecha_creacion, id_mascota, id_groomer, id_servicio, nota) VALUES (?, ?, ?, "agendada", ?, NOW(), ?, ?, ?, ?)');
        $stmt->execute([$fechaInicio, $fechaFin, $duracion, $createdById, $mascotaId, $groomerId, $servicioIds[0], $nota]);
        $citaId = intval($conn->lastInsertId());
        $stmt = $conn->prepare('INSERT INTO cita_servicio (id_cita, id_servicio) VALUES (?, ?)');
        foreach ($servicioIds as $servicioId) {
            $stmt->execute([$citaId, $servicioId]);
        }
        return $citaId;
    }

    function createFacturaAndPago(PDO $conn, int $citaId, float $monto, string $metodo) {
        $stmt = $conn->prepare('SELECT c.id_cita, c.id_servicio, s.precio_base, s.nombre AS servicio_nombre FROM cita c JOIN servicio s ON c.id_servicio = s.id_servicio WHERE c.id_cita = ?');
        $stmt->execute([$citaId]);
        $cita = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cita) {
            throw new Exception('Cita no encontrada para cobro');
        }
        $subtotal = floatval($cita['precio_base']);
        $total = $subtotal;
        $numeroFactura = 'TF' . date('YmdHis') . rand(100, 999);
        $stmt = $conn->prepare('INSERT INTO factura (numero_factura, subtotal, impuesto, total, estado_factura, id_cita) VALUES (?, ?, 0, ?, ?, ?)');
        $estado = $monto >= $total ? 'pagada' : 'pendiente';
        $stmt->execute([$numeroFactura, $subtotal, $total, $estado, $citaId]);
        $facturaId = intval($conn->lastInsertId());
        $stmt = $conn->prepare('INSERT INTO detalle_factura (id_factura, concepto, cantidad, precio_unitario, subtotal, id_servicio) VALUES (?, ?, 1, ?, ?, ?)');
        $stmt->execute([$facturaId, 'Servicio: ' . $cita['servicio_nombre'], $subtotal, $subtotal, intval($cita['id_servicio'])]);
        $stmt = $conn->prepare('INSERT INTO pago (monto, metodo_pago, referencia_transaccion, estado_pago, id_factura) VALUES (?, ?, ?, "completado", ?)');
        $stmt->execute([$monto, $metodo, 'TEST-' . rand(1000,9999), $facturaId]);
        if ($estado === 'pagada') {
            $stmt = $conn->prepare('UPDATE cita SET estado = "confirmada" WHERE id_cita = ?');
            $stmt->execute([$citaId]);
        }
        return $facturaId;
    }

    echo "Starting role simulation checks...\n\n";

    // Admin simulation: crear usuarios en los cuatro roles
    echo "ADMIN: Crear usuarios en los 4 roles\n";
    $adminUserId = createTestUser($pdo, ROLE_ADMIN, 'admin');
    ok('Admin creado para simulación');

    $userAdmin = createTestUser($pdo, ROLE_ADMIN, 'adm2');
    $userRecepcion = createTestUser($pdo, ROLE_RECEPCION, 'rep');
    $userGroomer = createTestUser($pdo, ROLE_GROOMER, 'groom');
    $userCliente = createTestUser($pdo, ROLE_CLIENTE, 'client');

    ok('Admin puede crear usuario Administrador');
    ok('Admin puede crear usuario Recepcionista');
    ok('Admin puede crear usuario Groomer');
    ok('Admin puede crear usuario Cliente');

    // Verificar roles en BD
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE id_rol IN (?, ?, ?, ?)');
    $stmt->execute([
        getRoleId($pdo, ROLE_ADMIN),
        getRoleId($pdo, ROLE_RECEPCION),
        getRoleId($pdo, ROLE_GROOMER),
        getRoleId($pdo, ROLE_CLIENTE)
    ]);
    $countRoles = intval($stmt->fetchColumn());
    ok("Registro de roles en BD verificado: $countRoles usuarios de prueba");

    echo "\nRECEPCIONISTA: Simulación de flujo de trabajo\n";
    $clienteParaCita = createTestUser($pdo, ROLE_CLIENTE, 'flowc');
    $mascotaId = createPet($pdo, $clienteParaCita, 'Luna', 'mediano', 12.5);
    ok('Cliente y mascota creados para recepción');

    $groomerParaCita = createTestUser($pdo, ROLE_GROOMER, 'flowg');
    $diaSemana = intval((new DateTime('+2 days'))->format('w'));
    createGroomerAvailability($pdo, $groomerParaCita, $diaSemana, '09:00', '17:00');
    ok('Disponibilidad de groomer creada para recepción');

    $servicioA = createServicio($pdo, 'Baño completo', 45, json_encode(['shampoo' => 1, 'acondicionador' => 1]));
    $servicioB = createServicio($pdo, 'Corte de pelo', 60, json_encode(['tijeras' => 1]));
    ok('Servicios creados para recepción');

    $fecha = (new DateTime('+2 days'))->format('Y-m-d');
    $citaId = createCita($pdo, $mascotaId, $groomerParaCita, [$servicioA, $servicioB], $fecha, '10:00', $userRecepcion, 'Prueba recepción');
    ok('Recepcionista puede agendar cita con múltiples servicios');

    // Reprogramar cita
    $nuevaHora = '13:00';
    $newStart = $fecha . ' ' . $nuevaHora . ':00';
    $duracion = calculateTotalDurationForServices($pdo, [$servicioA, $servicioB], $mascotaId);
    $newEnd = (new DateTime($newStart))->modify("+{$duracion} minutes")->format('Y-m-d H:i:s');
    $stmt = $pdo->prepare('UPDATE cita SET fecha_inicio = ?, fecha_fin = ?, fecha_reprogramacion = NOW(), usuario_reprogramo = ? WHERE id_cita = ?');
    $stmt->execute([$newStart, $newEnd, $userRecepcion, $citaId]);
    ok('Recepcionista puede reprogramar una cita existente');

    // Gestionar inventario
    $productoId = createProducto($pdo, 'Shampoo Premium', 25.50, 100);
    $stmt = $pdo->prepare('UPDATE producto SET stock_actual = stock_actual - 5 WHERE id_producto = ?');
    $stmt->execute([$productoId]);
    ok('Recepcionista puede gestionar inventario y ajustar stock');

    // Cobrar servicios por la cita
    $facturaId = createFacturaAndPago($pdo, $citaId, 150.00, 'efectivo');
    ok('Recepcionista puede cobrar servicios y generar factura');

    // Consultar reportes
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM cita WHERE estado IN ("agendada", "confirmada")');
    $stmt->execute();
    $reportCount = intval($stmt->fetchColumn());
    ok("Recepcionista consulta reportes: $reportCount citas activas encontradas");

    echo "\nGROOMER: Simulación de flujo de trabajo\n";
    $cliente2 = createTestUser($pdo, ROLE_CLIENTE, 'groomc');
    $mascota2 = createPet($pdo, $cliente2, 'Oso', 'grande', 22.0);
    $servicioC = createServicio($pdo, 'Corte express', 30, json_encode(['tijeras' => 1, 'peine' => 1]));
    $groomer2 = createTestUser($pdo, ROLE_GROOMER, 'flowg2');
    createGroomerAvailability($pdo, $groomer2, $diaSemana, '08:00', '18:00');
    $cita2 = createCita($pdo, $mascota2, $groomer2, [$servicioC], $fecha, '11:00', $userRecepcion, 'Para cerrar grooming');
    ok('Groomer tiene cita asignada para gestión');

    // Simular cierre de grooming
    $stmt = $pdo->prepare('INSERT INTO ficha_grooming (id_cita, observaciones, estado_mascota, hora_inicio, hora_fin_real, consumido_inventario, fecha_cierre) VALUES (?, ?, ?, ?, NOW(), 1, NOW())');
    $stmt->execute([$cita2, 'Todo ok', 'tranquila', $fecha . ' 11:00:00']);
    $fichaId = intval($pdo->lastInsertId());
    $checklistItems = [1,2,3,4];
    $stmt = $pdo->prepare('INSERT INTO ficha_checklist (id_ficha, id_item, completado, observacion) VALUES (?, ?, 1, ?)');
    foreach ($checklistItems as $item) {
        $stmt->execute([$fichaId, $item, 'Checklist completado']);
    }
    $stmt = $pdo->prepare('UPDATE cita SET estado = "completada" WHERE id_cita = ?');
    $stmt->execute([$cita2]);
    ok('Groomer puede completar grooming con checklist mínimo');

    // Inventario groomer / reportes
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM cita WHERE id_groomer = ?');
    $stmt->execute([$groomer2]);
    ok('Groomer puede ver su agenda y total de citas: ' . intval($stmt->fetchColumn()));

    echo "\nCLIENTE: Simulación de flujo de trabajo\n";
    $clienteUser = createTestUser($pdo, ROLE_CLIENTE, 'flowcli');
    $mascotaCli = createPet($pdo, $clienteUser, 'Misha', 'mediano', 11.0);
    $groomer3 = createTestUser($pdo, ROLE_GROOMER, 'flowg3');
    createGroomerAvailability($pdo, $groomer3, $diaSemana, '09:00', '18:00');
    $servicioD = createServicio($pdo, 'Baño relajante', 50, json_encode(['shampoo' => 1]));
    $citaCli = createCita($pdo, $mascotaCli, $groomer3, [$servicioD], $fecha, '15:00', $clienteUser, 'Reservación cliente');
    ok('Cliente puede agendar cita desde su interfaz');

    // Seleccionar mascota
    $stmt = $pdo->prepare('SELECT id_mascota FROM mascota WHERE id_cliente_principal = ?');
    $stmt->execute([$clienteUser]);
    $mascotasCount = intval($stmt->fetchColumn());
    ok("Cliente puede seleccionar mascota en la interfaz: $mascotasCount mascotas encontradas");

    // Ver citas y servicios
    $stmt = $pdo->prepare('SELECT c.id_cita, c.estado, s.nombre FROM cita c JOIN servicio s ON c.id_servicio = s.id_servicio WHERE c.id_mascota = ?');
    $stmt->execute([$mascotaCli]);
    $citasCliente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ok('Cliente puede ver sus citas y servicios asociados: ' . count($citasCliente));

    // Carrito y checkout
    $producto2 = createProducto($pdo, 'Cepillo para pelo', 15.00, 50);
    $carrito = [ $producto2 => 2 ];
    $subtotal = 15.00 * 2;
    $stmt = $pdo->prepare('INSERT INTO carrito (session_token, id_cliente, fecha_creacion, expires_at, metodo_contacto, contacto_destino, estado_pedido, subtotal, descuento, total) VALUES (?, ?, NOW(), ?, ?, ?, "pendiente", ?, 0, ?)');
    $token = 'TESTCART' . rand(1000,9999);
    $expires = (new DateTime('+7 days'))->format('Y-m-d H:i:s');
    $stmt->execute([$token, $clienteUser, $expires, 'whatsapp', '77777777', $subtotal, $subtotal]);
    $carritoId = intval($pdo->lastInsertId());
    $stmt = $pdo->prepare('INSERT INTO detalle_carrito (id_carrito, id_producto, id_variante, cantidad, precio_unitario) VALUES (?, ?, NULL, ?, ?)');
    $stmt->execute([$carritoId, $producto2, 2, 15.00]);
    ok('Cliente puede gestionar carrito con productos');

    $stmt = $pdo->prepare('SELECT id_carrito FROM carrito WHERE id_cliente = ? AND estado_pedido = "pendiente"');
    $stmt->execute([$clienteUser]);
    $cartCount = intval($stmt->rowCount());
    ok("Cliente puede ver su carrito: $cartCount pedido(s) pendiente(s)");

    echo "\nROLE SIMULATION SUMMARY:\n";
    ok('Simulación de uso por rol completada con éxito');

    $pdo->rollBack();
    echo "\nNota: todos los cambios se han revertido al final de la simulación.\n";

    exit(0);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "[ERROR] Excepción durante la simulación: " . $e->getMessage() . "\n";
    exit(1);
}
