<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAdmin();
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    $stmt = $conn->prepare("SELECT SUM(total) AS ingresos_totales, SUM(subtotal) AS subtotal_total FROM factura WHERE estado_factura = 'pagada'");
    $stmt->execute();
    $ingresos = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT SUM(df.subtotal) AS servicios, SUM(df.subtotal) AS productos FROM detalle_factura df JOIN factura f ON df.id_factura = f.id_factura WHERE f.estado_factura = 'pagada'");
    $stmt->execute();
    $ventas = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT s.nombre, SUM(df.cantidad) AS unidades, SUM(df.subtotal) AS total_ventas FROM detalle_factura df JOIN servicio s ON df.id_servicio = s.id_servicio JOIN factura f ON df.id_factura = f.id_factura WHERE f.estado_factura = 'pagada' AND df.id_servicio IS NOT NULL GROUP BY df.id_servicio ORDER BY total_ventas DESC LIMIT 5");
    $stmt->execute();
    $topServicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT p.nombre, SUM(df.cantidad) AS unidades, SUM(df.subtotal) AS total_ventas FROM detalle_factura df JOIN producto p ON df.id_producto = p.id_producto JOIN factura f ON df.id_factura = f.id_factura WHERE f.estado_factura = 'pagada' AND df.id_producto IS NOT NULL GROUP BY df.id_producto ORDER BY total_ventas DESC LIMIT 5");
    $stmt->execute();
    $topProductos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT AVG(puntuacion) AS promedio, COUNT(*) AS total_calificaciones, SUM(CASE WHEN puntuacion >= 4 THEN 1 ELSE 0 END) AS promotores, SUM(CASE WHEN puntuacion BETWEEN 2 AND 3 THEN 1 ELSE 0 END) AS pasivos, SUM(CASE WHEN puntuacion <= 1 THEN 1 ELSE 0 END) AS detractores FROM calificacion");
    $stmt->execute();
    $nps = $stmt->fetch(PDO::FETCH_ASSOC);
    $npsScore = 0;
    if ($nps['total_calificaciones'] > 0) {
        $npsScore = round((($nps['promotores'] - $nps['detractores']) / $nps['total_calificaciones']) * 100, 1);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS citas_totales, SUM(CASE WHEN estado IN ('completada','en_progreso','confirmada','agendada') THEN 1 ELSE 0 END) AS citas_activas FROM cita");
    $stmt->execute();
    $citasResumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT SUM(g.capacidad_simultanea) AS capacidad_total FROM groomer g");
    $stmt->execute();
    $capacidad = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT COUNT(*) AS citas_hoy FROM cita WHERE DATE(fecha_inicio) = CURDATE()');
    $stmt->execute();
    $citasHoy = $stmt->fetch(PDO::FETCH_ASSOC);

    $ocupacionGlobal = 0;
    if ($capacidad['capacidad_total'] > 0) {
        $ocupacionGlobal = round(($citasHoy['citas_hoy'] / $capacidad['capacidad_total']) * 100, 1);
    }

    $stmt = $conn->prepare("SELECT p.nombre, i.cantidad_fisica, p.stock_minimo FROM producto p JOIN inventario i ON p.id_producto = i.id_producto WHERE p.stock_actual <= p.stock_minimo GROUP BY p.id_producto ORDER BY p.stock_actual ASC LIMIT 10");
    $stmt->execute();
    $insumosCriticos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT pr.nombre, SUM(up.cantidad) AS usado FROM uso_producto up JOIN producto pr ON up.id_producto = pr.id_producto GROUP BY up.id_producto ORDER BY usado DESC LIMIT 10");
    $stmt->execute();
    $insumosUsados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT mi.referencia_tipo, SUM(mi.cantidad) AS total, mi.tipo_movimiento FROM movimiento_inventario mi WHERE mi.tipo_movimiento IN ('salida_consumo', 'salida_venta') GROUP BY mi.tipo_movimiento");
    $stmt->execute();
    $movimientosInsumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT f.id_factura, f.numero_factura, f.total, p.metodo_pago, p.monto, p.fecha_pago FROM pago p JOIN factura f ON p.id_factura = f.id_factura WHERE f.estado_factura = 'pagada' ORDER BY p.fecha_pago DESC LIMIT 20");
    $stmt->execute();
    $pagosRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT f.numero_factura, GROUP_CONCAT(df.concepto SEPARATOR ", ") AS conceptos FROM detalle_factura df JOIN factura f ON df.id_factura = f.id_factura WHERE f.estado_factura = "pagada" GROUP BY f.id_factura ORDER BY f.fecha_emision DESC LIMIT 10');
    $stmt->execute();
    $facturasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT c.*, CONCAT(u.nombre, " ", u.apellido) AS cliente, m.nombre AS mascota, s.nombre AS servicio FROM cita c JOIN mascota m ON c.id_mascota = m.id_mascota JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente JOIN usuario u ON cl.id_cliente = u.id_usuario JOIN servicio s ON c.id_servicio = s.id_servicio WHERE c.estado IN ("completada", "confirmada", "agendada") ORDER BY c.fecha_inicio DESC LIMIT 5');
    $stmt->execute();
    $citasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare('SELECT comentario, puntuacion, fecha FROM calificacion ORDER BY fecha DESC LIMIT 5');
    $stmt->execute();
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $ingresos = $ventas = $capacidad = $citasResumen = $citasHoy = ['citas_hoy' => 0];
    $topServicios = $topProductos = $insumosCriticos = $insumosUsados = $movimientosInsumos = $pagosRecientes = $facturasRecientes = $citasRecientes = $comentarios = [];
    $nps = ['promedio' => 0, 'total_calificaciones' => 0];
    $npsScore = 0;
    $ocupacionGlobal = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Admin - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header {
            margin-bottom: 20px;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
            padding: 24px;
            margin-bottom: 20px;
        }
        .report-card h4 {
            margin-bottom: 15px;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
            z-index: 1000;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 20px 0 0;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 10px 14px;
            border-radius: 8px;
        }
        .sidebar-menu a.active,
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.16);
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center mb-4">
            <i class="fas fa-spa fa-2x"></i>
            <h4 class="mt-3">Pet Spa</h4>
            <p>Administrador</p>
        </div>
        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/admin/dashboard.php"><i class="fas fa-chart-line me-2"></i>Dashboard</a></li>
            <li><a href="/petspa/public/empleado/admin/empleados.php"><i class="fas fa-users me-2"></i>Usuarios</a></li>
            <li><a href="/petspa/public/empleado/admin/productos.php"><i class="fas fa-box me-2"></i>Productos</a></li>
            <li><a href="/petspa/public/empleado/facturacion/generar_factura.php"><i class="fas fa-file-invoice-dollar me-2"></i>Facturación</a></li>
            <li><a href="/petspa/public/empleado/promociones.php"><i class="fas fa-tags me-2"></i>Promociones</a></li>
            <li><a href="/petspa/public/empleado/agenda.php"><i class="fas fa-calendar-alt me-2"></i>Agenda</a></li>
            <li><a class="active" href="/petspa/public/empleado/admin/reportes.php"><i class="fas fa-file-alt me-2"></i>Reportes</a></li>
            <li><a href="/petspa/public/empleado/admin/auditoria.php"><i class="fas fa-user-shield me-2"></i>Auditoría</a></li>
            <li><a href="/petspa/public/perfil.php"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
            <li><a href="/petspa/api/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header d-flex justify-content-between align-items-center">
            <div>
                <h2>Reportes de administración</h2>
                <p class="text-muted">Resumen de ingresos, insumos, ocupación y satisfacción.</p>
            </div>
            <a href="/petspa/public/empleado/admin/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver al dashboard</a>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="report-card">
                    <h4><i class="fas fa-dollar-sign me-2"></i>Ventas totales</h4>
                    <p class="mb-1">Ingresos por servicios y productos pagados.</p>
                    <h3>S/ <?php echo number_format($ingresos['ingresos_totales'] ?? 0, 2); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card">
                    <h4><i class="fas fa-chart-bar me-2"></i>Ocupación global</h4>
                    <p class="mb-1">Porcentaje de uso de la capacidad instalada.</p>
                    <h3><?php echo $ocupacionGlobal; ?>%</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card">
                    <h4><i class="fas fa-star me-2"></i>Satisfacción / NPS</h4>
                    <p class="mb-1">Calificación promedio y NPS estimado.</p>
                    <h3><?php echo number_format($nps['promedio'] ?? 0, 1); ?> / <?php echo $npsScore; ?>%</h3>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-boxes me-2"></i>Auditoría de insumos</h4>
                    <p class="mb-3">Comparación entre insumos entregados, usados y descontados.</p>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Movimiento</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientosInsumos as $mov): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mov['tipo_movimiento']); ?></td>
                                    <td><?php echo number_format($mov['total'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-thumbs-up me-2"></i>Encuestas post-servicio</h4>
                    <p class="mb-3">Últimas calificaciones y comentarios recibidos.</p>
                    <?php if (!empty($comentarios)): ?>
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="mb-3">
                                <strong><?php echo number_format($comentario['puntuacion'], 1); ?> estrellas</strong>
                                <p class="mb-0 small text-muted"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha'])); ?></p>
                                <p><?php echo nl2br(htmlspecialchars($comentario['comentario'] ?? 'Sin comentario')); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted">No hay encuestas disponibles.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-trophy me-2"></i>Ranking de ventas</h4>
                    <p class="mb-3">Servicios y productos con mayor volumen de venta.</p>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <strong>Servicios</strong>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($topServicios as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                        <span>S/ <?php echo number_format($item['total_ventas'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-12">
                            <strong>Productos</strong>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($topProductos as $item): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?php echo htmlspecialchars($item['nombre']); ?>
                                        <span>S/ <?php echo number_format($item['total_ventas'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="report-card">
                    <h4><i class="fas fa-medkit me-2"></i>Insumos críticos</h4>
                    <p class="mb-3">Productos con stock en nivel mínimo o crítico.</p>
                    <?php if (!empty($insumosCriticos)): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                    <th>Mínimo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($insumosCriticos as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo intval($item['cantidad_fisica']); ?></td>
                                        <td><?php echo intval($item['stock_minimo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted">No hay insumos críticos en este momento.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-12">
                <div class="report-card">
                    <h4><i class="fas fa-file-invoice-dollar me-2"></i>Pagos recientes</h4>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Factura</th>
                                <th>Método</th>
                                <th>Monto</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagosRecientes as $pago): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pago['numero_factura']); ?></td>
                                    <td><?php echo htmlspecialchars($pago['metodo_pago']); ?></td>
                                    <td>S/ <?php echo number_format($pago['monto'], 2); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($pago['fecha_pago'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($pagosRecientes)): ?>
                                <tr><td colspan="4" class="text-center text-muted">No hay pagos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
