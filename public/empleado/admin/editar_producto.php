<?php
require_once "../../../config/database.php";

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM producto WHERE id_producto = :id");
$stmt->bindParam(":id", $id, PDO::PARAM_INT);
$stmt->execute();
$p = $stmt->fetch(PDO::FETCH_ASSOC);

$categorias = $conn->query("SELECT id_categoria, nombre FROM categoria_producto ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2>Editar Producto</h2>
    <form action="/petspa/api/admin/editar_producto.php" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($p['id_producto']); ?>">

        <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($p['nombre']); ?>" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($p['descripcion']); ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Precio</label>
                <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($p['precio_base']); ?>" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Stock Actual</label>
                <input type="number" name="stock" value="<?= htmlspecialchars($p['stock_actual']); ?>" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Stock Mínimo</label>
                <input type="number" name="stock_minimo" value="<?= htmlspecialchars($p['stock_minimo']); ?>" class="form-control" min="0">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Categoría</label>
            <select name="categoria" class="form-select">
                <option value="">Seleccione una categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= $categoria['id_categoria']; ?>" <?= $categoria['id_categoria'] == $p['id_categoria'] ? 'selected' : ''; ?>><?= htmlspecialchars($categoria['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado" class="form-select">
                <option value="1" <?= $p['estado_activo'] ? 'selected' : ''; ?>>Activo</option>
                <option value="0" <?= !$p['estado_activo'] ? 'selected' : ''; ?>>Inactivo</option>
            </select>
        </div>

        <button class="btn btn-primary">Actualizar</button>
        <a href="productos.php" class="btn btn-secondary ms-2">Cancelar</a>
    </form>
</div>
</body>
</html>