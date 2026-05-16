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

try {
$stmt = $conn->prepare("
SELECT 
    p.id_producto,
    p.nombre,
    p.precio_base,
    p.stock_actual AS stock,

    COALESCE(c.nombre, 'Sin categoría') AS categoria

FROM producto p

LEFT JOIN categoria_producto c
ON p.id_categoria = c.id_categoria

ORDER BY p.nombre ASC
");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario Recepcionista - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Inventario</h2>
            <a href="/petspa/public/empleado/recepcionista/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>

        <?php if (!empty($productos)): ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria'] ?? 'Sin categoría'); ?></td>
                                <td><?php echo htmlspecialchars(number_format($producto['precio_base'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($producto['stock']); ?></td>
                                <td><?php echo $producto['stock'] > 0 ? '<span class="badge bg-success">Disponible</span>' : '<span class="badge bg-danger">Sin stock</span>'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">No se encontraron productos en el inventario.</div>
        <?php endif; ?>
    </div>
</body>
</html>
