<?php
/**
 * SERVICIOS - LISTADO DE GROOMERS Y SERVICIOS
 * ===========================================
 * Página para que clientes vean groomers disponibles y sus servicios
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

try {
    // Obtener groomers activos con sus especialidades y calificaciones
    $stmt = $conn->prepare("
        SELECT g.*, u.nombre, u.telefono, u.email,
               COUNT(c.id_cita) as total_citas,
               AVG(col.puntuacion) as promedio_calificacion
        FROM groomer g
        JOIN usuario u ON g.id_groomer = u.id_usuario
        LEFT JOIN cita c ON g.id_groomer = c.id_groomer AND c.estado IN ('completada', 'confirmada')
        join calificacion col on col.id_cita = c.id_cita
        WHERE g.estado_activo = 1
        GROUP BY g.id_groomer, u.nombre, u.telefono, u.email
        ORDER BY promedio_calificacion DESC, total_citas DESC
    ");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener especialidades disponibles
    $stmt = $conn->prepare("SELECT DISTINCT especialidad FROM groomer WHERE estado_activo = 1 ORDER BY especialidad");
    $stmt->execute();
    $especialidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $groomers = [];
    $especialidades = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicios - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            transition: all 0.3s;
        }

        .nav-link:hover {
            color: white !important;
        }

        .btn-user {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 25px;
            transition: all 0.3s;
        }

        .btn-user:hover {
            background: white;
            color: #667eea;
        }

        /* FILTROS */
        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filter-btn {
            border-radius: 25px;
            margin: 5px;
            transition: all 0.3s;
            border: 2px solid #667eea;
            background: white;
            color: #667eea;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
        }

        /* GROOMER CARDS */
        .groomer-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            height: 100%;
        }

        .groomer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .groomer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }

        .groomer-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            margin: 0 auto 15px;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .groomer-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .groomer-specialty {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 15px;
        }

        .groomer-rating {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .stars {
            color: #ffc107;
            font-size: 16px;
            margin-right: 8px;
        }

        .rating-text {
            font-size: 14px;
            opacity: 0.9;
        }

        .groomer-stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 18px;
            font-weight: bold;
            display: block;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.8;
        }

        .groomer-info {
            padding: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-item i {
            width: 20px;
            color: #667eea;
            margin-right: 10px;
        }

        .services-list {
            margin-top: 15px;
        }

        .service-tag {
            display: inline-block;
            background: #f8f9fa;
            color: #667eea;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            margin: 2px;
            border: 1px solid #e9ecef;
        }

        .btn-book {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: 15px;
        }

        .btn-book:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 80px;
            margin-bottom: 20px;
            color: #ddd;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .groomer-stats {
                flex-direction: column;
                gap: 10px;
            }

            .filters {
                padding: 15px;
            }

            .filter-btn {
                margin: 3px;
                font-size: 12px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/petspa/public/index.php">
                <i class="fas fa-spa"></i> Pet Spa
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/dashboard.php">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/tienda.php">
                            <i class="fas fa-shopping-bag"></i> Tienda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/petspa/public/cliente/servicios.php">
                            <i class="fas fa-cut"></i> Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/citas.php">
                            <i class="fas fa-calendar-check"></i> Mis Citas
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle btn-user" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/petspa/public/cliente/perfil.php">
                                <i class="fas fa-user-edit"></i> Mi Perfil
                            </a></li>
                            <li><a class="dropdown-item" href="/petspa/public/cliente/carrito.php">
                                <i class="fas fa-shopping-cart"></i> Mi Carrito
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/petspa/api/auth/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-fluid py-4">
        <div class="container">
            <!-- HEADER -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="display-5 fw-bold text-center mb-3">
                        <i class="fas fa-cut text-primary"></i> Nuestros Servicios
                    </h1>
                    <p class="text-center text-muted">Conoce a nuestros expertos groomers y agenda tu cita</p>
                </div>
            </div>

            <!-- FILTROS POR ESPECIALIDAD -->
            <div class="filters">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-3">
                            <i class="fas fa-filter"></i> Filtrar por Especialidad
                        </h5>
                        <div class="d-flex flex-wrap">
                            <button class="btn filter-btn active" onclick="filterGroomers('todos')">
                                <i class="fas fa-list"></i> Todos
                            </button>
                            <?php foreach ($especialidades as $esp): ?>
                                <button class="btn filter-btn" onclick="filterGroomers('<?php echo htmlspecialchars($esp['especialidad']); ?>')">
                                    <?php echo htmlspecialchars($esp['especialidad']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="mb-2">
                            <i class="fas fa-info-circle text-primary"></i>
                            <small class="text-muted d-block">Ordenados por calificación</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GROOMERS -->
            <?php if (!empty($groomers)): ?>
                <div class="row" id="groomersContainer">
                    <?php foreach ($groomers as $groomer): ?>
                        <div class="col-lg-4 col-md-6 mb-4 groomer-item"
                             data-specialty="<?php echo htmlspecialchars($groomer['especialidad']); ?>">
                            <div class="groomer-card h-100">
                                <div class="groomer-header">
                                    <div class="groomer-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <div class="groomer-name"><?php echo htmlspecialchars($groomer['nombre']); ?></div>
                                    <div class="groomer-specialty"><?php echo htmlspecialchars($groomer['especialidad']); ?></div>

                                    <div class="groomer-rating">
                                        <div class="stars">
                                            <?php
                                            $rating = round($groomer['promedio_calificacion'] ?? 0, 1);
                                            for ($i = 1; $i <= 5; $i++) {
                                                if ($i <= $rating) {
                                                    echo '<i class="fas fa-star"></i>';
                                                } elseif ($i - 0.5 <= $rating) {
                                                    echo '<i class="fas fa-star-half-alt"></i>';
                                                } else {
                                                    echo '<i class="far fa-star"></i>';
                                                }
                                            }
                                            ?>
                                        </div>
                                        <div class="rating-text">
                                            <?php echo $rating > 0 ? $rating . '/5.0' : 'Sin calificaciones'; ?>
                                        </div>
                                    </div>

                                    <div class="groomer-stats">
                                        <div class="stat-item">
                                            <span class="stat-number"><?php echo $groomer['total_citas'] ?? 0; ?></span>
                                            <span class="stat-label">Citas</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">
                                                <?php echo $groomer['capacidad_simultanea'] ?? 0; ?> años
                                            </span>
                                            <span class="stat-label">Experiencia</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="groomer-info">
                                    <div class="info-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($groomer['telefono']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($groomer['email']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo htmlspecialchars($groomer['horario_trabajo'] ?? 'Horario flexible'); ?></span>
                                    </div>

                                    <div class="services-list">
                                        <strong>Servicios:</strong><br>
                                        <?php
                                        $servicios = explode(',', $groomer['servicios_ofrecidos'] ?? 'Baño,Grooming,Corte de pelo');
                                        foreach ($servicios as $servicio) {
                                            echo '<span class="service-tag">' . htmlspecialchars(trim($servicio)) . '</span>';
                                        }
                                        ?>
                                    </div>

                                    <button class="btn btn-book" onclick="bookAppointment(<?php echo $groomer['id_groomer']; ?>, '<?php echo htmlspecialchars($groomer['nombre']); ?>')">
                                        <i class="fas fa-calendar-plus"></i> Agendar Cita
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- NO HAY GROOMERS -->
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No hay groomers disponibles</h3>
                    <p>Pronto tendremos profesionales disponibles para atenderte</p>
                    <a href="/petspa/public/cliente/dashboard.php" class="btn btn-primary mt-3">
                        <i class="fas fa-arrow-left"></i> Volver al Inicio
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ALERTS -->
    <div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <!-- Alerts will be inserted here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrar groomers por especialidad
        function filterGroomers(specialty) {
            const items = document.querySelectorAll('.groomer-item');
            const buttons = document.querySelectorAll('.filter-btn');

            // Reset active button
            buttons.forEach(btn => btn.classList.remove('active'));

            // Set active button
            event.target.classList.add('active');

            // Filter items
            items.forEach(item => {
                if (specialty === 'todos' || item.dataset.specialty === specialty) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Agendar cita
        function bookAppointment(groomerId, groomerName) {
            // Redirigir a página de agendar cita con groomer preseleccionado
            window.location.href = `/petspa/public/cliente/agendar_cita.php?groomer=${groomerId}`;
        }

        // Mostrar alertas
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();

            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            alertContainer.insertAdjacentHTML('beforeend', alertHTML);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                const alert = document.getElementById(alertId);
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>