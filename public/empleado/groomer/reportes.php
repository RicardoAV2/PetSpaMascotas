<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    $stmt = $conn->prepare('SELECT id_groomer FROM groomer WHERE id_groomer = ? LIMIT 1');
    $stmt->execute([$currentUser['id']]);
    $groomer = $stmt->fetch(PDO::FETCH_ASSOC);
    $groomerId = $groomer['id_groomer'] ?? 0;

    $stmt = $conn->prepare('SELECT COUNT(*) AS servicios_realizados, AVG(TIMESTAMPDIFF(MINUTE, c.fecha_inicio, f.hora_fin_real)) AS tiempo_promedio FROM cita c JOIN ficha_grooming f ON c.id_cita = f.id_cita WHERE c.id_groomer = ? AND c.estado = "completada"');
    $stmt->execute([$groomerId]);
    $productividad = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT f.id_ficha, c.fecha_inicio, c.fecha_fin, m.nombre AS mascota, s.nombre AS servicio, f.observaciones, f.estado_mascota FROM ficha_grooming f JOIN cita c ON f.id_cita = c.id_cita JOIN mascota m ON c.id_mascota = m.id_mascota JOIN servicio s ON c.id_servicio = s.id_servicio WHERE c.id_groomer = ? ORDER BY f.fecha_cierre DESC LIMIT 10');
    $stmt->execute([$groomerId]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT pr.nombre, SUM(up.cantidad) AS total_usado FROM uso_producto up JOIN ficha_grooming f ON up.id_ficha = f.id_ficha JOIN cita c ON f.id_cita = c.id_cita JOIN producto pr ON up.id_producto = pr.id_producto WHERE c.id_groomer = ? GROUP BY up.id_producto ORDER BY total_usado DESC LIMIT 10');
    $stmt->execute([$groomerId]);
    $consumo = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $productividad = ['servicios_realizados' => 0, 'tiempo_promedio' => 0];
    $historial = $consumo = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Groomer - Pet Spa</title>
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
            <p>Groomer</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/groomer/agenda.php"><i class="fas fa-calendar-check me-2"></i>Mi Agenda</a></li>
            <li><a href="/petspa/public/empleado/groomer/grooming.php"><i class="fas fa-cut me-2"></i>Grooming</a></li>
            <li><a href="/petspa/public/empleado/groomer/inventario.php"><i class="fas fa-boxes me-2"></i>Inventario</a></li>
            <li><a class="active" href="/petspa/public/empleado/groomer/reportes.php"><i class="fas fa-file-alt me-2"></i>Reportes</a></li>
            <li><a href="/petspa/public/perfil.php"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
            <li><a href="/petspa/api/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Reportes Groomer</h2>
                <p class="text-muted">Productividad, historial de servicios y consumo de insumos.</p>
            </div>
            <a href="/petspa/public/empleado/groomer/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-user-clock me-2"></i>Productividad individual</h4>
                    <p class="mb-3">Servicios realizados y tiempo promedio por servicio.</p>
                    <h3><?php echo intval($productividad['servicios_realizados']); ?></h3>
                    <p>Tiempo promedio: <?php echo intval($productividad['tiempo_promedio']); ?> minutos</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-boxes me-2"></i>Consumo de insumos</h4>
                    <table class="table table-sm">
                        <thead><tr><th>Producto</th><th>Cantidad usada</th></tr></thead>
                        <tbody>
                            <?php foreach ($consumo as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                    <td><?php echo intval($item['total_usado']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($consumo)): ?>
                                <tr><td colspan="2" class="text-center text-muted">No hay consumo registrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="report-card">
            <h4><i class="fas fa-history me-2"></i>Historial de servicios realizados</h4>
            <table class="table table-sm">
                <thead><tr><th>Ficha</th><th>Fecha</th><th>Mascota</th><th>Servicio</th><th>Observaciones</th></tr></thead>
                <tbody>
                    <?php foreach ($historial as $registro): ?>
                        <tr>
                            <td><?php echo intval($registro['id_ficha']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($registro['fecha_inicio'])); ?></td>
                            <td><?php echo htmlspecialchars($registro['mascota']); ?></td>
                            <td><?php echo htmlspecialchars($registro['servicio']); ?></td>
                            <td><?php echo htmlspecialchars($registro['observaciones'] ?: $registro['estado_mascota']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($historial)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No se encontró historial de servicios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
