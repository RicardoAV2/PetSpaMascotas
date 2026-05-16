<?php
/**
 * AGENDAR CITA - FORMULARIO DE RESERVA
 * ===================================
 * Página para que clientes agenden citas con groomers
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
$selectedGroomer = getGet('groomer', '');

// Obtener mascotas del cliente
try {
    $stmt = $conn->prepare("SELECT * FROM mascota WHERE id_cliente_principal = ? ORDER BY nombre");
    $stmt->execute([$userId]);
    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $mascotas = [];
}

// Obtener groomers disponibles
try {
    $stmt = $conn->prepare("
        SELECT g.id_groomer, u.nombre, g.especialidad,
               COUNT(c.id_cita) as total_citas,
               AVG(c.calificacion) as promedio_calificacion
        FROM groomer g
        JOIN usuario u ON g.id_groomer = u.id_usuario
        LEFT JOIN cita c ON g.id_groomer = c.id_groomer AND c.estado IN ('completada', 'confirmada')
        WHERE g.estado_activo = 1
        GROUP BY g.id_groomer, u.nombre, g.especialidad
        ORDER BY promedio_calificacion DESC
    ");
    $stmt->execute();
    $groomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $groomers = [];
}

// Obtener servicios disponibles
$servicios = [
    'baño' => 'Baño Completo',
    'corte_pelo' => 'Corte de Pelo',
    'corte_unas' => 'Corte de Uñas',
    'limpieza_oidos' => 'Limpieza de Oídos',
    'deslanado' => 'Deslanado',
    'baño_medicado' => 'Baño Medicado',
    'grooming_completo' => 'Grooming Completo'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - Pet Spa</title>
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

        /* FORMULARIO */
        .booking-form {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .form-header h2 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .form-header p {
            opacity: 0.9;
            margin: 0;
        }

        .form-body {
            padding: 40px;
        }

        .form-step {
            display: none;
        }

        .form-step.active {
            display: block;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 10px;
            transition: all 0.3s;
        }

        .step.active {
            background: #667eea;
            color: white;
        }

        .step.completed {
            background: #28a745;
            color: white;
        }

        .step-line {
            width: 60px;
            height: 2px;
            background: #e9ecef;
            margin: 0 10px;
        }

        .step-line.active {
            background: #667eea;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 16px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .groomer-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .groomer-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }

        .groomer-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .groomer-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-right: 15px;
        }

        .pet-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pet-card:hover {
            border-color: #667eea;
        }

        .pet-card.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .service-checkbox {
            display: none;
        }

        .service-label {
            display: block;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .service-label:hover {
            border-color: #667eea;
        }

        .service-checkbox:checked + .service-label {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .time-slots {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .time-slot {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .time-slot:hover {
            border-color: #667eea;
        }

        .time-slot.selected {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
            font-weight: bold;
        }

        .time-slot.disabled {
            background: #f8f9fa;
            color: #6c757d;
            cursor: not-allowed;
        }

        .btn-nav {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-prev {
            background: #6c757d;
            border: none;
            color: white;
        }

        .btn-prev:hover {
            background: #5a6268;
        }

        .btn-next {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
        }

        .btn-next:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-submit {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            color: white;
            padding: 15px 40px;
            font-size: 16px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }

            .step-indicator {
                flex-wrap: wrap;
            }

            .time-slots {
                grid-template-columns: repeat(3, 1fr);
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

    <!-- MAIN CONTENT -->
    <div class="container-fluid py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="booking-form">
                        <div class="form-header">
                            <h2><i class="fas fa-calendar-plus"></i> Agendar Cita</h2>
                            <p>Reserva tu cita con nuestros expertos groomers</p>
                        </div>

                        <form id="bookingForm">
                            <!-- STEP 1: Seleccionar Groomer -->
                            <div class="form-step active" id="step1">
                                <div class="form-body">
                                    <div class="step-indicator">
                                        <div class="step active">1</div>
                                        <div class="step-line"></div>
                                        <div class="step">2</div>
                                        <div class="step-line"></div>
                                        <div class="step">3</div>
                                        <div class="step-line"></div>
                                        <div class="step">4</div>
                                    </div>

                                    <h4 class="mb-3">Paso 1: Selecciona un Groomer</h4>

                                    <?php if (!empty($groomers)): ?>
                                        <?php foreach ($groomers as $groomer): ?>
                                            <div class="groomer-card" onclick="selectGroomer(<?php echo $groomer['id_groomer']; ?>, this)">
                                                <div class="d-flex align-items-center">
                                                    <div class="groomer-avatar">
                                                        <i class="fas fa-user-circle"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($groomer['nombre']); ?></h5>
                                                        <p class="mb-1 text-muted"><?php echo htmlspecialchars($groomer['especialidad']); ?></p>
                                                        <div class="d-flex align-items-center">
                                                            <div class="stars me-2">
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
                                                            <small class="text-muted">
                                                                <?php echo $rating > 0 ? $rating . '/5.0' : 'Sin calificaciones'; ?>
                                                                (<?php echo $groomer['total_citas'] ?? 0; ?> citas)
                                                            </small>
                                                        </div>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input groomer-radio" type="radio"
                                                               name="groomer" value="<?php echo $groomer['id_groomer']; ?>"
                                                               <?php echo $selectedGroomer == $groomer['id_groomer'] ? 'checked' : ''; ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <p>No hay groomers disponibles en este momento.</p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-next" onclick="nextStep(1)">
                                            Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 2: Seleccionar Mascota -->
                            <div class="form-step" id="step2">
                                <div class="form-body">
                                    <div class="step-indicator">
                                        <div class="step completed">1</div>
                                        <div class="step-line active"></div>
                                        <div class="step active">2</div>
                                        <div class="step-line"></div>
                                        <div class="step">3</div>
                                        <div class="step-line"></div>
                                        <div class="step">4</div>
                                    </div>

                                    <h4 class="mb-3">Paso 2: Selecciona tu Mascota</h4>

                                    <?php if (!empty($mascotas)): ?>
                                        <?php foreach ($mascotas as $mascota): ?>
                                            <div class="pet-card" onclick="selectPet(<?php echo $mascota['id_mascota']; ?>, this)">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <h5 class="mb-1"><?php echo htmlspecialchars($mascota['nombre']); ?></h5>
                                                        <p class="mb-1 text-muted">
                                                            <?php echo htmlspecialchars($mascota['raza']); ?> -
                                                            <?php echo htmlspecialchars($mascota['edad']); ?> años
                                                        </p>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($mascota['especie']); ?> -
                                                            <?php echo htmlspecialchars($mascota['color']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input pet-radio" type="radio"
                                                               name="mascota" value="<?php echo $mascota['id_mascota']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-dog fa-3x text-muted mb-3"></i>
                                            <p>No tienes mascotas registradas.</p>
                                            <a href="/petspa/public/cliente/mascotas.php" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Registrar Mascota
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-prev" onclick="prevStep(2)">
                                            <i class="fas fa-arrow-left me-2"></i> Anterior
                                        </button>
                                        <button type="button" class="btn btn-next" onclick="nextStep(2)">
                                            Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3: Seleccionar Servicios y Fecha -->
                            <div class="form-step" id="step3">
                                <div class="form-body">
                                    <div class="step-indicator">
                                        <div class="step completed">1</div>
                                        <div class="step-line active"></div>
                                        <div class="step completed">2</div>
                                        <div class="step-line active"></div>
                                        <div class="step active">3</div>
                                        <div class="step-line"></div>
                                        <div class="step">4</div>
                                    </div>

                                    <h4 class="mb-3">Paso 3: Servicios y Fecha</h4>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label">Servicios Requeridos</label>
                                                <?php foreach ($servicios as $key => $servicio): ?>
                                                    <input type="checkbox" class="service-checkbox" id="serv_<?php echo $key; ?>" name="servicios[]" value="<?php echo $key; ?>">
                                                    <label class="service-label" for="serv_<?php echo $key; ?>">
                                                        <?php echo htmlspecialchars($servicio); ?>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="fecha">Fecha de la Cita</label>
                                                <input type="date" class="form-control" id="fecha" name="fecha"
                                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                                       onchange="loadAvailableTimes()">
                                            </div>

                                            <div class="form-group">
                                                <label class="form-label">Horarios Disponibles</label>
                                                <div id="timeSlots" class="time-slots">
                                                    <div class="time-slot disabled">Selecciona una fecha</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-prev" onclick="prevStep(3)">
                                            <i class="fas fa-arrow-left me-2"></i> Anterior
                                        </button>
                                        <button type="button" class="btn btn-next" onclick="nextStep(3)">
                                            Siguiente <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 4: Confirmación -->
                            <div class="form-step" id="step4">
                                <div class="form-body">
                                    <div class="step-indicator">
                                        <div class="step completed">1</div>
                                        <div class="step-line active"></div>
                                        <div class="step completed">2</div>
                                        <div class="step-line active"></div>
                                        <div class="step completed">3</div>
                                        <div class="step-line active"></div>
                                        <div class="step active">4</div>
                                    </div>

                                    <h4 class="mb-3">Paso 4: Confirmación</h4>

                                    <div id="confirmationDetails" class="mb-4">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            Completa los pasos anteriores para ver el resumen de tu cita.
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label" for="notas">Notas Adicionales (Opcional)</label>
                                        <textarea class="form-control" id="notas" name="notas" rows="3"
                                                  placeholder="Alguna instrucción especial para el groomer..."></textarea>
                                    </div>

                                    <div class="d-flex justify-content-between mt-4">
                                        <button type="button" class="btn btn-prev" onclick="prevStep(4)">
                                            <i class="fas fa-arrow-left me-2"></i> Anterior
                                        </button>
                                        <button type="button" class="btn btn-submit" onclick="submitBooking()">
                                            <i class="fas fa-calendar-check"></i> Confirmar Cita
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
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
        let currentStep = 1;
        let selectedGroomer = null;
        let selectedPet = null;
        let selectedServices = [];
        let selectedDate = null;
        let selectedTime = null;

        // Seleccionar groomer
        function selectGroomer(groomerId, card) {
            document.querySelectorAll('.groomer-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.querySelector(`input[name="groomer"][value="${groomerId}"]`).checked = true;
            selectedGroomer = groomerId;
        }

        // Seleccionar mascota
        function selectPet(petId, card) {
            document.querySelectorAll('.pet-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            document.querySelector(`input[name="mascota"][value="${petId}"]`).checked = true;
            selectedPet = petId;
        }

        // Navegación entre pasos
        function nextStep(step) {
            if (validateStep(step)) {
                document.getElementById(`step${step}`).classList.remove('active');
                document.getElementById(`step${step + 1}`).classList.add('active');
                currentStep = step + 1;
                updateStepIndicator();
            }
        }

        function prevStep(step) {
            document.getElementById(`step${step}`).classList.remove('active');
            document.getElementById(`step${step - 1}`).classList.add('active');
            currentStep = step - 1;
            updateStepIndicator();
        }

        function updateStepIndicator() {
            document.querySelectorAll('.step').forEach((step, index) => {
                const stepNum = index + 1;
                step.classList.remove('active', 'completed');
                if (stepNum < currentStep) {
                    step.classList.add('completed');
                } else if (stepNum === currentStep) {
                    step.classList.add('active');
                }
            });

            document.querySelectorAll('.step-line').forEach((line, index) => {
                line.classList.remove('active');
                if (index < currentStep - 1) {
                    line.classList.add('active');
                }
            });
        }

        // Validar pasos
        function validateStep(step) {
            switch (step) {
                case 1:
                    if (!document.querySelector('input[name="groomer"]:checked')) {
                        showAlert('Por favor selecciona un groomer', 'warning');
                        return false;
                    }
                    return true;
                case 2:
                    if (!document.querySelector('input[name="mascota"]:checked')) {
                        showAlert('Por favor selecciona una mascota', 'warning');
                        return false;
                    }
                    return true;
                case 3:
                    selectedServices = Array.from(document.querySelectorAll('input[name="servicios[]"]:checked')).map(cb => cb.value);
                    if (selectedServices.length === 0) {
                        showAlert('Por favor selecciona al menos un servicio', 'warning');
                        return false;
                    }
                    if (!document.getElementById('fecha').value) {
                        showAlert('Por favor selecciona una fecha', 'warning');
                        return false;
                    }
                    if (!selectedTime) {
                        showAlert('Por favor selecciona un horario', 'warning');
                        return false;
                    }
                    updateConfirmation();
                    return true;
                default:
                    return true;
            }
        }

        // Cargar horarios disponibles
        function loadAvailableTimes() {
            const fecha = document.getElementById('fecha').value;
            if (!fecha) return;

            selectedDate = fecha;
            const timeSlots = document.getElementById('timeSlots');

            // Horarios de ejemplo (9:00 AM - 5:00 PM)
            const times = [];
            for (let hour = 9; hour <= 17; hour++) {
                times.push(`${hour.toString().padStart(2, '0')}:00`);
            }

            timeSlots.innerHTML = times.map(time => `
                <div class="time-slot" onclick="selectTime('${time}', this)">
                    ${time}
                </div>
            `).join('');
        }

        // Seleccionar horario
        function selectTime(time, slot) {
            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
            slot.classList.add('selected');
            selectedTime = time;
        }

        // Actualizar confirmación
        function updateConfirmation() {
            const confirmation = document.getElementById('confirmationDetails');

            // Aquí iría la lógica para mostrar el resumen
            confirmation.innerHTML = `
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Resumen de tu Cita</h5>
                    <p><strong>Groomer:</strong> Cargando...</p>
                    <p><strong>Mascota:</strong> Cargando...</p>
                    <p><strong>Servicios:</strong> ${selectedServices.join(', ')}</p>
                    <p><strong>Fecha:</strong> ${selectedDate}</p>
                    <p><strong>Hora:</strong> ${selectedTime}</p>
                </div>
            `;
        }

        // Enviar formulario
        function submitBooking() {
            const formData = {
                groomer_id: selectedGroomer,
                mascota_id: selectedPet,
                servicios: selectedServices,
                fecha: selectedDate,
                hora: selectedTime,
                notas: document.getElementById('notas').value
            };

            fetch('/petspa/api/cliente/citas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Cita agendada exitosamente', 'success');
                    setTimeout(() => {
                        window.location.href = '/petspa/public/cliente/citas.php';
                    }, 2000);
                } else {
                    showAlert(data.message || 'Error al agendar la cita', 'danger');
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

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            // Pre-seleccionar groomer si viene por URL
            const urlParams = new URLSearchParams(window.location.search);
            const groomerParam = urlParams.get('groomer');
            if (groomerParam) {
                const groomerRadio = document.querySelector(`input[name="groomer"][value="${groomerParam}"]`);
                if (groomerRadio) {
                    groomerRadio.checked = true;
                    groomerRadio.closest('.groomer-card').classList.add('selected');
                    selectedGroomer = groomerParam;
                }
            }
        });
    </script>
</body>
</html>