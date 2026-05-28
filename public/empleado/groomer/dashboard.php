<?php
require_once '../../../config/database.php';
require_once '../../../core/middleware.php';
require_once '../../../core/helpers.php';

// Inicializar Auth
Auth::setConnection($conn);

// Verificar autenticación y rol
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER); // Groomer

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];
// Obtener estadísticas para groomer
try {
    // Citas de hoy
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM cita
    WHERE DATE(fecha_inicio) = CURDATE()
    AND id_groomer = ?
");

$stmt->execute([$userId]);

$citasHoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Citas pendientes
    $stmt = $conn->prepare("
    SELECT COUNT(*) as total
    FROM cita
    WHERE estado = 'agendada' or estado = 'confirmada' or estado = 'completada'
    AND id_groomer = ?
");

$stmt->execute([$userId]);

$citasPendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calificación promedio
    $stmt = $conn->prepare("
    SELECT AVG(puntuacion) as promedio
    FROM calificacion
    WHERE id_groomer = ?
");

$stmt->execute([$userId]);

$result = $stmt->fetch(PDO::FETCH_ASSOC);

$calificacionPromedio = round($result['promedio'] ?? 0, 1);

    // Citas recientes
    $stmt = $conn->prepare("
    SELECT 
        c.id_cita,
        c.fecha_inicio,
        c.estado,

        m.nombre AS mascota,

        CONCAT(
            u.nombre,
            ' ',
            u.apellido
        ) AS cliente

    FROM cita c

    JOIN mascota m
    ON c.id_mascota = m.id_mascota

    JOIN cliente cl
    ON m.id_cliente_principal = cl.id_cliente

    JOIN usuario u
    ON cl.id_cliente = u.id_usuario

    WHERE c.id_groomer = ?

    ORDER BY c.fecha_inicio DESC

    LIMIT 10
");

$stmt->execute([$userId]);

$citasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $citasHoy = $citasPendientes = 0;
    $calificacionPromedio = null;
    $citasRecientes = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Groomer - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            z-index: 1000;
        }

        .sidebar-logo {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 5px 0;
        }

        .sidebar-menu a {
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            display: block;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left-color: white;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .topbar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stats-card-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stats-card-number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }

        .rating-stars {
            color: #ffc107;
        }

        .btn-logout {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #c82333;
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-spa" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
            <h5>Pet Spa</h5>
            <small>Groomer</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/groomer/agenda.php" class="active"><i class="fas fa-calendar-check me-2"></i> Mi Agenda</a></li>
            <li><a href="/petspa/public/empleado/groomer/grooming.php"><i class="fas fa-cut me-2"></i> Grooming</a></li>
            <li><a href="/petspa/public/empleado/groomer/inventario.php"><i class="fas fa-boxes me-2"></i> Inventario</a></li>
            <li><a href="/petspa/public/empleado/groomer/reportes.php"><i class="fas fa-file-alt me-2"></i> Reportes</a></li>
            <li><a href="/petspa/public/perfil.php"><i class="fas fa-user-circle me-2"></i> Mi Perfil</a></li>
            <li><a href="/petspa/api/auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <!-- TOPBAR -->
        <div class="topbar">
            <h3 style="margin: 0;"><i class="fas fa-chart-line me-2"></i> Dashboard</h3>
            <div class="topbar-user">
                <span><?php echo getGreeting(); ?>, <strong><?php echo htmlspecialchars($currentUser['nombre']); ?></strong></span>
                <div class="user-avatar"><?php echo getInitials($currentUser['nombre']); ?></div>
            </div>
        </div>

        <!-- ESTADÍSTICAS -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-card-icon text-primary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $citasHoy ?? 0; ?></div>
                    <div class="stats-card-label">Citas Hoy</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-card-icon text-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $citasPendientes ?? 0; ?></div>
                    <div class="stats-card-label">Citas Pendientes</div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stats-card-icon text-success">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stats-card-number">
                        <?php if ($calificacionPromedio): ?>
                            <?php echo number_format($calificacionPromedio, 1); ?>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= round($calificacionPromedio) ? '' : '-half-alt'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </div>
                    <div class="stats-card-label">Calificación Promedio</div>
                </div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-4"><i class="fas fa-tasks me-2"></i> Acciones Rápidas</h5>

                    <a href="/petspa/public/empleado/groomer/agenda.php" class="btn btn-primary btn-sm me-2 mb-2">
                        <i class="fas fa-calendar-plus me-1"></i> Ver Agenda
                    </a>

                    <a href="/petspa/public/empleado/groomer/grooming.php" class="btn btn-success btn-sm me-2 mb-2">
                        <i class="fas fa-cut me-1"></i> Iniciar Grooming
                    </a>

                    <a href="/petspa/public/empleado/groomer/reportes.php" class="btn btn-info btn-sm mb-2">
                        <i class="fas fa-file-alt me-1"></i> Ver reportes
                    </a>

                    <a href="/petspa/public/empleado/groomer/inventario.php" class="btn btn-info btn-sm mb-2">
                        <i class="fas fa-boxes me-1"></i> Ver Inventario
                    </a>
                </div>
            </div>

            <div class="col-md-4">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i> Información</h5>
                    <small class="text-muted">
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                        <p><strong>Rol:</strong> Groomer</p>
                        <p><strong>Hora:</strong> <?php echo date('H:i'); ?></p>
                    </small>
                </div>
            </div>
        </div>

        <!-- MIS CITAS RECIENTES -->
        <div class="row mt-4">
            <div class="col-12">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-4"><i class="fas fa-calendar-alt me-2"></i> Mis Citas Recientes</h5>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Fecha y Hora</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($citasRecientes)): ?>
                                    <?php foreach ($citasRecientes as $cita): ?>
                                        <tr>
                                            <td><?php echo $cita['id_cita']; ?></td>
                                            <td><?php echo htmlspecialchars($cita['cliente']); ?></td>
                                            
                                            <td><?php echo date('d/m/Y H:i', strtotime($cita['fecha_inicio'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo match($cita['estado']) {
                                                        'pendiente' => 'warning',
                                                        'confirmada' => 'info',
                                                        'completada' => 'success',
                                                        'cancelada' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($cita['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                // Obtener calificación para esta cita
                                                $stmt = $conn->prepare("SELECT puntuacion FROM calificacion WHERE id_cita = ?");
                                                $stmt->execute([$cita['id_cita']]);
                                                $calificacion = $stmt->fetch(PDO::FETCH_ASSOC)['puntuacion'] ?? null;

                                                if ($calificacion) {
                                                    echo '<div class="rating-stars">';
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo '<i class="fas fa-star' . ($i <= round($calificacion) ? '' : '-half-alt') . '"></i>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<span class="text-muted">Sin calificar</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="fas fa-calendar-times fa-2x mb-2"></i><br>
                                            No tienes citas recientes
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-3">
                        <a href="/petspa/public/empleado/groomer/agenda.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-calendar me-1"></i> Ver Toda la Agenda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>