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
    $stmt = $conn->prepare(
        "SELECT cl.id_cliente, CONCAT(u.nombre, ' ', u.apellido) AS cliente_nombre, u.email, u.telefono
            FROM cliente cl
            JOIN usuario u ON cl.id_cliente = u.id_usuario
            ORDER BY u.nombre, u.apellido"
    );
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT id_servicio, nombre, precio_base FROM servicio ORDER BY nombre");
    $stmt->execute();
    $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT id_producto, nombre FROM producto ORDER BY nombre");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT n.*, CONCAT(u.nombre, ' ', u.apellido) AS cliente_nombre
            FROM notificacion n
            LEFT JOIN cliente cl ON n.id_cliente = cl.id_cliente
            LEFT JOIN usuario u ON cl.id_cliente = u.id_usuario
            WHERE n.tipo_evento = 'promocion'
            ORDER BY n.fecha_programacion DESC
            LIMIT 50"
    );
    $stmt->execute();
    $promociones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        "SELECT p.*, GROUP_CONCAT(DISTINCT CONCAT('S:', ps.id_servicio) SEPARATOR ', ') AS servicios, GROUP_CONCAT(DISTINCT CONCAT('P:', pp.id_producto) SEPARATOR ', ') AS productos
            FROM promocion p
            LEFT JOIN promocion_servicio ps ON p.id_promocion = ps.id_promocion
            LEFT JOIN promocion_producto pp ON p.id_promocion = pp.id_promocion
            WHERE p.activo = 1
            GROUP BY p.id_promocion
            ORDER BY p.fecha_inicio DESC"
    );
    $stmt->execute();
    $ofertas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $clientes = [];    $servicios = [];
    $productos = [];    $promociones = [];
    $ofertas = [];
}

$backUrl = $currentUser['rol'] === ROLE_ADMIN ? '/petspa/public/empleado/admin/dashboard.php' : '/petspa/public/empleado/recepcionista/dashboard.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promociones - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Promociones y Comunicaciones</h2>
            <p class="text-muted mb-0">Envia promociones a clientes suscritos y revisa el historial de promociones programadas.</p>
        </div>
        <a href="<?php echo $backUrl; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">Promoción registrada correctamente y enviada al sistema de notificaciones.</div>
    <?php endif; ?>

    <div class="row gy-4">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i> Crear promoción</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/empleado/promociones.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mensaje</label>
                            <textarea name="mensaje" class="form-control" rows="4" required placeholder="Ej: Descuento del 20% en grooming este fin de semana."></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Canal</label>
                                <select name="canal" class="form-select" required>
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="sms">SMS</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Programar envío</label>
                                <input type="datetime-local" name="programar_fecha" class="form-control">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Cliente</label>
                            <select name="cliente_id" class="form-select">
                                <option value="">Seleccionar cliente</option>
                                <?php foreach ($clientes as $cliente): ?>
                                    <option value="<?php echo $cliente['id_cliente']; ?>"><?php echo htmlspecialchars($cliente['cliente_nombre'] . ' - ' . ($cliente['email'] ?: $cliente['telefono'])); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="enviar_todos" id="enviarTodos">
                            <label class="form-check-label" for="enviarTodos">Enviar a todos los clientes suscritos a promociones</label>
                        </div>
                        <button class="btn btn-success">Guardar promoción</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-percent me-2"></i> Crear oferta o descuento</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/empleado/promocion_ofertas.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nombre de la oferta</label>
                            <input type="text" name="nombre" class="form-control" required placeholder="Nombre de la promoción">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="3" placeholder="Breve descripción de la oferta"></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Tipo de oferta</label>
                                <select name="tipo" class="form-select" required>
                                    <option value="descuento_servicio">Descuento por servicio</option>
                                    <option value="descuento_producto">Descuento por producto</option>
                                    <option value="pack_servicio">Pack de servicios</option>
                                    <option value="pack_producto">Pack de productos</option>
                                    <option value="2x1_servicio">2x1 en servicio</option>
                                    <option value="2x1_producto">2x1 en producto</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Activo</label>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="activo" id="activoOferta" checked>
                                    <label class="form-check-label" for="activoOferta">Activo</label>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Seleccionar servicio</label>
                                <select name="servicio_id" class="form-select">
                                    <option value="">Seleccionar servicio</option>
                                    <?php foreach ($servicios as $servicio): ?>
                                        <option value="<?php echo $servicio['id_servicio']; ?>"><?php echo htmlspecialchars($servicio['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Seleccionar producto</label>
                                <select name="producto_id" class="form-select">
                                    <option value="">Seleccionar producto</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?php echo $producto['id_producto']; ?>"><?php echo htmlspecialchars($producto['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">Valor / %</label>
                                <input type="number" name="valor" class="form-control" step="0.01" placeholder="20">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Precio oferta</label>
                                <input type="number" name="precio_oferta" class="form-control" step="0.01" placeholder="Precio especial">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Cantidad</label>
                                <input type="number" name="cantidad_requerida" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-6 col-md-2">
                                <label class="form-label">Regalo</label>
                                <input type="number" name="cantidad_regalo" class="form-control" min="0" value="0">
                            </div>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Inicio</label>
                                <input type="date" name="fecha_inicio" class="form-control" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Fin</label>
                                <input type="date" name="fecha_fin" class="form-control" required>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="btn btn-primary">Guardar oferta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Historial de promociones</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Canal</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($promociones)): ?>
                                    <?php foreach ($promociones as $promo): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($promo['fecha_programacion']); ?></td>
                                            <td><?php echo htmlspecialchars($promo['cliente_nombre'] ?: 'Todos'); ?></td>
                                            <td><?php echo htmlspecialchars(strtoupper($promo['canal'])); ?></td>
                                            <td><?php echo htmlspecialchars($promo['destino'] ?: '-'); ?></td>
                                            <td><span class="badge bg-<?php echo $promo['estado_envio'] === 'enviado' ? 'success' : ($promo['estado_envio'] === 'fallido' ? 'danger' : 'warning'); ?>"><?php echo htmlspecialchars(ucfirst($promo['estado_envio'])); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay promociones registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row gy-4 mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i> Ofertas activas</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Rango</th>
                                    <th>Valor / Precio</th>
                                    <th>Servicios / Productos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($ofertas)): ?>
                                    <?php foreach ($ofertas as $oferta): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($oferta['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars(str_replace('_', ' ', $oferta['tipo'])); ?></td>
                                            <td><?php echo htmlspecialchars($oferta['fecha_inicio'] . ' - ' . $oferta['fecha_fin']); ?></td>
                                            <td>
                                                <?php
                                                    if ($oferta['precio_oferta'] !== null) {
                                                        echo '$' . number_format($oferta['precio_oferta'], 2);
                                                    } elseif ($oferta['tipo'] === 'descuento_servicio' || $oferta['tipo'] === 'descuento_producto') {
                                                        echo htmlspecialchars($oferta['valor'] . '%');
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($oferta['servicios'] ?: $oferta['productos'] ?: 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No hay ofertas activas.</td>
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
