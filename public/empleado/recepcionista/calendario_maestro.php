<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_RECEPCION, ROLE_ADMIN]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

$year = max(2000, intval($_GET['year'] ?? date('Y')));
$month = max(1, min(12, intval($_GET['month'] ?? date('n'))));
$currentDate = DateTime::createFromFormat('!Y-n', "$year-$month");
$firstDayOfMonth = $currentDate->format('Y-m-01 00:00:00');
$lastDayOfMonth = $currentDate->format('Y-m-t 23:59:59');

$daysOfWeek = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

$eventDays = [];

function addEventLabel(&$eventDays, $date, $type, $label) {
    if (!isset($eventDays[$date])) {
        $eventDays[$date] = [];
    }
    $eventDays[$date][] = ['type' => $type, 'label' => $label];
}

try {
    $stmt = $conn->prepare("SELECT b.*, CONCAT(u.nombre, ' ', u.apellido) AS groomer_nombre FROM bloqueo_agenda b LEFT JOIN groomer g ON b.id_groomer = g.id_groomer LEFT JOIN usuario u ON g.id_groomer = u.id_usuario WHERE b.fecha_inicio <= :end AND b.fecha_fin >= :start ORDER BY b.fecha_inicio");
    $stmt->execute([':start' => $firstDayOfMonth, ':end' => $lastDayOfMonth]);
    $bloqueos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT c.id_cita, c.fecha_inicio, c.fecha_fin, c.estado, CONCAT(u_cliente.nombre, ' ', u_cliente.apellido) AS cliente_nombre, CONCAT(u_groomer.nombre, ' ', u_groomer.apellido) AS groomer_nombre, GROUP_CONCAT(DISTINCT s.nombre ORDER BY s.nombre SEPARATOR ', ') AS servicios_nombres FROM cita c JOIN mascota m ON c.id_mascota = m.id_mascota JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente JOIN usuario u_cliente ON cl.id_cliente = u_cliente.id_usuario JOIN groomer g ON c.id_groomer = g.id_groomer JOIN usuario u_groomer ON g.id_groomer = u_groomer.id_usuario LEFT JOIN cita_servicio cs ON cs.id_cita = c.id_cita LEFT JOIN servicio s ON cs.id_servicio = s.id_servicio WHERE c.estado NOT IN ('cancelada', 'completada') AND c.fecha_inicio <= :end AND c.fecha_fin >= :start GROUP BY c.id_cita ORDER BY c.fecha_inicio");
    $stmt->execute([':start' => $firstDayOfMonth, ':end' => $lastDayOfMonth]);
    $citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT p.* FROM promocion p WHERE p.activo = 1 AND p.fecha_inicio <= :end AND p.fecha_fin >= :start ORDER BY p.fecha_inicio");
    $stmt->execute([':start' => $firstDayOfMonth, ':end' => $lastDayOfMonth]);
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $bloqueos = $citas = $promociones = [];
}

foreach ($bloqueos as $bloqueo) {
    $inicio = new DateTime($bloqueo['fecha_inicio']);
    $fin = new DateTime($bloqueo['fecha_fin']);
    $actualStart = max($inicio, new DateTime($firstDayOfMonth));
    $actualEnd = min($fin, new DateTime($lastDayOfMonth));
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($actualStart, $interval, $actualEnd->add($interval));
    foreach ($period as $day) {
        addEventLabel($eventDays, $day->format('Y-m-d'), 'bloqueo', ($bloqueo['groomer_nombre'] ? 'Groomer: ' . $bloqueo['groomer_nombre'] . ' - ' : '') . ucfirst($bloqueo['tipo_bloqueo']) . ': ' . ($bloqueo['motivo'] ?: 'Bloqueado'));
    }
}

foreach ($citas as $cita) {
    $date = (new DateTime($cita['fecha_inicio']))->format('Y-m-d');
    addEventLabel($eventDays, $date, 'cita', 'Cita: ' . $cita['servicios_nombres'] . ' con ' . $cita['groomer_nombre']);
}

foreach ($promociones as $promo) {
    $inicio = new DateTime($promo['fecha_inicio']);
    $fin = new DateTime($promo['fecha_fin']);
    $actualStart = max($inicio, new DateTime($firstDayOfMonth));
    $actualEnd = min($fin, new DateTime($lastDayOfMonth));
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($actualStart, $interval, $actualEnd->add($interval));
    foreach ($period as $day) {
        addEventLabel($eventDays, $day->format('Y-m-d'), 'promocion', 'Promo: ' . $promo['nombre']);
    }
}

