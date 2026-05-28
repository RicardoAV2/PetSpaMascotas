<?php
require_once "../../../config/database.php";
require_once "../../../config/constants.php";
require_once "../../../core/Auth.php";
require_once "../../../core/middleware.php";
require_once "../../../core/helpers.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$message = $_GET['success'] ?? $_GET['error'] ?? null;
$messageType = isset($_GET['error']) ? 'danger' : 'success';

$sql = "SELECT c.*, m.nombre AS mascota, s.nombre AS servicio, u.nombre AS cliente_nombre, g.id_groomer, gu.nombre AS groomer_nombre
        FROM cita c
        JOIN mascota m ON c.id_mascota = m.id_mascota
        JOIN servicio s ON c.id_servicio = s.id_servicio
        JOIN usuario u ON m.id_cliente_principal = u.id_usuario
        JOIN groomer g ON c.id_groomer = g.id_groomer
        JOIN usuario gu ON g.id_groomer = gu.id_usuario
        ORDER BY c.fecha_inicio DESC";
$citas = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$groomers = $conn->query("SELECT g.id_groomer, u.nombre FROM groomer g JOIN usuario u ON g.id_groomer = u.id_usuario WHERE g.estado_activo = 1 ORDER BY u.nombre")->fetchAll(PDO::FETCH_ASSOC);

define('STATUS_LABELS', [
    'pendiente' => 'Pendiente',
    'agendada' => 'Agendada',
    'confirmada' => 'Confirmada',
    'en_progreso' => 'En progreso',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
    'no_asistio' => 'No asistió'
]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas - Recepción</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gestión de Citas</h2>
            <a href="/petspa/public/empleado/recepcionista/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>

            <p class="text-muted mb-0">Confirma, reprograma o cancela citas creadas por clientes.</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Mascota</th>
                    <th>Cliente</th>
                    <th>Groomer</th>
                    <th>Servicio</th>
                    <th>Fecha / Hora</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($citas as $c): ?>
                    <tr>
                        <td><?php echo $c['id_cita']; ?></td>
                        <td><?php echo htmlspecialchars($c['mascota']); ?></td>
                        <td><?php echo htmlspecialchars($c['cliente_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($c['groomer_nombre']); ?></td>
                        <td><?php echo htmlspecialchars($c['servicio']); ?></td>
                        <td><?php echo htmlspecialchars($c['fecha_inicio']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo $c['estado'] === 'confirmada' ? 'primary' : ($c['estado'] === 'pendiente' ? 'warning text-dark' : ($c['estado'] === 'completada' ? 'success' : ($c['estado'] === 'cancelada' ? 'danger' : 'secondary'))); ?>">
                                <?php echo STATUS_LABELS[$c['estado']] ?? $c['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <?php if (in_array($c['estado'], ['pendiente', 'agendada', 'confirmada'])): ?>
                                    <?php if ($c['estado'] === 'pendiente' || $c['estado'] === 'agendada'): ?>
                                        <a href="/petspa/api/empleado/citas.php?action=confirmar&id=<?php echo $c['id_cita']; ?>" class="btn btn-sm btn-success">Confirmar</a>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#reprogramModal" data-id="<?php echo $c['id_cita']; ?>" data-fecha="<?php echo substr($c['fecha_inicio'], 0, 10); ?>" data-hora="<?php echo substr($c['fecha_inicio'], 11, 5); ?>" data-groomer="<?php echo $c['id_groomer']; ?>">Reprogramar</button>
                                    <a href="/petspa/api/empleado/citas.php?action=cancelar&id=<?php echo $c['id_cita']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Cancelar esta cita?');">Cancelar</a>
                                <?php else: ?>
                                    <span class="text-muted">Sin acciones</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Reprogramación -->
<div class="modal fade" id="reprogramModal" tabindex="-1" aria-labelledby="reprogramModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="/petspa/api/empleado/citas.php" method="GET" class="modal-content">
            <input type="hidden" name="action" value="reprogramar">
            <input type="hidden" name="id" id="modalCitaId" value="">
            <div class="modal-header">
                <h5 class="modal-title" id="reprogramModalLabel">Reprogramar cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modalFecha" class="form-label">Fecha nueva</label>
                    <input type="date" id="modalFecha" name="fecha" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="modalHora" class="form-label">Hora nueva</label>
                    <input type="time" id="modalHora" name="hora" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="modalGroomer" class="form-label">Groomer</label>
                    <select id="modalGroomer" name="groomer_id" class="form-select">
                        <option value="">Mismo groomer</option>
                        <?php foreach ($groomers as $g): ?>
                            <option value="<?php echo $g['id_groomer']; ?>"><?php echo htmlspecialchars($g['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="modalMotivo" class="form-label">Motivo / Notas</label>
                    <textarea id="modalMotivo" name="motivo" class="form-control" rows="2" placeholder="Opcional"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const reprogramModal = document.getElementById('reprogramModal');
    reprogramModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button.getAttribute('data-id');
        const fecha = button.getAttribute('data-fecha');
        const hora = button.getAttribute('data-hora');
        const groomer = button.getAttribute('data-groomer');

        document.getElementById('modalCitaId').value = id;
        document.getElementById('modalFecha').value = fecha;
        document.getElementById('modalHora').value = hora;
        document.getElementById('modalGroomer').value = groomer;
    });
</script>
</body>
</html>