<?php
/**
 * API: GESTIÓN DE DISPONIBILIDAD Y SLOTS DE AGENDA
 * =================================================
 * Calcula slots disponibles considerando:
 * - Horarios de trabajo del groomer
 * - Bloqueos (feriados, vacaciones, mantenimiento)
 * - Citas existentes
 * - Capacidad del groomer
 * - Duración del servicio (ajustada por tamaño de mascota)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';

Auth::setConnection($conn);
Middleware::checkSessionTimeout();

// Solo admin, recepcion y groomer pueden acceder
$allowedRoles = ['admin', 'recepcion', 'groomer'];
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Obtener disponibilidad y slots
            $action = $_GET['action'] ?? 'slots';
            
            switch ($action) {
                case 'groomers':
                    echo json_encode(getGroomersDisponibles($conn));
                    break;
                case 'horarios':
                    $id_groomer = $_GET['id_groomer'] ?? null;
                    $dia = $_GET['dia'] ?? null;
                    echo json_encode(getHorariosGroomer($conn, $id_groomer, $dia));
                    break;
                case 'slots':
                    $fecha = $_GET['fecha'] ?? date('Y-m-d');
                    $id_groomer = $_GET['id_groomer'] ?? null;
                    $id_servicio = $_GET['id_servicio'] ?? null;
                    $id_mascota = $_GET['id_mascota'] ?? null;
                    echo json_encode(getSlotsDisponibles($conn, $fecha, $id_groomer, $id_servicio, $id_mascota));
                    break;
                case 'disponibilidad_completa':
                    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
                    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d', strtotime('+7 days'));
                    echo json_encode(getDisponibilidadRango($conn, $fecha_inicio, $fecha_fin));
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            break;
            
        case 'POST':
            // Crear/modificar disponibilidad o bloqueo
            $data = json_decode(file_get_contents('php://input'), true);
            $action = $data['action'] ?? 'crear_slot';
            
            switch ($action) {
                case 'crear_disponibilidad':
                    echo json_encode(crearDisponibilidad($conn, $data));
                    break;
                case 'crear_bloqueo':
                    echo json_encode(crearBloqueo($conn, $data));
                    break;
                case 'eliminar_bloqueo':
                    echo json_encode(eliminarBloqueo($conn, $data['id_bloqueo']));
                    break;
                case 'configurar_horario_trabajo':
                    echo json_encode(configurarHorarioTrabajo($conn, $data));
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
}

/**
 * Obtiene lista de groomers disponibles para trabajar
 */
