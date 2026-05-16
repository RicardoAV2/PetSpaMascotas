<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/middleware.php';
require_once __DIR__ . '/../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    $stmt = $conn->prepare("SELECT u.*, r.nombre AS rol_nombre FROM usuario u JOIN rol r ON u.id_rol = r.id_rol WHERE u.id_usuario = ?");
    $stmt->execute([$currentUser['id']]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $profile = null;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><i class="fas fa-user-circle me-2"></i> Mi Perfil</h3>
            </div>
            <div class="card-body">
                <?php if ($profile): ?>
                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($profile['nombre'] . ' ' . $profile['apellido']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($profile['email']); ?></p>
                    <p><strong>Rol:</strong> <?php echo htmlspecialchars($profile['rol_nombre']); ?></p>
                    <p><strong>Estado:</strong> <?php echo htmlspecialchars($profile['estado']); ?></p>
                    <p><strong>Registrado:</strong> <?php echo htmlspecialchars($profile['fecha_creacion'] ?? '-'); ?></p>
                <?php else: ?>
                    <div class="alert alert-warning">No se pudo cargar la información del perfil.</div>
                <?php endif; ?>
                <a href="/petspa/public/login.php" class="btn btn-secondary">Volver</a>
            </div>
        </div>
    </div>
</body>
</html>
