<?php
session_start();
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/Logger.php";
require_once "../../core/helpers.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

header('Content-Type: application/json');

$userId = Auth::getCurrentUser()['id'];
$groomerId = intval($_GET['groomer_id'] ?? 0);
$mascotaId = intval($_GET['mascota_id'] ?? 0);
$servicioIds = [];
if (!empty($_GET['servicio_ids'])) {
    if (is_array($_GET['servicio_ids'])) {
        $servicioIds = array_map('intval', $_GET['servicio_ids']);
    } else {
        $servicioIds = array_map('intval', explode(',', $_GET['servicio_ids']));
    }
} elseif (!empty($_GET['servicio_id'])) {
    $servicioIds = [intval($_GET['servicio_id'])];
}
$servicioIds = array_filter($servicioIds, fn($id) => $id > 0);
$fecha = trim($_GET['fecha'] ?? '');

function sanitizeDate($value) {
    if (DateTime::createFromFormat('Y-m-d', $value)) {
        return $value;
    }
    return null;
}

/*function getDayOfWeek($date) {
    $dt = new DateTime($date);
    return intval($dt->format('w')); // 0=domingo
}*/

function getDayOfWeek($date) {
    $dt = new DateTime($date);
    return intval($dt->format('w')); // 0=domingo, 1=lunes, ..., 6=sábado
}

function getServiceDuration($conn, array $servicioIds, $mascotaId) {
    if (empty($servicioIds)) {
        return 60;
    }

    return calculateTotalDurationForServices($conn, $servicioIds, $mascotaId);
}

function isBlocked($conn, $groomerId, $dateTimeStart, $dateTimeEnd) {
    $stmt = $conn->prepare("SELECT 1 FROM bloqueo_agenda WHERE (id_groomer = :groomer OR id_groomer IS NULL) AND fecha_inicio <= :end AND fecha_fin >= :start LIMIT 1");
    $stmt->execute([':groomer' => $groomerId, ':start' => $dateTimeStart, ':end' => $dateTimeEnd]);
    return (bool)$stmt->fetch();
}

function hasConflict($conn, $groomerId, $start, $end) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cita WHERE id_groomer = :groomer AND estado NOT IN ('cancelada', 'completada') AND ((fecha_inicio <= :start AND fecha_fin > :start) OR (fecha_inicio < :end AND fecha_fin >= :end))");
    $stmt->execute([':groomer' => $groomerId, ':start' => $start, ':end' => $end]);
    $total = intval($stmt->fetchColumn() ?: 0);

    $capacity = getCapacity($conn, $groomerId);
    return $total >= $capacity;
}