function getGroomersDisponibles($conn) {
    $stmt = $conn->prepare("
        SELECT g.id_groomer, u.nombre, u.apellido, g.especialidad, 
               g.capacidad_simultanea, g.estado_activo, g.horario_trabajo
        FROM groomer g
        JOIN usuario u ON g.id_groomer = u.id_usuario
        WHERE g.estado_activo = 1 AND u.estado = 1
        ORDER BY u.nombre ASC
    ");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['success' => true, 'data' => $groomers];
}

/**
 * Obtiene horarios de trabajo de un groomer específico
 */
function getHorariosGroomer($conn, $id_groomer, $dia = null) {
    if ($id_groomer) {
        $sql = "
            SELECT d.*, 
                   CASE d.dia_semana 
                       WHEN 0 THEN 'Domingo'
                       WHEN 1 THEN 'Lunes'
                       WHEN 2 THEN 'Martes'
                       WHEN 3 THEN 'Miércoles'
                       WHEN 4 THEN 'Jueves'
                       WHEN 5 THEN 'Viernes'
                       WHEN 6 THEN 'Sábado'
                   END as dia_nombre
            FROM disponibilidad d
            WHERE d.id_groomer = ?
        ";
        $params = [$id_groomer];
        
        if ($dia !== null) {
            $sql .= " AND d.dia_semana = ?";
            $params[] = $dia;
        }
        
        $sql .= " ORDER BY d.dia_semana ASC, d.hora_inicio ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    } else {
        // Obtener horarios por defecto si no hay específicos
        $stmt = $conn->prepare("
            SELECT * FROM disponibilidad 
            ORDER BY dia_semana ASC, hora_inicio ASC
        ");
        $stmt->execute();
    }
    
    return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
}

/**
 * Obtiene slots disponibles para una fecha específica
 * Considera: horarios, bloqueos, citas existentes, duración del servicio
 */
function getSlotsDisponibles($conn, $fecha, $id_groomer = null, $id_servicio = null, $id_mascota = null) {
    // Validar fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        return ['success' => false, 'message' => 'Fecha inválida'];
    }
    
    $dia_semana = date('w', strtotime($fecha)); // 0=Domingo, 6=Sábado
    
    // Obtener duración del servicio si se proporciona
    $duracion_minutos = 60; //默认值
    $precio_ajustado = 0;
    
    if ($id_servicio) {
        $stmt = $conn->prepare("SELECT duracion_base_minutos, precio_base FROM servicio WHERE id_servicio = ? AND estado_activo = 1");
        $stmt->execute([$id_servicio]);
        $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($servicio) {
            $duracion_minutos = $servicio['duracion_base_minutos'];
            $precio_ajustado = $servicio['precio_base'];
        }
    }
    
    // Ajustar duración por tamaño de mascota
    if ($id_mascota) {
        $stmt = $conn->prepare("SELECT tamano, temperamento FROM mascota WHERE id_mascota = ?");
        $stmt->execute([$id_mascota]);
        $mascota = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mascota) {
            $factor_duracion = calcularFactorDuracion($mascota['tamano'], $mascota['temperamento']);
            $duracion_minutos = ceil($duracion_minutos * $factor_duracion);
            
            // Ajustar precio según factor
            $precio_ajustado = $precio_ajustado * $factor_duracion;
        }
    }
    
    // Obtener groomers disponibles para ese día
    $sql_groomers = "
        SELECT g.id_groomer, u.nombre, u.apellido, g.capacidad_simultanea
        FROM disponibilidad d
        JOIN groomer g ON d.id_groomer = g.id_groomer
        JOIN usuario u ON g.id_groomer = u.id_usuario
        WHERE d.dia_semana = ? 
          AND g.estado_activo = 1 
          AND u.estado = 1
    ";
    $params_groomers = [$dia_semana];
    
    if ($id_groomer) {
        $sql_groomers .= " AND d.id_groomer = ?";
        $params_groomers[] = $id_groomer;
    }
    
    $sql_groomers .= " GROUP BY g.id_groomer ORDER BY u.nombre ASC";
    
    $stmt = $conn->prepare($sql_groomers);
    $stmt->execute($params_groomers);
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $slots_disponibles = [];
    
    foreach ($groomers as $groomer) {
        // Obtener horarios de trabajo para este día
        $stmt = $conn->prepare("
            SELECT hora_inicio, hora_fin, intervalo_descanso 
            FROM disponibilidad 
            WHERE id_groomer = ? AND dia_semana = ?
        ");
        $stmt->execute([$groomer['id_groomer'], $dia_semana]);
        $horario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$horario) continue;
        
        // Obtener bloqueos para este día
        $stmt = $conn->prepare("
            SELECT fecha_inicio, fecha_fin, tipo_bloqueo, motivo
            FROM bloqueo_agenda
            WHERE (id_groomer = ? OR id_groomer IS NULL)
              AND fecha_inicio <= ? 
              AND fecha_fin >= ?
        ");
        $stmt->execute([
            $groomer['id_groomer'], 
            $fecha . ' 23:59:59',
            $fecha . ' 00:00:00'
        ]);
        $bloqueos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener citas existentes para este día y groomer
        $stmt = $conn->prepare("
            SELECT fecha_inicio, fecha_fin, id_mascota
            FROM cita
            WHERE id_groomer = ?
              AND DATE(fecha_inicio) = ?
              AND estado NOT IN ('cancelada', 'no_asistio')
        ");
        $stmt->execute([$groomer['id_groomer'], $fecha]);
        $citas_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular slots disponibles
        $slots = generarSlots(
            $horario['hora_inicio'], 
            $horario['hora_fin'],
            $duracion_minutos,
            $bloqueos,
            $citas_existentes,
            $groomer['capacidad_simultanea'],
            $fecha
        );
        
        if (!empty($slots)) {
            $slots_disponibles[] = [
                'groomer' => [
                    'id' => $groomer['id_groomer'],
                    'nombre' => $groomer['nombre'] . ' ' . $groomer['apellido'],
                    'capacidad' => $groomer['capacidad_simultanea']
                ],
                'duracion_servicio' => $duracion_minutos,
                'precio_ajustado' => round($precio_ajustado, 2),
                'slots' => $slots
            ];
        }
    }
    
    return [
        'success' => true,
        'data' => [
            'fecha' => $fecha,
            'dia_semana' => $dia_semana,
            'duracion_calculada' => $duracion_minutos,
            'groomers_disponibles' => $slots_disponibles
        ]
    ];
}

/**
 * Calcula el factor de duración según tamaño y temperamento de la mascota
 */
function calcularFactorDuracion($tamano, $temperamento) {
    // Factores por tamaño
    $factores_tamano = [
        'pequeño' => 1.0,
        'mediano' => 1.10,
        'grande' => 1.15,
        'gigante' => 1.30,
        'pequeno' => 1.0,
        'mediana' => 1.10,
        'grande' => 1.15,
        'gigante' => 1.30
    ];
    
    // Factores por temperamento
    $factores_temperamento = [
        'tranquilo' => 1.0,
        'nervioso' => 1.40,
        'agresivo' => 1.40,
        'inquieto' => 1.20
    ];
    
    $factor_tamano = $factores_tamano[strtolower($tamano)] ?? 1.0;
    $factor_temperamento = $factores_temperamento[strtolower($temperamento)] ?? 1.0;
    
    // Tomar el factor mayor entre tamaño y temperamento
    return max($factor_tamano, $factor_temperamento);
}

/**
 * Genera slots disponibles para un groomer
 */
function generarSlots($hora_inicio, $hora_fin, $duracion_minutos, $bloqueos, $citas, $capacidad, $fecha) {
    $slots = [];
    $intervalo = 15; // Intervalo de 15 minutos entre slots
    
    $inicio = strtotime($fecha . ' ' . $hora_inicio);
    $fin = strtotime($fecha . ' ' . $hora_fin);
    
    $slot_actual = $inicio;
    
    while ($slot_actual + ($duracion_minutos * 60) <= $fin) {
        $slot_fin = $slot_actual + ($duracion_minutos * 60);
        
        // Verificar si el slot está bloqueado
        $esta_bloqueado = false;
        foreach ($bloqueos as $bloqueo) {
            $bloqueo_inicio = strtotime($bloqueo['fecha_inicio']);
            $bloqueo_fin = strtotime($bloqueo['fecha_fin']);
            
            if ($slot_actual < $bloqueo_fin && $slot_fin > $bloqueo_inicio) {
                $esta_bloqueado = true;
                break;
            }
        }
        
        // Verificar si hay citas solapadas
        if (!$esta_bloqueado) {
            $citas_en_slot = 0;
            foreach ($citas as $cita) {
                $cita_inicio = strtotime($cita['fecha_inicio']);
                $cita_fin = strtotime($cita['fecha_fin']);
                
                if ($slot_actual < $cita_fin && $slot_fin > $cita_inicio) {
                    $citas_en_slot++;
                }
            }
            
            // Si hay capacidad disponible, agregar slot
            if ($citas_en_slot < $capacidad) {
                $slots[] = [
                    'hora' => date('H:i', $slot_actual),
                    'disponible' => true,
                    'capacidad_restante' => $capacidad - $citas_en_slot
                ];
            }
        }
        
        $slot_actual += $intervalo * 60; // Avanzar al siguiente slot
    }
    
    return $slots;
}

/**
 * Obtiene disponibilidad en un rango de fechas (para calendario)
 */
function getDisponibilidadRango($conn, $fecha_inicio, $fecha_fin) {
    $stmt = $conn->prepare("
        SELECT 
            d.id_groomer,
            u.nombre,
            u.apellido,
            GROUP_CONCAT(d.dia_semana) as dias_laborales
        FROM disponibilidad d
        JOIN groomer g ON d.id_groomer = g.id_groomer
        JOIN usuario u ON g.id_groomer = u.id_usuario
        WHERE g.estado_activo = 1 AND u.estado = 1
        GROUP BY d.id_groomer
    ");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $disponibilidad = [];
    
    foreach ($groomers as $groomer) {
        $dias_laborales = explode(',', $groomer['dias_laborales']);
        
        // Generar fechas disponibles
        $fechas = [];
        $fecha_actual = strtotime($fecha_inicio);
        $fecha_fin_ts = strtotime($fecha_fin);
        
        while ($fecha_actual <= $fecha_fin_ts) {
            $dia_semana = date('w', $fecha_actual);
            
            if (in_array($dia_semana, $dias_laborales)) {
                $fecha_str = date('Y-m-d', $fecha_actual);
                
                // Contar citas para ese día
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total
                    FROM cita
                    WHERE id_groomer = ?
                      AND DATE(fecha_inicio) = ?
                      AND estado NOT IN ('cancelada', 'no_asistio')
                ");
                $stmt->execute([$groomer['id_groomer'], $fecha_str]);
                $citas_count = $stmt->fetch()['total'];
                
                // Contar bloqueos
                $stmt = $conn->prepare("
                    SELECT COUNT(*) as total
                    FROM bloqueo_agenda
                    WHERE id_groomer = ?
                      AND fecha_inicio <= ?
                      AND fecha_fin >= ?
                ");
                $stmt->execute([
                    $groomer['id_groomer'],
                    $fecha_str . ' 23:59:59',
                    $fecha_str . ' 00:00:00'
                ]);
                $bloqueos_count = $stmt->fetch()['total'];
                
                $fechas[] = [
                    'fecha' => $fecha_str,
                    'disponible' => $citas_count == 0 && $bloqueos_count == 0,
                    'citas' => $citas_count,
                    'bloqueado' => $bloqueos_count > 0
                ];
            }
            
            $fecha_actual = strtotime('+1 day', $fecha_actual);
        }
        
        $disponibilidad[] = [
            'groomer' => [
                'id' => $groomer['id_groomer'],
                'nombre' => $groomer['nombre'] . ' ' . $groomer['apellido']
            ],
            'fechas' => $fechas
        ];
    }
    
    return [
        'success' => true,
        'data' => $disponibilidad
    ];
}

/**
 * Crea disponibilidad para un groomer
 */
function crearDisponibilidad($conn, $data) {
    // Validar datos
    if (!isset($data['id_groomer']) || !isset($data['dia_semana'])) {
        return ['success' => false, 'message' => 'Datos incompletos'];
    }
    
    $stmt = $conn->prepare("
        INSERT INTO disponibilidad (id_groomer, dia_semana, hora_inicio, hora_fin, intervalo_descanso)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([
            $data['id_groomer'],
            $data['dia_semana'],
            $data['hora_inicio'] ?? '09:00:00',
            $data['hora_fin'] ?? '18:00:00',
            $data['intervalo_descanso'] ?? null
        ]);
        
        return [
            'success' => true,
            'message' => 'Disponibilidad creada exitosamente',
            'id' => $conn->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al crear disponibilidad'];
    }
}

/**
 * Crea un bloqueo de agenda
 */
function crearBloqueo($conn, $data) {
    if (!isset($data['fecha_inicio']) || !isset($data['fecha_fin'])) {
        return ['success' => false, 'message' => 'Fechas requeridas'];
    }
    
    $stmt = $conn->prepare("
        INSERT INTO bloqueo_agenda (id_groomer, fecha_inicio, fecha_fin, motivo, tipo_bloqueo)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([
            $data['id_groomer'] ?? null, // null = bloqueo global
            $data['fecha_inicio'],
            $data['fecha_fin'],
            $data['motivo'] ?? 'Bloqueo manual',
            $data['tipo_bloqueo'] ?? 'ausencia'
        ]);
        
        return [
            'success' => true,
            'message' => 'Bloqueo creado exitosamente',
            'id' => $conn->lastInsertId()
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al crear bloqueo'];
    }
}

/**
 * Elimina un bloqueo
 */
function eliminarBloqueo($conn, $id_bloqueo) {
    $stmt = $conn->prepare("DELETE FROM bloqueo_agenda WHERE id_bloqueo = ?");
    
    try {
        $stmt->execute([$id_bloqueo]);
        return ['success' => true, 'message' => 'Bloqueo eliminado'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al eliminar bloqueo'];
    }
}

/**
 * Configura el horario de trabajo de un groomer (formato JSON)
 */
function configurarHorarioTrabajo($conn, $data) {
    if (!isset($data['id_groomer'])) {
        return ['success' => false, 'message' => 'Groomer requerido'];
    }
    
    $horario_json = json_encode($data['horario'] ?? []);
    
    $stmt = $conn->prepare("
        UPDATE groomer 
        SET horario_trabajo = ? 
        WHERE id_groomer = ?
    ");
    
    try {
        $stmt->execute([$horario_json, $data['id_groomer']]);
        return ['success' => true, 'message' => 'Horario configurado'];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return ['success' => false, 'message' => 'Error al configurar horario'];
    }
}
