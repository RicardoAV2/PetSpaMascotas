<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/invoice_types.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$invoiceTypes = require __DIR__ . '/../../../config/invoice_types.php';

try {
    $stmt = $conn->prepare(
        "SELECT f.id_factura, f.numero_factura, f.fecha_emision, f.total, f.estado_factura,
                COALESCE(CONCAT(u.nombre, ' ', u.apellido), ca.contacto_destino, 'Cliente no identificado') AS cliente_nombre
            FROM factura f
            LEFT JOIN cita c ON f.id_cita = c.id_cita
            LEFT JOIN mascota m ON c.id_mascota = m.id_mascota
            LEFT JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
            LEFT JOIN usuario u ON cl.id_cliente = u.id_usuario
            LEFT JOIN carrito ca ON f.id_pedido = ca.id_carrito
            ORDER BY f.fecha_emision DESC
            LIMIT 40"
    );
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $facturas = [];
}

$backUrl = $currentUser['rol'] === ROLE_ADMIN ? '/petspa/public/empleado/admin/dashboard.php' : '/petspa/public/empleado/recepcionista/dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar factura PDF - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Generar factura en PDF</h2>
            <p class="text-muted mb-0">Descarga facturas como archivo PDF y revisa los tipos de factura disponibles.</p>
        </div>
        <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row gy-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Tipos de factura</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Estos son los tipos de factura definidos en el sistema. Actualmente el PDF se genera con el formato de recibo simple para cualquier factura existente.</p>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($invoiceTypes as $code => $label): ?>
                            <li class="list-group-item">
                                <strong><?php echo htmlspecialchars($label); ?></strong>
                                <span class="text-muted d-block small">Código: <?php echo htmlspecialchars($code); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Facturas recientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($facturas)): ?>
                                    <?php foreach ($facturas as $factura): ?>
                                        <tr>
                                            <td><?php echo $factura['id_factura']; ?></td>
                                            <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                            <td><?php echo htmlspecialchars($factura['cliente_nombre']); ?></td>
                                            <td><?php echo number_format($factura['total'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $factura['estado_factura'] === 'pagada' ? 'success' : ($factura['estado_factura'] === 'pendiente' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($factura['estado_factura'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/petspa/api/empleado/recibo_pdf.php?id=<?php echo $factura['id_factura']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-file-pdf me-1"></i> Descargar PDF
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No se encontraron facturas recientes.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
