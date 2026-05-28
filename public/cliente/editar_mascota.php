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

$idMascota = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mascota = null;

if ($idMascota > 0) {
    $stmt = $conn->prepare('SELECT * FROM mascota WHERE id_mascota = ? AND id_cliente_principal = ?');
    $stmt->execute([$idMascota, $clienteId]);
    $mascota = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mascota) {
        header('Location: /petspa/public/cliente/mascotas.php');
        exit();
    }
}

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mascota ? 'Editar Mascota' : 'Registrar Mascota'; ?> - Pet Spa</title>
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
                <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/mascotas.php">Mis Mascotas</a></li>
                <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/citas.php">Citas</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item"><a class="nav-link" href="/petspa/api/auth/logout.php">Cerrar sesión</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="mb-0"><?php echo $mascota ? 'Editar Mascota' : 'Registrar Mascota'; ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($success === 'updated'): ?>
                        <div class="alert alert-success">Datos actualizados correctamente.</div>
                    <?php endif; ?>
                    <form action="/petspa/api/cliente/mascotas.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                        <input type="hidden" name="action" value="<?php echo $mascota ? 'update' : 'create'; ?>">
                        <?php if ($mascota): ?>
                            <input type="hidden" name="id_mascota" value="<?php echo $mascota['id_mascota']; ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($mascota['nombre'] ?? ''); ?>" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Especie</label>
                                <input type="text" name="especie" class="form-control" value="<?php echo htmlspecialchars($mascota['especie'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Raza</label>
                                <input type="text" name="raza" class="form-control" value="<?php echo htmlspecialchars($mascota['raza'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-md-4">
                                <label class="form-label">Edad (años)</label>
                                <input type="number" name="edad" min="0" class="form-control" value="<?php echo htmlspecialchars($mascota['edad'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Peso (kg)</label>
                                <input type="number" name="peso" min="0" step="0.1" class="form-control" value="<?php echo htmlspecialchars($mascota['peso'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tamaño</label>
                                <input type="text" name="tamano" class="form-control" value="<?php echo htmlspecialchars($mascota['tamano'] ?? ''); ?>" placeholder="Pequeño / Mediano / Grande">
                            </div>
                        </div>
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label">Vacunas</label>
                                <input type="text" name="vacunas" class="form-control" value="<?php echo htmlspecialchars($mascota['vacunas'] ?? ''); ?>" placeholder="Ej: Rabia, Parvovirus">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Temperamento</label>
                                <input type="text" name="temperamento" class="form-control" value="<?php echo htmlspecialchars($mascota['temperamento'] ?? ''); ?>" placeholder="Ej: Tranquilo, Activo">
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Comportamiento / notas</label>
                            <textarea name="notas" class="form-control" rows="4"><?php echo htmlspecialchars($mascota['comportamiento'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto de la mascota</label>
                            <input type="file" name="foto" class="form-control">
                            <?php if (!empty($mascota['foto'])): ?>
                                <small class="text-muted">Imagen actual: <a href="<?php echo htmlspecialchars($mascota['foto']); ?>" target="_blank">Ver</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/petspa/public/cliente/mascotas.php" class="btn btn-secondary">Volver</a>
                            <button type="submit" class="btn btn-primary"><?php echo $mascota ? 'Guardar cambios' : 'Registrar mascota'; ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
