<?php
/**
 * DASHBOARD CLIENTE
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/middleware.php';
require_once __DIR__ . '/../../core/helpers.php';

// Inicializar Auth
Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

try {
    // Obtener datos del cliente
    $stmt = $conn->prepare("
        SELECT c.*, u.nombre, u.telefono, u.email
        FROM cliente c
        JOIN usuario u ON c.id_cliente = u.id_usuario
        WHERE c.id_cliente = ?
    ");
    $stmt->execute([Auth::getCurrentUser()['id']]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    // Obtener mascotas del cliente
    $stmt = $conn->prepare("SELECT * FROM mascota WHERE id_cliente_principal = ? LIMIT 5");
    $stmt->execute([Auth::getCurrentUser()['id']]);
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Cliente - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 70px;
        }

        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
        }

        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .welcome-card h2 {
            margin: 0 0 10px 0;
        }

        .welcome-card p {
            margin: 0;
            opacity: 0.9;
        }

        .action-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .action-card-icon {
            font-size: 40px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .action-card-title {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .action-card-desc {
            font-size: 13px;
            color: #999;
        }

        .mascotas-section h4 {
            margin-bottom: 20px;
            color: #333;
        }

        .mascota-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .mascota-info {
            flex: 1;
        }

        .mascota-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .mascota-breed {
            font-size: 13px;
            color: #999;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #693a97 100%);
        }

        .container-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }

            .welcome-card {
                padding: 20px;
            }

            .action-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/petspa/public/index.php">
                <i class="fas fa-spa"></i> Pet Spa
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/tienda.php">
                            <i class="fas fa-shopping-bag"></i> Tienda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/servicios.php">
                            <i class="fas fa-cut"></i> Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/petspa/public/cliente/carrito.php">
                            <i class="fas fa-cart-shopping"></i> Carrito
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['nombre']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/petspa/public/cliente/perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="/petspa/public/cliente/historial_citas.php">Historial de Citas</a></li>
                            <li><a class="dropdown-item" href="/petspa/public/cliente/historial_compras.php">Mis Compras</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/petspa/api/auth/logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <div class="container-content">
        <!-- WELCOME CARD -->
        <div class="welcome-card mt-4">
            <h2>¡Bienvenido, <?php echo htmlspecialchars($currentUser['nombre']); ?>!</h2>
            <p>Te alegra verte de vuelta en Pet Spa</p>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="row mb-4">
            <div class="col-md-4">
                <a href="/petspa/public/cliente/agendar_cita.php" class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="action-card-title">Agendar Cita</div>
                    <div class="action-card-desc">Reserva un servicio de grooming</div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="/petspa/public/cliente/tienda.php" class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="action-card-title">Ir a Tienda</div>
                    <div class="action-card-desc">Compra productos para tu mascota</div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="/petspa/public/cliente/servicios.php" class="action-card">
                    <div class="action-card-icon">
                        <i class="fas fa-spa"></i>
                    </div>
                    <div class="action-card-title">Nuestros Servicios</div>
                    <div class="action-card-desc">Conoce todos nuestros servicios</div>
                </a>
            </div>
        </div>

        <!-- MIS MASCOTAS -->
        <div class="mascotas-section">
            <h4><i class="fas fa-paw"></i> Mis Mascotas</h4>

            <?php if (!empty($mascotas)): ?>
                <?php foreach ($mascotas as $mascota): ?>
                    <div class="mascota-card">
                        <div class="mascota-info">
                            <div class="mascota-name"><?php echo htmlspecialchars($mascota['nombre']); ?></div>
                            <div class="mascota-breed">
                                <?php echo htmlspecialchars($mascota['raza'] ?? 'Sin especificar'); ?> • 
                                <?php echo isset($mascota['edad']) ? $mascota['edad'] . ' años' : 'Edad desconocida'; ?>
                            </div>
                        </div>
                        <div>
                            <a href="/petspa/public/cliente/editar_mascota.php?id=<?php echo $mascota['id_mascota']; ?>" 
                               class="btn btn-sm btn-outline-primary">
                                Editar
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <div class="mt-3">
                    <a href="/petspa/public/cliente/mascotas.php" class="btn btn-primary w-100">
                        <i class="fas fa-plus"></i> Agregar Nueva Mascota
                    </a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-paw"></i>
                    <h5>Aún no tienes mascotas registradas</h5>
                    <p>Agrega tu primera mascota para comenzar</p>
                    <a href="/petspa/public/cliente/mascotas.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus"></i> Registrar Mascota
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>