$firstWeekday = (int)(new DateTime($currentDate->format('Y-m-01')))->format('w');
$daysInMonth = (int)$currentDate->format('t');
$prevMonth = clone $currentDate;
$prevMonth->modify('-1 month');
$nextMonth = clone $currentDate;
$nextMonth->modify('+1 month');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Maestro - Recepcionista Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f3f6fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 0; z-index: 1000; }
        .sidebar-logo { text-align: center; padding: 20px; border-bottom: 1px solid rgba(255,255,255,.2); margin-bottom: 20px; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { margin: 5px 0; }
        .sidebar-menu a { color: white; text-decoration: none; padding: 12px 25px; display: block; transition: all .3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,.2); color: white; }
        .main-content { margin-left: 250px; padding: 20px; }
        .topbar { background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.05); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .calendar-card { background: white; border-radius: 16px; box-shadow: 0 2px 20px rgba(0,0,0,.08); padding: 24px; }
        .calendar-head { display:flex; justify-content: space-between; align-items:center; margin-bottom: 20px; gap: 10px; }
        .calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 10px; }
        .weekday-label { text-align: center; font-weight: 700; color: #4d5b7c; padding: 10px 0; }
        .day-cell { min-height: 120px; background: #ffffff; border: 1px solid #e8ebf4; border-radius: 14px; padding: 12px; position: relative; }
        .day-cell.disabled { background: #eef2f8; color: #8c96aa; }
        .day-number { font-size: 0.95rem; font-weight: 700; margin-bottom: 10px; }
        .event-chip { display: block; font-size: 0.78rem; line-height: 1.3; margin-bottom: 6px; padding: 6px 8px; border-radius: 12px; color: white; }
        .event-chip.bloqueo { background: #e74c3c; }
        .event-chip.cita { background: #2ecc71; }
        .event-chip.promocion { background: #3498db; }
        .legend { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .legend-item { display: flex; align-items: center; gap: 8px; background: white; border-radius: 12px; padding: 10px 14px; box-shadow: 0 1px 10px rgba(0,0,0,.05); }
        .legend-dot { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-spa" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
            <h5>Pet Spa</h5>
            <small>Recepcionista</small>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/agenda.php"><i class="fas fa-calendar-alt me-2"></i> Agenda</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/calendario_maestro.php" class="active"><i class="fas fa-calendar-week me-2"></i> Calendario Maestro</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/citas.php"><i class="fas fa-calendar-check me-2"></i> Citas</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/inventario.php"><i class="fas fa-boxes me-2"></i> Inventario</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/cobrar.php"><i class="fas fa-credit-card me-2"></i> Cobrar servicios</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/reportes.php"><i class="fas fa-file-alt me-2"></i> Reportes</a></li>
            <li><a href="/petspa/public/perfil.php"><i class="fas fa-user-circle me-2"></i> Mi Perfil</a></li>
            <li><a href="/petspa/api/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="topbar">
            <div>
                <h3 style="margin:0;"><i class="fas fa-calendar-week me-2"></i> Calendario Maestro</h3>
                <p class="text-muted mb-0">Mira los bloqueos globales, citas programadas y promociones activas en un calendario mensual central.</p>
            </div>
            <div class="topbar-user">
                <span><?php echo getGreeting(); ?>, <strong><?php echo htmlspecialchars($currentUser['nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo getInitials($currentUser['nombre']); ?></div>
            </div>
        </div>

        <div class="calendar-card">
            <div class="calendar-head">
                <div>
                    <h4 class="mb-1"><?php echo $monthNames[$month - 1] . ' ' . $year; ?></h4>
                    <small class="text-muted">Días bloqueados, citas y promociones visibles con color.</small>
                </div>
                <div class="btn-group">
                    <a href="?month=<?php echo $prevMonth->format('n'); ?>&year=<?php echo $prevMonth->format('Y'); ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-chevron-left"></i> Mes anterior</a>
                    <a href="?month=<?php echo $nextMonth->format('n'); ?>&year=<?php echo $nextMonth->format('Y'); ?>" class="btn btn-outline-secondary btn-sm">Mes siguiente <i class="fas fa-chevron-right"></i></a>
                </div>
            </div>

            <div class="calendar-grid mb-3">
                <?php foreach ($daysOfWeek as $weekday): ?>
                    <div class="weekday-label"><?php echo $weekday; ?></div>
                <?php endforeach; ?>

                <?php for ($blank = 0; $blank < $firstWeekday; $blank++): ?>
                    <div class="day-cell disabled"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++):
                    $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $items = $eventDays[$dateKey] ?? [];
                    $hasEvents = !empty($items);
                ?>
                    <div class="day-cell<?php echo $hasEvents ? '' : ' disabled'; ?>">
                        <div class="day-number"><?php echo $day; ?></div>
                        <?php if ($hasEvents): ?>
                            <?php foreach ($items as $item): ?>
                                <span class="event-chip <?php echo htmlspecialchars($item['type']); ?>" title="<?php echo htmlspecialchars($item['label']); ?>"><?php echo htmlspecialchars(mb_strimwidth($item['label'], 0, 32, '...')); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-muted" style="font-size:.85rem;">Sin eventos</div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="legend">
                <div class="legend-item"><span class="legend-dot" style="background:#e74c3c"></span> Bloqueos / Días cerrados</div>
                <div class="legend-item"><span class="legend-dot" style="background:#2ecc71"></span> Citas programadas</div>
                <div class="legend-item"><span class="legend-dot" style="background:#3498db"></span> Promociones activas</div>
            </div>

            <div class="mt-4">
                <h6>Detalle de eventos</h6>
                <?php if (empty($eventDays)): ?>
                    <p class="text-muted">No hay eventos programados para este mes.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Evento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php ksort($eventDays); foreach ($eventDays as $date => $items): ?>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($date)); ?></td>
                                            <td><span class="badge bg-<?php echo $item['type'] === 'bloqueo' ? 'danger' : ($item['type'] === 'cita' ? 'success' : 'primary'); ?> me-2"><?php echo strtoupper($item['type']); ?></span><?php echo htmlspecialchars($item['label']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
