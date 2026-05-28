<?php
/**
 * LANDING PAGE / HOME
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/middleware.php';

// Iniciar sesión solo si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
Middleware::checkSessionTimeout();

try {
    // Obtener servicios destacados (groomers con mejor calificación)
    $stmt = $conn->prepare("
        SELECT g.*, u.nombre, u.telefono
        FROM groomer g
        JOIN usuario u ON g.id_groomer = u.id_usuario
        WHERE g.estado_activo = 1
        ORDER BY g.calificacion_promedio DESC
        LIMIT 3
    ");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener productos destacados
    $stmt = $conn->prepare("
        SELECT * FROM producto
        WHERE estado_activo = 1
        ORDER BY descripcion DESC
        LIMIT 6
    ");
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log($e->getMessage());
    $groomers = [];
    $productos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Spa - Sistema de Gestión para tu Mascota</title>
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
        }

        /* NAVBAR */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 24px;
            font-weight: bold;
            color: white !important;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.85) !important;
            transition: all 0.3s;
            margin-left: 20px;
        }

        .nav-link:hover {
            color: white !important;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: white;
            color: #667eea;
        }

        /* HERO SECTION */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .hero p {
            font-size: 20px;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .btn-cta {
            background: white;
            color: #667eea;
            padding: 12px 40px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 25px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-right: 15px;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .btn-cta-secondary {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-cta-secondary:hover {
            background: white;
            color: #667eea;
            border-color: white;
        }

        /* SECTION STYLES */
        .section {
            padding: 80px 20px;
        }

        .section-title {
            text-align: center;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 50px;
            color: #333;
        }

        .section-title-underline {
            width: 100px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 20px auto;
        }

        /* GROOMER CARDS */
        .groomer-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .groomer-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            color: #333;
        }

        .groomer-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            color: white;
        }

        .groomer-info {
            padding: 20px;
        }

        .groomer-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .groomer-specialty {
            font-size: 13px;
            color: #999;
            margin-bottom: 10px;
        }

        .groomer-rating {
            color: #ffc107;
            font-size: 14px;
        }

        /* PRODUCT CARDS */
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
            color: #333;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            color: white;
        }

        .product-info {
            padding: 15px;
        }

        .product-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        /* FEATURES */
        .feature-icon {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 15px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .feature-text {
            color: #666;
            font-size: 14px;
        }

        /* FOOTER */
        footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .footer-links {
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #ccc;
            text-decoration: none;
            margin: 0 15px;
            transition: all 0.3s;
        }

        .footer-links a:hover {
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero p {
                font-size: 16px;
            }

            .section-title {
                font-size: 28px;
            }

            .btn-cta {
                margin-bottom: 10px;
                display: block;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
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
                        <a class="nav-link" href="#servicios">Servicios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#groomers">Nuestro Equipo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contacto">Contacto</a>
                    </li>
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/petspa/public/cliente/dashboard.php">
                                <i class="fas fa-user-circle"></i> Mi Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/petspa/api/auth/logout.php">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn-login" href="/petspa/public/login.php">Iniciar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero">
        <div>
            <h1><i class="fas fa-paw"></i> Bienvenido a Pet Spa</h1>
            <p>El mejor lugar para consentir a tu mascota y Dinosaurio</p>
            <div>
                <?php if (!isset($_SESSION['usuario_id'])): ?>
                    <a href="/petspa/public/register.php" class="btn-cta">Registrarme Ahora</a>
                    <a href="/petspa/public/login.php" class="btn-cta btn-cta-secondary">Iniciar Sesión</a>
                <?php else: ?>
                    <a href="/petspa/public/cliente/agendar_cita.php" class="btn-cta">Agendar Cita</a>
                    <a href="/petspa/public/cliente/tienda.php" class="btn-cta btn-cta-secondary">Ver Tienda</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="section" style="background: #f8f9fa;">
        <div class="container">
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon"><i class="fas fa-spa"></i></div>
                    <div class="feature-title">Grooming Profesional</div>
                    <div class="feature-text">Nuestros groomers certificados ofrecen los mejores servicios de peluquería para tu mascota</div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="feature-title">Tienda Online</div>
                    <div class="feature-text">Acceso a una variedad de productos de calidad para el cuidado de tu mascota</div>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="feature-title">Reserva Fácil</div>
                    <div class="feature-text">Agenda citas en línea de forma rápida y sencilla desde cualquier lugar</div>
                </div>
            </div>
        </div>
    </section>

    <!-- GROOMERS SECTION -->
    <section class="section" id="groomers">
        <div class="container">
            <h2 class="section-title">Nuestro Equipo de Expertos</h2>
            <div class="section-title-underline"></div>

            <?php if (!empty($groomers)): ?>
                <div class="row">
                    <?php foreach ($groomers as $groomer): ?>
                        <div class="col-md-4 mb-4">
                            <a href="/petspa/public/cliente/servicios.php" class="groomer-card">
                                <div class="groomer-image">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="groomer-info">
                                    <div class="groomer-name"><?php echo htmlspecialchars($groomer['nombre']); ?></div>
                                    <div class="groomer-specialty"><?php echo htmlspecialchars($groomer['especialidad']); ?></div>
                                    <div class="groomer-rating">
                                        <i class="fas fa-star"></i> <?php echo round($groomer['calificacion_promedio'], 1); ?>/5.0
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Pronto tendremos mass groomers disponibles</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- PRODUCTS SECTION -->
    <section class="section" style="background: #f8f9fa;" id="productos">
        <div class="container">
            <h2 class="section-title">Productos Destacados</h2>
            <div class="section-title-underline"></div>

            <?php if (!empty($productos)): ?>
                <div class="row">
                    <?php foreach ($productos as $producto): ?>
                        <div class="col-md-4 mb-4">
                            <a href="/petspa/public/cliente/tienda.php" class="product-card">
                                <div class="product-image">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="product-info">
                                    <div class="product-name"><?php echo htmlspecialchars($producto['nombre']); ?></div>
                                    <div class="product-price"><?php echo formatCurrency($producto['precio_base']); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>Pronto habrá productos disponibles</p>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['usuario_id'])): ?>
                <div class="text-center mt-4">
                    <a href="/petspa/public/register.php" class="btn-cta">Ver Todos los Productos</a>
                </div>
            <?php else: ?>
                <div class="text-center mt-4">
                    <a href="/petspa/public/cliente/tienda.php" class="btn-cta">Ver Todos los Productos</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="contacto">
        <div class="container">
            <div class="footer-links">
                <a href="#servicios">Servicios</a>
                <a href="#productos">Productos</a>
                <a href="#groomers">Nuestro Equipo</a>
                <a href="#">Política de Privacidad</a>
                <a href="#">Términos de Uso</a>
            </div>
            <div class="mb-3">
                <p><strong>Pet Spa - Sistema de Gestión</strong></p>
                <p>Teléfono: +591 123456788764 XDDD</p>
                <p>Email: info@petspa.com</p>
            </div>
            <div style="border-top: 1px solid #4e4343; padding-top: 20px;">
                <p>&copy; 2024 Pet Spa. Todos los derechos reservados UMSA Trabajo taller XD.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>