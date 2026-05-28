<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/Security.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$clienteId = $currentUser['id'];

$mascotas = [];
try {
    $stmt = $conn->prepare(
        'SELECT DISTINCT m.*, 
            CASE WHEN m.id_cliente_principal = :cliente THEN "Propietario principal" ELSE "Co-propietario" END AS propietario_tipo
         FROM mascota m
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_cliente_principal = :cliente OR md.id_cliente = :cliente
         ORDER BY m.nombre'
    );
    $stmt->execute([':cliente' => $clienteId]);
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mascotas = [];
}

$errors = $_SESSION['mascota_errors'] ?? [];
unset($_SESSION['mascota_errors']);

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Mascotas - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/petspa/public/cliente/dashboard.php">Pet Spa</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/dashboard.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link active" href="/petspa/public/cliente/mascotas.php">Mis Mascotas</a></li>
                <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/citas.php">Citas</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="/petspa/api/auth/logout.php">Cerrar sesión</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Mis Mascotas</h1>
            <p class="text-muted">Administra los datos de tus mascotas y agrega nueva información.</p>
        </div>
        <a href="/petspa/public/cliente/editar_mascota.php" class="btn btn-success">Registrar Nueva Mascota</a>
    </div>

    <?php if ($success === 'created'): ?>
        <div class="alert alert-success">Mascota creada correctamente.</div>
    <?php elseif ($success === 'updated'): ?>
        <div class="alert alert-success">Mascota actualizada correctamente.</div>
    <?php elseif ($success === 'deleted'): ?>
        <div class="alert alert-success">Mascota eliminada correctamente.</div>
    <?php elseif ($success === 'server'): ?>
        <div class="alert alert-danger">Ocurrió un error del servidor.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($mascotas)): ?>
        <div class="row g-3">
            <?php foreach ($mascotas as $mascota): ?>
                <div class="col-md-6">
                    <div class="card h-100">
                        <?php if (!empty($mascota['foto'])): ?>
                            <img src="<?php echo htmlspecialchars($mascota['foto']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($mascota['nombre']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-info text-dark"><?php echo htmlspecialchars($mascota['propietario_tipo']); ?></span>
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($mascota['nombre']); ?></h5>
                            <p class="card-text mb-1"><strong>Especie:</strong> <?php echo htmlspecialchars($mascota['especie']); ?></p>
                            <p class="card-text mb-1"><strong>Raza:</strong> <?php echo htmlspecialchars($mascota['raza']); ?></p>
                            <p class="card-text mb-1"><strong>Edad:</strong> <?php echo htmlspecialchars($mascota['edad'] ?? 'No definido'); ?> años</p>
                            <p class="card-text mb-1"><strong>Tamaño:</strong> <?php echo htmlspecialchars($mascota['tamano'] ?? 'No definido'); ?></p>
                            <p class="card-text mb-1"><strong>Temperamento:</strong> <?php echo htmlspecialchars($mascota['temperamento'] ?? 'No definido'); ?></p>
                            <p class="card-text"><strong>Comportamiento:</strong> <?php echo htmlspecialchars($mascota['comportamiento'] ?? 'No definido'); ?></p>
                        </div>
                        <div class="card-footer d-flex justify-content-between">
                            <?php if ($mascota['id_cliente_principal'] == $clienteId): ?>
                                <a href="/petspa/public/cliente/editar_mascota.php?id=<?php echo $mascota['id_mascota']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <form action="/petspa/api/cliente/mascotas.php" method="POST" onsubmit="return confirm('¿Eliminar esta mascota?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_mascota" value="<?php echo $mascota['id_mascota']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">Eliminar</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-sm" disabled>No editable desde esta cuenta</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-paw fa-3x text-primary mb-3"></i>
                <h4>No tienes mascotas registradas</h4>
                <p class="text-muted">Registra una mascota para poder agendar citas y personalizar su atención.</p>
                <a href="/petspa/public/cliente/editar_mascota.php" class="btn btn-primary mt-3">Registrar Mascota</a>
            </div>
        </div>
    <?php endif; ?>
</div>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
