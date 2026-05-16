<?php
require_once "../../../config/database.php";
require_once "../../../core/middleware.php";
require_once "../../../core/Auth.php";

// Inicializar Auth
Auth::setConnection($conn);

Middleware::requireAdmin();

// traer productos por categoría
$sql = "SELECT p.*, c.nombre as categoria
        FROM producto p
        JOIN categoria_producto c ON p.id_categoria = c.id_categoria";

$productos = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// agrupar por categoría
$categorias = [
    'Accesorios' => [],
    'Salud' => [],
    'Alimentos' => []
];

foreach ($productos as $p) {
    $categorias[$p['categoria']][] = $p;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2>Gestión de Productos</h2>

    <!-- FORM CREAR -->
    <form action="/petspa/api/admin/productos.php" method="POST" class="mb-4">
        <input type="text" name="nombre" placeholder="Nombre" class="form-control mb-2" required>
        <input type="number" step="0.01" name="precio" placeholder="Precio" class="form-control mb-2" required>
        <input type="number" name="stock" placeholder="Stock" class="form-control mb-2" required>

        <select name="categoria" class="form-control mb-2">
            <option value="1">Accesorios</option>
            <option value="2">Salud</option>
            <option value="3">Alimentos</option>
        </select>

        <button class="btn btn-success">Crear Producto</button>
    </form>

    <?php foreach ($categorias as $nombre => $lista): ?>
        <h4 class="mt-4"><?= $nombre ?></h4>

        <table class="table table-bordered">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Stock</th>
                <th>Acciones</th>
            </tr>

            <?php foreach ($lista as $p): ?>
            <tr>
                <td><?= $p['id_producto'] ?></td>
                <td><?= $p['nombre'] ?></td>
                <td><?= $p['precio_base'] ?></td>
                <td><?= $p['stock_actual'] ?></td>
                <td>
                    <a href="editar_producto.php?id=<?= $p['id_producto'] ?>" class="btn btn-warning btn-sm">Editar</a>
                    <a href="/petspa/api/admin/eliminar_producto.php?id=<?= $p['id_producto'] ?>" 
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('¿Eliminar?')">Eliminar</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endforeach; ?>

</div>
    <div class="col-md-4">
        <a href="dashboard.php" class="card p-3 bg-warning text-dark d-block text-decoration-none">
            Volver al Panel Admin
        </a>
    </div>
</body>
</html>