function getScheduleIntervals($conn, $groomerId, $diaSemana) {

    $stmt = $conn->prepare("
        SELECT
            hora_inicio,
            hora_fin,
            intervalo_descanso
        FROM disponibilidad
        WHERE id_groomer=:groomer
        AND dia_semana=:dia
    ");

    $stmt->execute([
        ':groomer'=>$groomerId,
        ':dia'=>$diaSemana
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countAppointmentsForDay($conn, $groomerId, $date) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM cita WHERE id_groomer = :groomer AND DATE(fecha_inicio) = :date AND estado NOT IN ('cancelada', 'completada')");
    $stmt->execute([':groomer' => $groomerId, ':date' => $date]);
    return intval($stmt->fetchColumn());
}

if (!$groomerId || !$mascotaId || empty($servicioIds) || !sanitizeDate($fecha)) {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

$stmt = $conn->prepare("SELECT u.id_usuario, u.nombre FROM groomer g JOIN usuario u ON g.id_groomer = u.id_usuario WHERE g.id_groomer = ? AND g.estado_activo = 1");
$stmt->execute([$groomerId]);
$groomer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$groomer) {
    echo json_encode(['success' => false, 'message' => 'Groomer no disponible']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT 1 FROM mascota m
     LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
     WHERE m.id_mascota = :mascota
       AND (m.id_cliente_principal = :cliente OR md.id_cliente = :cliente)
     LIMIT 1"
);
$stmt->execute([':mascota' => $mascotaId, ':cliente' => $userId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Mascota inválida o no pertenece al cliente']);
    exit;
}

$placeholders = implode(',', array_fill(0, count($servicioIds), '?'));
$stmt = $conn->prepare("SELECT id_servicio FROM servicio WHERE id_servicio IN ($placeholders) AND estado_activo = 1");
$stmt->execute($servicioIds);
$available = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (count($available) !== count($servicioIds)) {
    echo json_encode(['success' => false, 'message' => 'Alguno de los servicios seleccionados no está disponible']);
    exit;
}

$duration = getServiceDuration($conn, $servicioIds, $mascotaId);
$day = getDayOfWeek($fecha);
$intervals = getScheduleIntervals($conn, $groomerId, $day);
error_log("DIA: ".$day);
error_log(print_r($intervals,true));
if (empty($intervals)) {
    echo json_encode(['success' => true, 'slots' => [], 'message' => 'El groomer no trabaja ese día.']);
    exit;
}

/*if (countAppointmentsForDay($conn, $groomerId, $fecha) >= 4) {
    echo json_encode(['success' => true, 'slots' => [], 'message' => 'El groomer ya tiene la capacidad máxima para ese día.']);
    exit;
}*/
function getCapacity($conn,$groomerId){

    $stmt=$conn->prepare("
        SELECT capacidad_simultanea
        FROM groomer
        WHERE id_groomer=?
    ");

    $stmt->execute([$groomerId]);

    return intval($stmt->fetchColumn() ?: 1);
}

$capacidad=getCapacity($conn,$groomerId);

if(countAppointmentsForDay(
    $conn,
    $groomerId,
    $fecha
)>=($capacidad*8)){

    echo json_encode([
        'success'=>true,
        'slots'=>[],
        'message'=>'Capacidad diaria alcanzada'
    ]);

    exit;
}
function isBreakTime($interval,$current,$slotEnd){

    if(empty($interval['intervalo_descanso'])){
        return false;
    }

    $descanso=json_decode(
        $interval['intervalo_descanso'],
        true
    );

    if(!$descanso){
        return false;
    }

    $fecha=$current->format('Y-m-d');

    $inicio=new DateTime(
        "$fecha ".$descanso['inicio']
    );

    $fin=new DateTime(
        "$fecha ".$descanso['fin']
    );

    return (
        $current < $fin &&
        $slotEnd > $inicio
    );
}
$slots = [];
foreach ($intervals as $interval) {

    $start = new DateTime(
        "$fecha {$interval['hora_inicio']}"
    );

    $end = new DateTime(
        "$fecha {$interval['hora_fin']}"
    );

    $current = clone $start;

    while($current < $end){

        $slotEnd=clone $current;

        $slotEnd->modify(
            "+{$duration} minutes"
        );

        if($slotEnd>$end){
            break;
        }

        $inicioStr=$current->format(
            'Y-m-d H:i:s'
        );

        $finStr=$slotEnd->format(
            'Y-m-d H:i:s'
        );

        $blocked=
            isBlocked(
                $conn,
                $groomerId,
                $inicioStr,
                $finStr
            )
            || hasConflict(
                $conn,
                $groomerId,
                $inicioStr,
                $finStr
            )
            || isBreakTime(
                $interval,
                $current,
                $slotEnd
            );

        if(!$blocked){
            $slots[]=
                $current->format('H:i');
        }

        $current->modify(
            '+30 minutes'
        );
    }
}

if (empty($slots)) {
    echo json_encode(['success' => true, 'slots' => [], 'message' => 'No hay horarios disponibles para el día seleccionado.']);
    exit;
}

echo json_encode(['success' => true, 'slots' => array_values(array_unique($slots))]);
exit;
