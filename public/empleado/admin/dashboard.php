<?php
/**
 * DASHBOARD DEL ADMINISTRADOR
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';

// Iniciar Auth
Auth::setConnection($conn);
Middleware::requireAdmin();
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();

// Obtener estadísticas
try {
    // Total usuarios
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuario WHERE estado = ?");
    $stmt->execute([USER_STATUS_ACTIVE]);
    $totalUsers = $stmt->fetch()['total'];

    // Total clientes
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cliente");
    $stmt->execute();
    $totalClientes = $stmt->fetch()['total'];

    // Total groomers
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM groomer WHERE estado_activo = 1");
    $stmt->execute();
    $totalGroomers = $stmt->fetch()['total'];

    // Total productos
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM producto");
    $stmt->execute();
    $totalProductos = $stmt->fetch()['total'];

    // Obtener lista de empleados
    $stmt = $conn->prepare("
        SELECT u.id_usuario, u.email, r.nombre as rol, u.estado, u.fecha_creacion
        FROM usuario u
        JOIN rol r ON u.id_rol = r.id_rol
        WHERE u.id_rol IN (1, 2, 3, 4)  -- Admin, Recepcionista, Groomer, Cliente
        ORDER BY u.fecha_creacion DESC
    ");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            overflow-y: auto;
            padding-top: 20px;
            z-index: 1000;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
        }

        .sidebar-logo {
            text-align: center;
            margin-bottom: 30px;
            padding: 0 15px;
        }

        .sidebar-logo h5 {
            margin: 0;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: white;
        }

        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-left-color: white;
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

        .stats-card-label {
            color: #999;
            font-size: 14px;
        }

        .topbar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
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

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-menu {
                display: flex;
                flex-wrap: wrap;
            }

            .sidebar-menu li {
                flex: 1 1 50%;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <div class="sidebar">
        <div class="sidebar-logo">
            <i class="fas fa-spa" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
            <h5>Pet Spa</h5>
            <small>Administrador</small>
        </div>

        <ul class="sidebar-menu">
            <li><a href="/petspa/public/empleado/admin/dashboard.php" class="active"><i class="fas fa-chart-line me-2"></i> Dashboard</a></li>
            <li><a href="/petspa/public/empleado/admin/empleados.php"><i class="fas fa-users me-2"></i> Usuarios</a></li>
            <li><a href="/petspa/public/empleado/admin/productos.php"><i class="fas fa-box me-2"></i> Productos</a></li>
            <li><a href="/petspa/public/empleado/facturacion/generar_factura.php"><i class="fas fa-file-invoice-dollar me-2"></i> Facturación</a></li>
            <li><a href="/petspa/public/empleado/promociones.php"><i class="fas fa-tags me-2"></i> Promociones</a></li>
            <li><a href="/petspa/public/empleado/agenda.php"><i class="fas fa-calendar-alt me-2"></i> Agenda</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/calendario_maestro.php"><i class="fas fa-calendar-week me-2"></i> Calendario Maestro</a></li>
            <li><a href="/petspa/public/empleado/admin/reportes.php"><i class="fas fa-file-alt me-2"></i> Reportes</a></li>
            <li><a href="/petspa/public/empleado/recepcionista/cobrar.php"><i class="fas fa-credit-card me-2"></i> Cobrar servicios</a></li>
            <li><a href="/petspa/public/empleado/admin/auditoria.php"><i class="fas fa-file-chart-bar me-2"></i>@@ Auditoria</a></li>
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

        <!-- STATS CARDS -->
        <div class="row">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-card-icon" style="color: #667eea;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $totalUsers ?? 0; ?></div>
                    <div class="stats-card-label">Usuarios Activos</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-card-icon" style="color: #28a745;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $totalClientes ?? 0; ?></div>
                    <div class="stats-card-label">Clientes</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-card-icon" style="color: #ffc107;">
                        <i class="fas fa-cut"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $totalGroomers ?? 0; ?></div>
                    <div class="stats-card-label">Groomers</div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-card-icon" style="color: #dc3545;">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stats-card-number"><?php echo $totalProductos ?? 0; ?></div>
                    <div class="stats-card-label">Productos</div>
                </div>
            </div>
        </div>

        <!-- ACCIONES RÁPIDAS -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-4"><i class="fas fa-tasks me-2"></i> Acciones Rápidas</h5>
                    
                    <a href="/petspa/public/empleado/admin/crear.php" class="btn btn-primary btn-sm me-2 mb-2">
                        <i class="fas fa-user-plus me-1"></i> Crear Empleado
                    </a>
                    
                    <a href="/petspa/public/empleado/admin/productos.php" class="btn btn-success btn-sm me-2 mb-2">
                        <i class="fas fa-box-open me-1"></i> Nuevo Producto
                    </a>
                    
                    <a href="/petspa/public/empleado/admin/auditoria.php" class="btn btn-info btn-sm mb-2">
                        <i class="fas fa-download me-1"></i> Ver auditoria
                    </a>
                    <a href="/petspa/public/empleado/admin/reportes.php" class="btn btn-warning btn-sm me-2 mb-2">
                        <i class="fas fa-file-alt me-1"></i> Ver reportes
                    </a>
                </div>
            </div>

            <div class="col-md-4">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i> Sistema</h5>
                    <small class="text-muted">
                        <p><strong>Versión:</strong> 1.0.0</p>
                        <p><strong>Estado:</strong> <span class="badge bg-success">Operativo</span></p>
                        <p><strong>Usuario:</strong> <?php echo htmlspecialchars($currentUser['email']); ?></p>
                    </small>
                </div>
            </div>
        </div>

        <!-- LISTA DE EMPLEADOS RECIENTES -->
        <div class="row mt-4">
            <div class="col-12">
                <div style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);">
                    <h5 class="mb-4"><i class="fas fa-users me-2"></i> Empleados Recientes</h5>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($empleados)): ?>
                                    <?php foreach ($empleados as $emp): ?>
                                        <tr>
                                            <td><?php echo $emp['id_usuario']; ?></td>
                                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    echo match($emp['rol']) {
                                                        'admin' => 'danger',
                                                        'recepcionista' => 'warning',
                                                        'groomer' => 'info',
                                                        'cliente' => 'success',
                                                        default => 'secondary'
                                                    };
                                                ?>">
                                                    <?php echo ucfirst($emp['rol']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $emp['estado'] == 'activo' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($emp['estado']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($emp['fecha_creacion'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="fas fa-users fa-2x mb-2"></i><br>
                                            No hay empleados registrados
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-end mt-3">
                        <a href="/petspa/public/empleado/admin/empleados.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Gestionar Empleados
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>