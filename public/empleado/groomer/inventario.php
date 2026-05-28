<?php
require_once "../../../config/database.php";
require_once "../../../config/constants.php";
require_once "../../../core/Auth.php";
require_once "../../../core/middleware.php";
require_once "../../../core/helpers.php";
require_once "../../../core/Security.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['producto_id'], $_POST['csrf_token'])) {
    if (!Security::validateCSRFToken($_POST['csrf_token'])) {
        $messages[] = ['type' => 'danger', 'text' => 'Token de seguridad inválido.'];
    } else {
        $productoId = intval($_POST['producto_id']);
        $ajustar = intval($_POST['ajustar'] ?? 0);
        try {
            $stmt = $conn->prepare('SELECT id_producto, nombre, stock_actual FROM producto WHERE id_producto = ?');
            $stmt->execute([$productoId]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($producto) {
                $nuevoStock = max(0, $producto['stock_actual'] - abs($ajustar));
                $stmt = $conn->prepare('UPDATE producto SET stock_actual = ? WHERE id_producto = ?');
                $stmt->execute([$nuevoStock, $productoId]);
                $messages[] = ['type' => 'success', 'text' => "Stock actualizado para {$producto['nombre']}."];
            } else {
                $messages[] = ['type' => 'warning', 'text' => 'Producto no encontrado.'];
            }
        } catch (Exception $e) {
            $messages[] = ['type' => 'danger', 'text' => 'Error al actualizar el inventario.'];
        }
    }
}

$productos = [];
try {
    $stmt = $conn->query('SELECT id_producto, nombre, descripcion, precio_base, stock_actual, stock_minimo, estado_activo FROM producto ORDER BY nombre');
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Inventario de Grooming</h1>
            <p class="text-muted mb-0">Controla consumibles y prepara materiales para el servicio.</p>
        </div>
        <a class="btn btn-secondary" href="/petspa/public/empleado/groomer/dashboard.php">Volver al panel</a>
    </div>

    <?php foreach ($messages as $message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>"><?php echo htmlspecialchars($message['text']); ?></div>
    <?php endforeach; ?>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Producto</th>
                    <th>Precio</th>
                    <th>Stock Actual</th>
                    <th>Mínimo</th>
                    <th>Disponibilidad</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                        <td><?php echo number_format($producto['precio_base'], 2); ?></td>
                        <td><?php echo intval($producto['stock_actual']); ?></td>
                        <td><?php echo intval($producto['stock_minimo']); ?></td>
                        <td>
                            <?php if (!$producto['estado_activo']): ?>
                                <span class="badge bg-secondary">Inactivo</span>
                            <?php elseif ($producto['stock_actual'] <= $producto['stock_minimo']): ?>
                                <span class="badge bg-warning text-dark">Bajo stock</span>
                            <?php else: ?>
                                <span class="badge bg-success">Suficiente</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="" method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                <input type="hidden" name="producto_id" value="<?php echo intval($producto['id_producto']); ?>">
                                <input type="hidden" name="ajustar" value="1">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Usar 1</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
