<?php
require_once "../../../config/database.php";
require_once "../../../core/middleware.php";
require_once "../../../core/Auth.php";
require_once "../../../core/helpers.php";

Auth::setConnection($conn);
Middleware::requireAdmin();
Middleware::checkSessionTimeout();

try {
    $productos = $conn->query(
        "SELECT p.*, c.nombre AS categoria_nombre FROM producto p LEFT JOIN categoria_producto c ON p.id_categoria = c.id_categoria ORDER BY p.nombre"
    )->fetchAll(PDO::FETCH_ASSOC);

    $categorias = $conn->query("SELECT id_categoria, nombre, descripcion, id_padre FROM categoria_producto ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

    $inventarios = $conn->query(
        "SELECT i.*, p.nombre AS producto_nombre, v.atributo, v.valor FROM inventario i JOIN producto p ON i.id_producto = p.id_producto LEFT JOIN variante_producto v ON i.id_variante = v.id_variante ORDER BY p.nombre, i.id_inventario"
    )->fetchAll(PDO::FETCH_ASSOC);

    $movimientos = $conn->query(
        "SELECT m.*, i.id_producto, p.nombre AS producto_nombre, v.atributo, v.valor FROM movimiento_inventario m JOIN inventario i ON m.id_inventario = i.id_inventario JOIN producto p ON i.id_producto = p.id_producto LEFT JOIN variante_producto v ON i.id_variante = v.id_variante ORDER BY m.fecha_movimiento DESC LIMIT 50"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $productos = $categorias = $inventarios = $movimientos = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos e Inventario - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gestión de Productos e Inventario</h2>
            <p class="text-muted">Administra productos, categorías, inventario y movimientos en un solo lugar.</p>
        </div>
        <a href="dashboard.php" class="btn btn-secondary">Volver al panel</a>
    </div>

    <div class="row gy-4">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i> Nuevo Producto</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/admin/productos.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Precio Base</label>
                                <input type="number" step="0.01" name="precio" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Actual</label>
                                <input type="number" name="stock" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Mínimo</label>
                                <input type="number" name="stock_minimo" value="5" min="0" class="form-control">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Estado</label>
                                <select name="estado" class="form-select">
                                    <option value="1">Activo</option>
                                    <option value="0">Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria" class="form-select">
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-success">Crear producto</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i> Nuevo registro de inventario</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/admin/inventario.php" method="POST">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label class="form-label">Producto</label>
                            <select name="producto_id" class="form-select" required>
                                <option value="">Seleccione un producto</option>
                                <?php foreach ($productos as $producto): ?>
                                    <option value="<?php echo $producto['id_producto']; ?>"><?php echo htmlspecialchars($producto['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Variante (ID)</label>
                            <input type="number" name="variante_id" class="form-control" placeholder="ID de variante">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad física</label>
                                <input type="number" name="cantidad_fisica" class="form-control" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cantidad reservada</label>
                                <input type="number" name="cantidad_reservada" class="form-control" min="0" value="0">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ubicación</label>
                            <input type="text" name="ubicacion" class="form-control" placeholder="Bodega A, Estante 2">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Fecha de vencimiento</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                        </div>
                        <button class="btn btn-outline-success">Agregar inventario</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row gy-4 mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-box-open me-2"></i> Productos</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Stock mínimo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($productos)): ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr>
                                            <td><?php echo $producto['id_producto']; ?></td>
                                            <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($producto['categoria_nombre'] ?: 'Sin categoría'); ?></td>
                                            <td><?php echo number_format($producto['precio_base'], 2); ?></td>
                                            <td><?php echo intval($producto['stock_actual']); ?></td>
                                            <td><?php echo intval($producto['stock_minimo']); ?></td>
                                            <td><?php echo $producto['estado_activo'] ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>'; ?></td>
                                            <td>
                                                <a href="editar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                                <a href="/petspa/api/admin/eliminar_producto.php?id=<?php echo $producto['id_producto']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar producto?');">Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No hay productos registrados.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i> Inventario</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Variante</th>
                                    <th>Física</th>
                                    <th>Reservada</th>
                                    <th>Disponible</th>
                                    <th>Ubicación</th>
                                    <th>Vencimiento</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($inventarios)): ?>
                                    <?php foreach ($inventarios as $inv): ?>
                                        <tr>
                                            <td><?php echo $inv['id_inventario']; ?></td>
                                            <td><?php echo htmlspecialchars($inv['producto_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($inv['atributo'] ? $inv['atributo'] . ': ' . $inv['valor'] : '-'); ?></td>
                                            <td><?php echo intval($inv['cantidad_fisica']); ?></td>
                                            <td><?php echo intval($inv['cantidad_reservada']); ?></td>
                                            <td><?php echo intval($inv['cantidad_disponible']); ?></td>
                                            <td><?php echo htmlspecialchars($inv['ubicacion'] ?: '-'); ?></td>
                                            <td><?php echo htmlspecialchars($inv['fecha_vencimiento'] ?: '-'); ?></td>
                                            <td>
                                                <form action="/petspa/api/admin/inventario.php" method="POST" class="d-flex gap-1 flex-wrap">
                                                    <input type="hidden" name="action" value="update">
                                                    <input type="hidden" name="id_inventario" value="<?php echo $inv['id_inventario']; ?>">
                                                    <input type="number" name="cantidad_fisica" value="<?php echo intval($inv['cantidad_fisica']); ?>" class="form-control form-control-sm" style="width:100px;" min="0">
                                                    <input type="number" name="cantidad_reservada" value="<?php echo intval($inv['cantidad_reservada']); ?>" class="form-control form-control-sm" style="width:100px;" min="0">
                                                    <button class="btn btn-sm btn-primary">Guardar</button>
                                                </form>
                                                <a href="/petspa/api/admin/inventario.php?action=delete&id=<?php echo $inv['id_inventario']; ?>" class="btn btn-sm btn-danger mt-1" onclick="return confirm('Eliminar registro de inventario?');">Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" class="text-center">No hay registros de inventario.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-tags me-2"></i> Categorías</h5>
                </div>
                <div class="card-body">
                    <form action="/petspa/api/admin/categorias.php" method="POST" class="row gy-3 mb-4">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-4">
                            <input type="text" name="nombre" class="form-control" placeholder="Nombre de categoría" required>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="descripcion" class="form-control" placeholder="Descripción">
                        </div>
                        <div class="col-md-3">
                            <select name="id_padre" class="form-select">
                                <option value="">Categoria padre (opcional)</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-success w-100">Crear</button>
                        </div>
                    </form>
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Padre</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categorias)): ?>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?php echo $categoria['id_categoria']; ?></td>
                                            <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($categoria['descripcion']); ?></td>
                                            <td><?php echo htmlspecialchars($categoria['id_padre'] ? (function($categorias, $padreId) {
                                                $parent = '-';
                                                foreach ($categorias as $cat) {
                                                    if ($cat['id_categoria'] == $padreId) {
                                                        $parent = $cat['nombre'];
                                                        break;
                                                    }
                                                }
                                                return $parent;
                                            })($categorias, $categoria['id_padre']) : '-'); ?></td>
                                            <td>
                                                <a href="/petspa/api/admin/categorias.php?action=delete&id=<?php echo $categoria['id_categoria']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar categoría?');">Eliminar</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No hay categorías definidas.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Movimientos de inventario</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Variante</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Antes</th>
                                    <th>Después</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($movimientos)): ?>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td><?php echo $mov['id_movimiento']; ?></td>
                                            <td><?php echo htmlspecialchars($mov['producto_nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($mov['atributo'] ? $mov['atributo'] . ': ' . $mov['valor'] : '-'); ?></td>
                                            <td><?php echo htmlspecialchars($mov['tipo_movimiento']); ?></td>
                                            <td><?php echo intval($mov['cantidad']); ?></td>
                                            <td><?php echo intval($mov['cantidad_fisica_antes']); ?></td>
                                            <td><?php echo intval($mov['cantidad_fisica_despues']); ?></td>
                                            <td><?php echo htmlspecialchars($mov['fecha_movimiento']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No hay movimientos de inventario.</td></tr>
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
