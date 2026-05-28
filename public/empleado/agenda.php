<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']);

try {

    // =========================
// PAGINACIÓN
// =========================

// Citas
$pageCitas = max(1, intval($_GET['page_citas'] ?? 1));
$limitCitas = 10;
$offsetCitas = ($pageCitas - 1) * $limitCitas;

// Horarios
$pageHorario = max(1, intval($_GET['page_horario'] ?? 1));
$limitHorario = 8;
$offsetHorario = ($pageHorario - 1) * $limitHorario;


    $stmt = $conn->prepare("SELECT g.id_groomer, u.id_usuario, CONCAT(u.nombre, ' ', u.apellido) AS nombre, g.especialidad, g.estado_activo FROM groomer g JOIN usuario u ON g.id_groomer = u.id_usuario ORDER BY u.nombre");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT b.*, CONCAT(u.nombre, ' ', u.apellido) AS groomer_nombre FROM bloqueo_agenda b LEFT JOIN groomer g ON b.id_groomer = g.id_groomer LEFT JOIN usuario u ON g.id_groomer = u.id_usuario ORDER BY b.fecha_inicio DESC");
    $stmt->execute();
    $bloqueos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total horarios
    $stmt=$conn->prepare("
    SELECT COUNT(*)
    FROM disponibilidad
    ");
    $stmt->execute();

    $totalHorario=$stmt->fetchColumn();

    $totalPagesHorario=ceil(
    $totalHorario/$limitHorario
    );


    // Horarios paginados

    $stmt=$conn->prepare("
    SELECT d.*,
    CONCAT(u.nombre,' ',u.apellido)
    AS groomer_nombre

    FROM disponibilidad d

    JOIN groomer g
    ON d.id_groomer=g.id_groomer

    JOIN usuario u
    ON g.id_groomer=u.id_usuario

    ORDER BY u.nombre,d.dia_semana

    LIMIT $limitHorario
    OFFSET $offsetHorario
    ");

    $stmt->execute();

    $disponibilidades=
    $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total citas
    $stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM cita
    WHERE estado NOT IN ('cancelada','completada')
    ");
    $stmt->execute();
    $totalCitas = $stmt->fetchColumn();

    $totalPagesCitas = ceil($totalCitas / $limitCitas);

    // Citas paginadas
    $stmt = $conn->prepare("
    SELECT c.id_cita,
    c.fecha_inicio,
    c.fecha_fin,
    c.estado,
    c.nota,
    c.id_groomer,
    c.id_servicio,
    c.motivo_cancelacion,
    CONCAT(u_cliente.nombre,' ',u_cliente.apellido) AS cliente_nombre,
    m.nombre AS mascota_nombre,
    CONCAT(u_groomer.nombre,' ',u_groomer.apellido) AS groomer_nombre,
    GROUP_CONCAT(DISTINCT s.nombre ORDER BY s.nombre SEPARATOR ', ') AS servicios_nombres

    FROM cita c
    JOIN mascota m 
    ON c.id_mascota=m.id_mascota

    JOIN cliente cl
    ON m.id_cliente_principal=cl.id_cliente

    JOIN usuario u_cliente
    ON cl.id_cliente=u_cliente.id_usuario

    JOIN groomer g
    ON c.id_groomer=g.id_groomer

    JOIN usuario u_groomer
    ON g.id_groomer=u_groomer.id_usuario

    LEFT JOIN cita_servicio cs
    ON cs.id_cita = c.id_cita

    LEFT JOIN servicio s
    ON cs.id_servicio = s.id_servicio

    WHERE c.estado NOT IN ('cancelada','completada')

    GROUP BY c.id_cita
    ORDER BY c.fecha_inicio ASC
    LIMIT $limitCitas OFFSET $offsetCitas
    ");

    $stmt->execute();
    $citas=$stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT m.id_mascota, m.nombre AS mascota_nombre, CONCAT(u_cliente.nombre, ' ', u_cliente.apellido) AS cliente_nombre FROM mascota m JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente JOIN usuario u_cliente ON cl.id_cliente = u_cliente.id_usuario ORDER BY u_cliente.nombre, m.nombre");
    $stmt->execute();
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT id_servicio, nombre, precio_base FROM servicio WHERE estado_activo = 1 ORDER BY nombre");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $groomers = $bloqueos = $disponibilidades = $citas = $mascotas = $servicios = [];
}

$days = [0 => 'Domingo', 1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda y Disponibilidad - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Agenda y Disponibilidad</h2>
            <p class="text-muted mb-0">Administra bloqueos, disponibilidad de groomers y reprogramaciones de citas.</p>
        </div>
        <div>
            <a href="<?php echo $currentUser['rol'] === ROLE_ADMIN ? '/petspa/public/empleado/admin/dashboard.php' : '/petspa/public/empleado/recepcionista/dashboard.php'; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver al panel
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">Acción guardada correctamente.</div>
    <?php endif; ?>

    <div class="row gy-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-minus me-2"></i> Crear Bloqueo de Agenda</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/empleado/agenda.php" method="POST">
                        <input type="hidden" name="action" value="crear_bloqueo">
                        <div class="mb-3">
                            <label class="form-label">Groomer</label>
                            <select name="groomer_id" class="form-select">
                                <option value="">Global / Todos</option>
                                <?php foreach ($groomers as $g): ?>
                                    <option value="<?php echo $g['id_groomer']; ?>"><?php echo htmlspecialchars($g['nombre'] . ' (' . $g['especialidad'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Fecha inicio</label>
                                <input type="datetime-local" name="fecha_inicio" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Fecha fin</label>
                                <input type="datetime-local" name="fecha_fin" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tipo</label>
                            <select name="tipo" class="form-select" required>
                                <option value="ausencia">Ausencia</option>
                                <option value="feriado">Feriado</option>
                                <option value="vacaciones">Vacaciones</option>
                                <option value="mantenimiento">Mantenimiento</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motivo</label>
                            <textarea name="motivo" class="form-control" rows="2" placeholder="Ej: Día del trabajador"></textarea>
                        </div>
                        <button class="btn btn-success">Guardar bloqueo</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i> Citas próximas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Mascota</th>
                                    <th>Groomer</th>
                                    <th>Servicios</th>
                                    <th>Inicio</th>
                                    <th>Reprogramar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($citas)): ?>
                                    <?php foreach ($citas as $c): ?>
                                        <tr>
                                            <td><?php echo $c['id_cita']; ?></td>
                                            <td><?php echo htmlspecialchars($c['cliente_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($c['mascota_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($c['groomer_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($c['servicios_nombres'] ?? $c['servicio_nombre'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($c['fecha_inicio']); ?></td>
                                            <td>
                                                <form action="/petspa/api/empleado/agenda.php" method="POST" class="d-flex flex-column gap-2">
                                                    <input type="hidden" name="action" value="reprogramar_cita">
                                                    <input type="hidden" name="cita_id" value="<?php echo $c['id_cita']; ?>">
                                                    <div class="mb-1">
                                                        <label class="form-label small mb-1">Fecha</label>
                                                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?php echo date('Y-m-d', strtotime($c['fecha_inicio'])); ?>" required>
                                                    </div>
                                                    <div class="mb-1">
                                                        <label class="form-label small mb-1">Hora</label>
                                                        <input type="time" name="hora" class="form-control form-control-sm" value="<?php echo date('H:i', strtotime($c['fecha_inicio'])); ?>" required>
                                                    </div>
                                                    <div>
                                                        <label class="form-label small mb-1">Groomer</label>
                                                        <select name="groomer_id" class="form-select form-select-sm" required>
                                                            <?php foreach ($groomers as $g): ?>
                                                                <option value="<?php echo $g['id_groomer']; ?>" <?php echo $g['id_groomer'] === $c['id_groomer'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['nombre']); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <button class="btn btn-sm btn-primary mt-2">Reprogramar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No hay citas próximas para mostrar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center p-3">

                            <?php if($pageCitas>1): ?>
                            <a class="btn btn-outline-primary me-2"
                            href="?page_citas=<?php echo $pageCitas-1; ?>&page_horario=<?php echo $pageHorario; ?>">
                            <i class="fas fa-arrow-left"></i>
                            </a>
                            <?php endif; ?>


                            <span class="align-self-center">
                            Página <?php echo $pageCitas; ?>
                            de <?php echo $totalPagesCitas; ?>
                            </span>


                            <?php if($pageCitas<$totalPagesCitas): ?>

                            <a class="btn btn-outline-primary ms-2"
                            href="?page_citas=<?php echo $pageCitas+1; ?>&page_horario=<?php echo $pageHorario; ?>">
                            <i class="fas fa-arrow-right"></i>
                            </a>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4 mt-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i> Crear nueva cita</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/empleado/agenda.php" method="POST">
                        <input type="hidden" name="action" value="crear_cita">
                        <div class="row gy-3">
                            <div class="col-md-3">
                                <label class="form-label">Mascota</label>
                                <select name="mascota_id" class="form-select" required>
                                    <option value="">Selecciona una mascota</option>
                                    <?php foreach ($mascotas as $m): ?>
                                        <option value="<?php echo $m['id_mascota']; ?>"><?php echo htmlspecialchars($m['cliente_nombre'] . ' - ' . $m['mascota_nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Servicios</label>
                                <select name="servicio_ids[]" class="form-select" multiple size="5" required>
                                    <?php foreach ($servicios as $s): ?>
                                        <option value="<?php echo $s['id_servicio']; ?>"><?php echo htmlspecialchars($s['nombre'] . ' - $' . number_format($s['precio_base'], 2)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Ctrl/Cmd + clic para seleccionar varios servicios.</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Groomer</label>
                                <select name="groomer_id" class="form-select" required>
                                    <option value="">Selecciona un groomer</option>
                                    <?php foreach ($groomers as $g): ?>
                                        <option value="<?php echo $g['id_groomer']; ?>"><?php echo htmlspecialchars($g['nombre'] . ' (' . $g['especialidad'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Hora</label>
                                <input type="time" name="hora" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notas</label>
                                <input type="text" name="nota" class="form-control" placeholder="Ej: cliente necesita correa extra">
                            </div>
                            <div class="col-md-12 text-end">
                                <button class="btn btn-primary">Crear cita</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4 mt-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-ban me-2"></i> Bloqueos de Agenda</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Groomer</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Tipo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($bloqueos)): ?>
                                    <?php foreach ($bloqueos as $b): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($b['groomer_nombre'] ?: 'Global'); ?></td>
                                            <td><?php echo htmlspecialchars($b['fecha_inicio']); ?></td>
                                            <td><?php echo htmlspecialchars($b['fecha_fin']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($b['tipo_bloqueo'])); ?></td>
                                            <td>
                                                <a href="/petspa/api/empleado/agenda.php?action=delete_bloqueo&id=<?php echo $b['id_bloqueo']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar bloqueo?');">Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay bloqueos activos.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i> Horarios de Groomers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Groomer</th>
                                    <th>Día</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th>Activo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($disponibilidades)): ?>
                                    <?php foreach ($disponibilidades as $d): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($d['groomer_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($days[intval($d['dia_semana'])] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($d['hora_inicio']); ?></td>
                                            <td><?php echo htmlspecialchars($d['hora_fin']); ?></td>
                                            <td><span class="badge bg-<?php echo $d['estado_activo'] ? 'success' : 'secondary'; ?>"><?php echo $d['estado_activo'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay disponibilidad registrada.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <div class="d-flex justify-content-center p-3">

                            <?php if($pageHorario>1): ?>

                            <a class="btn btn-outline-success me-2"
                            href="?page_horario=<?php echo $pageHorario-1; ?>&page_citas=<?php echo $pageCitas; ?>">

                            <i class="fas fa-arrow-left"></i>

                            </a>

                            <?php endif; ?>


                            <span class="align-self-center">

                            Página <?php echo $pageHorario; ?>

                            de

                            <?php echo $totalPagesHorario; ?>

                            </span>


                            <?php if($pageHorario<$totalPagesHorario): ?>

                            <a class="btn btn-outline-success ms-2"
                            href="?page_horario=<?php echo $pageHorario+1; ?>&page_citas=<?php echo $pageCitas; ?>">

                            <i class="fas fa-arrow-right"></i>

                            </a>

                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
