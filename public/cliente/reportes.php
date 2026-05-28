<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    $stmt = $conn->prepare(
        'SELECT DISTINCT m.id_mascota, m.nombre AS mascota, m.especie, m.raza, m.peso, m.fecha_registro
         FROM mascota m
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_cliente_principal = :cliente OR md.id_cliente = :cliente
         ORDER BY m.nombre'
    );
    $stmt->execute([':cliente' => $currentUser['id']]);
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        'SELECT DISTINCT f.id_ficha, c.fecha_inicio, m.nombre AS mascota, s.nombre AS servicio, f.observaciones, f.estado_mascota, f.fecha_cierre
         FROM ficha_grooming f
         JOIN cita c ON f.id_cita = c.id_cita
         JOIN mascota m ON c.id_mascota = m.id_mascota
         JOIN servicio s ON c.id_servicio = s.id_servicio
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_cliente_principal = :cliente OR md.id_cliente = :cliente
         ORDER BY f.fecha_cierre DESC'
    );
    $stmt->execute([':cliente' => $currentUser['id']]);
    $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        'SELECT DISTINCT fo.url, fo.tipo, fo.descripcion, m.nombre AS mascota, f.id_ficha
         FROM foto fo
         JOIN ficha_grooming f ON fo.id_ficha = f.id_ficha
         JOIN cita c ON f.id_cita = c.id_cita
         JOIN mascota m ON c.id_mascota = m.id_mascota
         LEFT JOIN mascota_dueno md ON m.id_mascota = md.id_mascota
         WHERE m.id_cliente_principal = :cliente OR md.id_cliente = :cliente
         ORDER BY fo.fecha_subida DESC'
    );
    $stmt->execute([':cliente' => $currentUser['id']]);
    $fotos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS visitas, SUM(p.monto) AS gastado
         FROM pago p
         JOIN factura f ON p.id_factura = f.id_factura
         JOIN cita c ON f.id_cita = c.id_cita
         WHERE c.id_mascota IN (
             SELECT id_mascota FROM mascota WHERE id_cliente_principal = :cliente
             UNION
             SELECT id_mascota FROM mascota_dueno WHERE id_cliente = :cliente
         )
           AND f.estado_factura = "pagada"'
    );
    $stmt->execute([':cliente' => $currentUser['id']]);
    $beneficios = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $mascotas = $historial = $fotos = [];
    $beneficios = ['visitas' => 0, 'gastado' => 0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Cliente - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding-top: 70px; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .content { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .report-card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .photo-card img { width: 100%; border-radius: 10px; object-fit: cover; max-height: 240px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/petspa/public/cliente/dashboard.php"><i class="fas fa-spa"></i> Pet Spa</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/tienda.php"><i class="fas fa-shopping-bag"></i> Tienda</a></li>
                    <li class="nav-item"><a class="nav-link" href="/petspa/public/cliente/servicios.php"><i class="fas fa-cut"></i> Servicios</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="/petspa/public/cliente/reportes.php"><i class="fas fa-file-alt"></i> Reportes</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown"><?php echo htmlspecialchars($currentUser['nombre']); ?></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="/petspa/public/perfil.php">Perfil</a></li>
                            <li><a class="dropdown-item" href="/petspa/api/auth/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>Reportes de Cliente</h2>
                <p class="text-muted">Historial clínico, evolución y beneficios como cliente frecuente.</p>
            </div>
            <a href="/petspa/public/cliente/dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Volver</a>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="report-card text-center">
                    <h4><i class="fas fa-notes-medical me-2"></i>Historial clínico</h4>
                    <p>Servicios realizados y recomendaciones por mascota.</p>
                    <p class="mb-0"><strong><?php echo intval($beneficios['visitas']); ?></strong> visitas</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card text-center">
                    <h4><i class="fas fa-image me-2"></i>Galería de evolución</h4>
                    <p>Fotos antes y después de los servicios.</p>
                    <p class="mb-0"><strong><?php echo count($fotos); ?></strong> imágenes</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="report-card text-center">
                    <h4><i class="fas fa-gift me-2"></i>Beneficios acumulados</h4>
                    <p>Acumulado de pagos y visitas.</p>
                    <p class="mb-0">S/ <?php echo number_format($beneficios['gastado'] ?? 0, 2); ?></p>
                </div>
            </div>
        </div>

        <div class="report-card">
            <h4>Historial de servicios</h4>
            <table class="table table-sm">
                <thead><tr><th>Fecha</th><th>Mascota</th><th>Servicio</th><th>Recomendación / Estado</th></tr></thead>
                <tbody>
                    <?php foreach ($historial as $item): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($item['fecha_inicio'])); ?></td>
                            <td><?php echo htmlspecialchars($item['mascota']); ?></td>
                            <td><?php echo htmlspecialchars($item['servicio']); ?></td>
                            <td><?php echo htmlspecialchars($item['observaciones'] ?: $item['estado_mascota'] ?: 'Sin datos'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($historial)): ?>
                        <tr><td colspan="4" class="text-center text-muted">Aún no tienes un historial de servicios.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="report-card">
            <h4>Galería de evolución</h4>
            <div class="row g-3">
                <?php foreach ($fotos as $foto): ?>
                    <div class="col-md-4">
                        <div class="photo-card">
                            <img src="<?php echo htmlspecialchars($foto['url']); ?>" alt="<?php echo htmlspecialchars($foto['descripcion'] ?? 'Foto'); ?>">
                            <div class="mt-2">
                                <strong><?php echo htmlspecialchars($foto['tipo']); ?></strong>
                                <p class="mb-0 small text-muted"><?php echo htmlspecialchars($foto['descripcion'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($fotos)): ?>
                    <div class="col-12 text-center text-muted">No hay fotos disponibles.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
