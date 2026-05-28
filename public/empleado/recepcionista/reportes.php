<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_RECEPCION);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    $stmt = $conn->prepare("SELECT c.id_cita, c.fecha_inicio, c.fecha_fin, c.estado, CONCAT(u.nombre, ' ', u.apellido) AS cliente, m.nombre AS mascota, s.nombre AS servicio FROM cita c JOIN mascota m ON c.id_mascota = m.id_mascota JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente JOIN usuario u ON cl.id_cliente = u.id_usuario JOIN servicio s ON c.id_servicio = s.id_servicio WHERE DATE(c.fecha_inicio) = CURDATE() ORDER BY c.fecha_inicio ASC");
    $stmt->execute();
    $agendaHoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT estado, COUNT(*) AS total FROM cita WHERE estado IN ('cancelada', 'no_asistio') GROUP BY estado");
    $stmt->execute();
    $canceladas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT p.id_producto, p.nombre, p.stock_actual, p.stock_minimo FROM producto p WHERE p.stock_actual <= p.stock_minimo ORDER BY p.stock_actual ASC");
    $stmt->execute();
    $inventarioCritico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT p.metodo_pago, SUM(p.monto) AS total, COUNT(*) AS pagos FROM pago p JOIN factura f ON p.id_factura = f.id_factura WHERE DATE(p.fecha_pago) = CURDATE() AND f.estado_factura = 'pagada' GROUP BY p.metodo_pago");
    $stmt->execute();
    $cierreCaja = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT SUM(p.monto) AS total_general FROM pago p JOIN factura f ON p.id_factura = f.id_factura WHERE DATE(p.fecha_pago) = CURDATE() AND f.estado_factura = 'pagada'");
    $stmt->execute();
    $totalCierre = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $agendaHoy = $canceladas = $inventarioCritico = $cierreCaja = [];
    $totalCierre = ['total_general' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Recepción - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .sidebar { position: fixed; top: 0; left: 0; width: 250px; height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; overflow-y: auto; }
        .sidebar-menu { list-style: none; padding: 0; margin: 20px 0 0; }
        .sidebar-menu li { margin-bottom: 10px; }
        .sidebar-menu a { color: white; text-decoration: none; display: block; padding: 10px 14px; border-radius: 8px; }
        .sidebar-menu a.active, .sidebar-menu a:hover { background: rgba(255,255,255,0.16); }
        .main-content { margin-left: 270px; padding: 20px; }
        .report-card { background: white; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); padding: 24px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <i class="fas fa-spa fa-2x"></i>
            <h4 class="mt-3">Pet Spa</h4>
            <p>Recepción</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/agenda.php"><i class="fas fa-calendar-alt me-2"></i>Agenda</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/citas.php"><i class="fas fa-calendar-check me-2"></i>Citas</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/inventario.php"><i class="fas fa-boxes me-2"></i>Inventario</a></li>
            <li><a class="active" href="/petspa/public/empleado/recepcionista/reportes.php"><i class="fas fa-file-alt me-2"></i>Reportes</a></li>
            <li><a href="/petspa/public/perfil.php"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
            <li><a href="/petspa/api/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Reportes de Recepción</h2>
                <p class="text-muted">Cronograma, cancelaciones, inventario crítico y cierre de caja.</p>
            </div>
            <a href="/petspa/public/empleado/recepcionista/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-calendar-day me-2"></i>Cronograma diario de citas</h4>
                    <table class="table table-sm">
                        <thead><tr><th>Hora</th><th>Mascota</th><th>Cliente</th><th>Servicio</th><th>Estado</th></tr></thead>
                        <tbody>
                            <?php foreach ($agendaHoy as $cita): ?>
                                <tr>
                                    <td><?php echo date('H:i', strtotime($cita['fecha_inicio'])); ?></td>
                                    <td><?php echo htmlspecialchars($cita['mascota']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['servicio']); ?></td>
                                    <td><?php echo htmlspecialchars($cita['estado']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($agendaHoy)): ?>
                                <tr><td colspan="5" class="text-center text-muted">No hay citas programadas para hoy.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-calendar-times me-2"></i>Citas canceladas / no show</h4>
                    <table class="table table-sm">
                        <thead><tr><th>Estado</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($canceladas as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['estado']); ?></td>
                                    <td><?php echo intval($item['total']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($canceladas)): ?>
                                <tr><td colspan="2" class="text-center text-muted">No hay registros de cancelaciones o no show.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-boxes me-2"></i>Inventario crítico</h4>
                    <table class="table table-sm">
                        <thead><tr><th>Producto</th><th>Stock</th><th>Mínimo</th></tr></thead>
                        <tbody>
                            <?php foreach ($inventarioCritico as $prod): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                    <td><?php echo intval($prod['stock_actual']); ?></td>
                                    <td><?php echo intval($prod['stock_minimo']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inventarioCritico)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No hay productos en stock crítico.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-cash-register me-2"></i>Cierre de caja diario</h4>
                    <p class="mb-2">Total cobrado hoy: S/ <?php echo number_format($totalCierre['total_general'] ?? 0, 2); ?></p>
                    <table class="table table-sm">
                        <thead><tr><th>Método</th><th>Total</th><th>Pagos</th></tr></thead>
                        <tbody>
                            <?php foreach ($cierreCaja as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['metodo_pago']); ?></td>
                                    <td>S/ <?php echo number_format($row['total'], 2); ?></td>
                                    <td><?php echo intval($row['pagos']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($cierreCaja)): ?>
                                <tr><td colspan="3" class="text-center text-muted">Aún no se han registrado pagos hoy.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
