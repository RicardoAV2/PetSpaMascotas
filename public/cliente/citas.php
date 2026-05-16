<?php
/**
 * MIS CITAS - DASHBOARD DE CITAS PARA CLIENTES
 * ===========================================
 * Página para ver y gestionar citas agendadas
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

$userId = $_SESSION['usuario_id'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas - Pet Spa</title>
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

        /* MAIN CONTENT */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .page-header p {
            opacity: 0.9;
            margin: 0;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }

        .card-header h5 {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }

        .card-body {
            padding: 25px;
        }

        /* ESTADOS DE CITAS */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pendiente {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmada {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-en_progreso {
            background: #d4edda;
            color: #155724;
        }

        .status-completada {
            background: #d4edda;
            color: #155724;
        }

        .status-cancelada {
            background: #f8d7da;
            color: #721c24;
        }

        /* ACCIONES */
        .btn-action {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-cancel {
            background: #dc3545;
            border: none;
            color: white;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-rate {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
        }

        .btn-rate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-agendar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 25px;
            font-size: 16px;
        }

        .btn-agendar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* CALIFICACIÓN */
        .rating-stars {
            color: #ffc107;
            font-size: 16px;
        }

        .rating-input {
            display: flex;
            gap: 5px;
        }

        .rating-input input {
            display: none;
        }

        .rating-input label {
            font-size: 25px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }

        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label {
            color: #ffc107;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 64px;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 30px;
        }

        /* MODAL */
        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }

        .modal-body {
            padding: 30px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .page-header {
                padding: 20px 0;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .card-body {
                padding: 15px;
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
                        <a class="nav-link" href="/petspa/public/cliente/servicios.php">
                            <i class="fas fa-cut"></i> Servicios
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/petspa/public/cliente/citas.php">
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

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h1><i class="fas fa-calendar-check"></i> Mis Citas</h1>
                    <p>Gestiona tus citas de grooming y servicios para tus mascotas</p>
                </div>
                <div class="col-lg-4 text-end">
                    <a href="/petspa/public/cliente/agendar_cita.php" class="btn btn-agendar">
                        <i class="fas fa-plus"></i> Agendar Nueva Cita
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mb-5">
        <div class="row">
            <div class="col-12">
                <div id="citasContainer">
                    <!-- Las citas se cargarán aquí vía JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA CALIFICAR -->
    <div class="modal fade" id="ratingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-star"></i> Calificar Servicio</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="ratingContent">
                        <!-- Contenido dinámico -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ALERTS -->
    <div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <!-- Alerts will be inserted here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let citasData = [];

        // Cargar citas al iniciar
        document.addEventListener('DOMContentLoaded', function() {
            loadCitas();
        });

        // Cargar citas desde API
        function loadCitas() {
            fetch('/petspa/api/cliente/citas.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    citasData = data.citas;
                    renderCitas();
                } else {
                    showAlert(data.message || 'Error al cargar citas', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Renderizar citas
        function renderCitas() {
            const container = document.getElementById('citasContainer');

            if (citasData.length === 0) {
                container.innerHTML = `
                    <div class="card">
                        <div class="card-body empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No tienes citas agendadas</h3>
                            <p>¡Es hora de consentir a tu mascota! Agenda una cita con nuestros expertos groomers.</p>
                            <a href="/petspa/public/cliente/agendar_cita.php" class="btn btn-agendar">
                                <i class="fas fa-plus"></i> Agendar Primera Cita
                            </a>
                        </div>
                    </div>
                `;
                return;
            }

            // Agrupar por estado
            const citasPorEstado = {
                pendientes: citasData.filter(c => ['pendiente', 'confirmada'].includes(c.estado)),
                en_progreso: citasData.filter(c => c.estado === 'en_progreso'),
                completadas: citasData.filter(c => c.estado === 'completada'),
                canceladas: citasData.filter(c => c.estado === 'cancelada')
            };

            let html = '';

            // Citas pendientes/confirmadas
            if (citasPorEstado.pendientes.length > 0) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> Citas Próximas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;

                citasPorEstado.pendientes.forEach(cita => {
                    html += renderCitaCard(cita);
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;
            }

            // Citas en progreso
            if (citasPorEstado.en_progreso.length > 0) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-spinner fa-spin"></i> Citas en Progreso</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;

                citasPorEstado.en_progreso.forEach(cita => {
                    html += renderCitaCard(cita);
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;
            }

            // Citas completadas
            if (citasPorEstado.completadas.length > 0) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-check-circle"></i> Citas Completadas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;

                citasPorEstado.completadas.forEach(cita => {
                    html += renderCitaCard(cita);
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;
            }

            // Citas canceladas
            if (citasPorEstado.canceladas.length > 0) {
                html += `
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-times-circle"></i> Citas Canceladas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                `;

                citasPorEstado.canceladas.forEach(cita => {
                    html += renderCitaCard(cita);
                });

                html += `
                            </div>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        // Renderizar tarjeta de cita
        function renderCitaCard(cita) {
            const servicios = JSON.parse(cita.servicios || '[]');
            const serviciosTexto = servicios.map(s => s.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())).join(', ');

            let acciones = '';
            let calificacionHtml = '';

            // Acciones según estado
            if (['pendiente', 'confirmada'].includes(cita.estado)) {
                acciones = `<button class="btn btn-action btn-cancel" onclick="cancelarCita(${cita.id_cita})">
                    <i class="fas fa-times"></i> Cancelar
                </button>`;
            } else if (cita.estado === 'completada' && !cita.calificacion) {
                acciones = `<button class="btn btn-action btn-rate" onclick="mostrarModalCalificar(${cita.id_cita})">
                    <i class="fas fa-star"></i> Calificar
                </button>`;
            }

            // Mostrar calificación si existe
            if (cita.calificacion) {
                calificacionHtml = `
                    <div class="rating-stars mb-2">
                        ${renderStars(cita.calificacion)}
                        <span class="ms-2">${cita.calificacion}/5</span>
                    </div>
                    ${cita.comentario ? `<p class="text-muted small mb-0"><i>"${cita.comentario}"</i></p>` : ''}
                `;
            }

            return `
                <div class="col-lg-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="card-title mb-1">
                                        <i class="fas fa-paw"></i> ${cita.mascota_nombre}
                                    </h6>
                                    <p class="text-muted small mb-1">${cita.raza} - ${cita.especie}</p>
                                </div>
                                <span class="status-badge status-${cita.estado}">
                                    ${cita.estado_texto}
                                </span>
                            </div>

                            <div class="row mb-3">
                                <div class="col-6">
                                    <p class="mb-1"><strong><i class="fas fa-user"></i> Groomer:</strong></p>
                                    <p class="text-muted small">${cita.groomer_nombre}</p>
                                    <p class="text-muted small">${cita.especialidad}</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><strong><i class="fas fa-calendar"></i> Fecha:</strong></p>
                                    <p class="text-muted small">${cita.fecha_formateada}</p>
                                    <p class="mb-1"><strong><i class="fas fa-clock"></i> Hora:</strong></p>
                                    <p class="text-muted small">${cita.hora_formateada}</p>
                                </div>
                            </div>

                            <div class="mb-3">
                                <p class="mb-1"><strong><i class="fas fa-cut"></i> Servicios:</strong></p>
                                <p class="text-muted small">${serviciosTexto || 'No especificado'}</p>
                            </div>

                            ${cita.notas ? `
                                <div class="mb-3">
                                    <p class="mb-1"><strong><i class="fas fa-sticky-note"></i> Notas:</strong></p>
                                    <p class="text-muted small">${cita.notas}</p>
                                </div>
                            ` : ''}

                            ${calificacionHtml}

                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-plus"></i> Creada: ${cita.fecha_creacion_formateada}
                                </small>
                                ${acciones}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Renderizar estrellas
        function renderStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="fas fa-star"></i>';
                } else {
                    stars += '<i class="far fa-star"></i>';
                }
            }
            return stars;
        }

        // Cancelar cita
        function cancelarCita(citaId) {
            if (!confirm('¿Estás seguro de que quieres cancelar esta cita?')) {
                return;
            }

            fetch('/petspa/api/cliente/citas.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cita_id: citaId,
                    accion: 'cancelar'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Cita cancelada exitosamente', 'success');
                    loadCitas(); // Recargar citas
                } else {
                    showAlert(data.message || 'Error al cancelar la cita', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Mostrar modal de calificación
        function mostrarModalCalificar(citaId) {
            const cita = citasData.find(c => c.id_cita == citaId);
            if (!cita) return;

            const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
            const content = document.getElementById('ratingContent');

            content.innerHTML = `
                <div class="text-center mb-4">
                    <h6>¿Cómo calificarías el servicio para ${cita.mascota_nombre}?</h6>
                    <p class="text-muted">Con ${cita.groomer_nombre} el ${cita.fecha_formateada}</p>
                </div>

                <form id="ratingForm">
                    <div class="mb-3">
                        <label class="form-label">Calificación (1-5 estrellas)</label>
                        <div class="rating-input">
                            <input type="radio" name="rating" value="5" id="star5">
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario (opcional)</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3"
                                  placeholder="Comparte tu experiencia..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="enviarCalificacion(${citaId})">
                            <i class="fas fa-paper-plane"></i> Enviar Calificación
                        </button>
                    </div>
                </form>
            `;

            modal.show();
        }

        // Enviar calificación
        function enviarCalificacion(citaId) {
            const rating = document.querySelector('input[name="rating"]:checked');
            const comentario = document.getElementById('comentario').value;

            if (!rating) {
                showAlert('Por favor selecciona una calificación', 'warning');
                return;
            }

            const formData = {
                cita_id: citaId,
                calificacion: rating.value,
                comentario: comentario
            };

            fetch('/petspa/api/cliente/calificar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Calificación enviada exitosamente', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                    loadCitas(); // Recargar citas
                } else {
                    showAlert(data.message || 'Error al enviar calificación', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error de conexión', 'danger');
            });
        }

        // Mostrar alertas
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();

            const alertHTML = `
                <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            alertContainer.insertAdjacentHTML('beforeend', alertHTML);

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