<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success = isset($_GET['success']);

try {
    $stmt = $conn->prepare(
        "SELECT c.id_cita, c.fecha_inicio, c.fecha_fin, c.estado, m.nombre AS mascota_nombre, CONCAT(u_cliente.nombre, ' ', u_cliente.apellido) AS cliente_nombre, CONCAT(u_groomer.nombre, ' ', u_groomer.apellido) AS groomer_nombre, s.nombre AS servicio_nombre, s.precio_base
            FROM cita c
            JOIN mascota m ON c.id_mascota = m.id_mascota
            JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
            JOIN usuario u_cliente ON cl.id_cliente = u_cliente.id_usuario
            JOIN groomer g ON c.id_groomer = g.id_groomer
            JOIN usuario u_groomer ON g.id_groomer = u_groomer.id_usuario
            JOIN servicio s ON c.id_servicio = s.id_servicio
            WHERE c.estado NOT IN ('cancelada')
            AND c.id_cita NOT IN (SELECT id_cita FROM factura WHERE id_cita IS NOT NULL)
            ORDER BY c.fecha_inicio ASC"
    );
    $stmt->execute();
    $citasPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT ca.id_carrito, ca.fecha_creacion, ca.total, ca.estado_pedido, CONCAT(u_cliente.nombre, ' ', u_cliente.apellido) AS cliente_nombre
            FROM carrito ca
            JOIN cliente cl ON ca.id_cliente = cl.id_cliente
            JOIN usuario u_cliente ON cl.id_cliente = u_cliente.id_usuario
            WHERE ca.estado_pedido NOT IN ('cancelado')
            AND ca.id_carrito NOT IN (SELECT id_pedido FROM factura WHERE id_pedido IS NOT NULL)
            ORDER BY ca.fecha_creacion ASC"
    );
    $stmt->execute();
    $pedidosPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT f.id_factura, f.numero_factura, f.fecha_emision, f.subtotal, f.impuesto, f.total, f.estado_factura,
                COALESCE(SUM(p.monto), 0) AS pagado,
                f.total - COALESCE(SUM(p.monto), 0) AS saldo,
                c.id_cita, ca.id_carrito,
                COALESCE(CONCAT(u_cliente.nombre, ' ', u_cliente.apellido), ca.contacto_destino) AS cliente_nombre
            FROM factura f
            LEFT JOIN pago p ON p.id_factura = f.id_factura
            LEFT JOIN cita c ON f.id_cita = c.id_cita
            LEFT JOIN mascota m ON c.id_mascota = m.id_mascota
            LEFT JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
            LEFT JOIN usuario u_cliente ON cl.id_cliente = u_cliente.id_usuario
            LEFT JOIN carrito ca ON f.id_pedido = ca.id_carrito
            GROUP BY f.id_factura
            ORDER BY f.estado_factura = 'pendiente' DESC, f.fecha_emision DESC
            LIMIT 50"
    );
    $stmt->execute();
    $facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT f.id_factura, f.numero_factura, f.total, COALESCE(SUM(p.monto), 0) AS pagado, f.total - COALESCE(SUM(p.monto), 0) AS saldo
            FROM factura f
            LEFT JOIN pago p ON p.id_factura = f.id_factura
            WHERE f.estado_factura = 'pendiente'
            GROUP BY f.id_factura
            ORDER BY f.fecha_emision DESC"
    );
    $stmt->execute();
    $facturasPendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $citasPendientes = [];
    $pedidosPendientes = [];
    $facturas = [];
    $facturasPendientes = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cobrar servicios - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Cobrar servicios</h2>
            <p class="text-muted mb-0">Genera facturas y registra pagos para citas agendadas.</p>
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
        <div class="alert alert-success">Factura y pago registrados correctamente.</div>
    <?php endif; ?>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-file-invoice-dollar me-2"></i> Generar factura y pago</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($citasPendientes) || !empty($pedidosPendientes)): ?>
                        <form action="/petspa/api/empleado/cobrar.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Cita</label>
                                <select name="cita_id" class="form-select">
                                    <option value="">Selecciona una cita</option>
                                    <?php foreach ($citasPendientes as $cita): ?>
                                        <option value="<?php echo $cita['id_cita']; ?>">
                                            <?php echo htmlspecialchars($cita['fecha_inicio'] . ' - ' . $cita['cliente_nombre'] . ' / ' . $cita['mascota_nombre'] . ' / ' . $cita['servicio_nombre'] . ' / Precio: ' . number_format($cita['precio_base'], 2)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Pedido</label>
                                <select name="pedido_id" class="form-select">
                                    <option value="">Selecciona un pedido</option>
                                    <?php foreach ($pedidosPendientes as $pedido): ?>
                                        <option value="<?php echo $pedido['id_carrito']; ?>">
                                            <?php echo htmlspecialchars($pedido['fecha_creacion'] . ' - ' . $pedido['cliente_nombre'] . ' / Total: ' . number_format($pedido['total'], 2)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="alert alert-secondary small">
                                Selecciona solo una opción: una cita o un pedido. Si dejas ambas vacías, no se generará factura.
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Monto</label>
                                    <input type="number" name="monto" class="form-control" step="0.01" min="0" placeholder="Monto a pagar" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Método de pago</label>
                                    <select name="metodo_pago" class="form-select" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="qr">QR</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="tarjeta">Tarjeta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Referencia transacción</label>
                                <input type="text" name="referencia_transaccion" class="form-control" placeholder="Opcional">
                            </div>
                            <button class="btn btn-primary mt-4">Registrar factura y pago</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No hay citas ni pedidos disponibles para facturar en este momento.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i> Registrar pago a factura existente</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($facturasPendientes)): ?>
                        <form action="/petspa/api/empleado/cobrar.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Factura pendiente</label>
                                <select id="facturaPendienteSelect" name="factura_id" class="form-select" required>
                                    <option value="" data-total="0" data-pagado="0" data-saldo="0">Selecciona una factura</option>
                                    <?php foreach ($facturasPendientes as $facturaPendiente): ?>
                                        <option value="<?php echo $facturaPendiente['id_factura']; ?>"
                                                data-total="<?php echo number_format($facturaPendiente['total'], 2, '.', ''); ?>"
                                                data-pagado="<?php echo number_format($facturaPendiente['pagado'], 2, '.', ''); ?>"
                                                data-saldo="<?php echo number_format($facturaPendiente['saldo'], 2, '.', ''); ?>">
                                            <?php echo htmlspecialchars($facturaPendiente['numero_factura'] . ' - Total: ' . number_format($facturaPendiente['total'], 2) . ' - Saldo: ' . number_format($facturaPendiente['saldo'], 2)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="facturaPendienteInfo" class="alert alert-info small d-none">
                                <div><strong>Total factura:</strong> <span id="facturaTotal">0.00</span></div>
                                <div><strong>Pagado hasta ahora:</strong> <span id="facturaPagado">0.00</span></div>
                                <div><strong>Saldo pendiente:</strong> <span id="facturaSaldo">0.00</span></div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label class="form-label">Monto</label>
                                    <input type="number" name="monto" class="form-control" step="0.01" min="0" placeholder="Monto a pagar" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Método de pago</label>
                                    <select name="metodo_pago" class="form-select" required>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="qr">QR</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="tarjeta">Tarjeta</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Referencia transacción</label>
                                <input type="text" name="referencia_transaccion" class="form-control" placeholder="Opcional">
                            </div>
                            <button class="btn btn-primary mt-4">Registrar pago</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">No hay facturas pendientes de pago.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i> Facturas recientes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <a href="/petspa/public/empleado/facturacion/generar_factura.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-file-pdf me-1"></i> Generar factura PDF
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Pagado</th>
                                    <th>Saldo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($facturas)): ?>
                                    <?php foreach ($facturas as $factura): ?>
                                        <tr>
                                            <td><?php echo $factura['id_factura']; ?></td>
                                            <td><?php echo htmlspecialchars($factura['numero_factura']); ?></td>
                                            <td><?php echo htmlspecialchars($factura['cliente_nombre'] ?? '-'); ?></td>
                                            <td><?php echo number_format($factura['total'], 2); ?></td>
                                            <td><?php echo number_format($factura['pagado'], 2); ?></td>
                                            <td><?php echo number_format($factura['saldo'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $factura['estado_factura'] === 'pagada' ? 'success' : ($factura['estado_factura'] === 'pendiente' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($factura['estado_factura'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/petspa/api/empleado/recibo_pdf.php?id=<?php echo $factura['id_factura']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-file-pdf me-1"></i> PDF
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">No se han generado facturas recientes.</td>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const facturaSelect = document.getElementById('facturaPendienteSelect');
        const info = document.getElementById('facturaPendienteInfo');
        const totalEl = document.getElementById('facturaTotal');
        const pagadoEl = document.getElementById('facturaPagado');
        const saldoEl = document.getElementById('facturaSaldo');

        function updateFacturaInfo() {
            const option = facturaSelect.options[facturaSelect.selectedIndex];
            const total = parseFloat(option.dataset.total || '0').toFixed(2);
            const pagado = parseFloat(option.dataset.pagado || '0').toFixed(2);
            const saldo = parseFloat(option.dataset.saldo || '0').toFixed(2);

            if (facturaSelect.value) {
                totalEl.textContent = total;
                pagadoEl.textContent = pagado;
                saldoEl.textContent = saldo;
                info.classList.remove('d-none');
            } else {
                info.classList.add('d-none');
            }
        }

        if (facturaSelect) {
            facturaSelect.addEventListener('change', updateFacturaInfo);
            updateFacturaInfo();
        }
    });
</script>
</body>
</html>
