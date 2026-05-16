<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAdmin();
Middleware::checkSessionTimeout();

try {
    $sql = "SELECT u.id_usuario, u.email, u.nombre, u.apellido, u.estado, r.nombre AS rol
            FROM usuario u
            JOIN rol r ON u.id_rol = r.id_rol
            WHERE r.nombre IN ('admin', 'recepcion', 'groomer')
            ORDER BY u.fecha_creacion DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $empleados = [];
}

$roles = [
    ROLE_ADMIN => 'Administrador',
    ROLE_RECEPCION => 'Recepcionista',
    ROLE_GROOMER => 'Groomer'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Admin - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestión de Empleados</h2>
            <a href="/petspa/public/empleado/admin/dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Crear nuevo empleado</h5>
            </div>
            <div class="card-body">
                <form action="/petspa/api/admin/usuarios.php" method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Correo</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Rol</label>
                            <select name="rol" class="form-control" required>
                                <option value="1">Administrador</option>
                                <option value="3">Recepcionista</option>
                                <option value="4">Groomer</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-success">Crear empleado</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Empleados registrados</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($empleados)): ?>
                                <?php foreach ($empleados as $e): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($e['id_usuario']); ?></td>
                                        <td><?php echo htmlspecialchars($e['nombre'] . ' ' . $e['apellido']); ?></td>
                                        <td><?php echo htmlspecialchars($e['email']); ?></td>
                                        <td><?php echo htmlspecialchars($roles[$e['rol']] ?? ucfirst($e['rol'])); ?></td>
                                        <td><?php echo htmlspecialchars($e['estado']); ?></td>
                                        <td>
                                            <a href="editar.php?id=<?php echo $e['id_usuario']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                            <a href="/petspa/api/admin/eliminar_usuario.php?id=<?php echo $e['id_usuario']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Eliminar usuario?');">Eliminar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-3">No hay empleados registrados.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
