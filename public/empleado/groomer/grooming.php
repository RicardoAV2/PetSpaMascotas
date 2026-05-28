<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../core/Auth.php';
require_once __DIR__ . '/../../../core/middleware.php';
require_once __DIR__ . '/../../../core/helpers.php';
require_once __DIR__ . '/../../../core/Security.php';
require_once __DIR__ . '/../../../api/empleado/helpers_grooming.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

$id_cita = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_cita <= 0) {
    header('Location: /petspa/public/empleado/groomer/agenda.php');
    exit();
}

// Obtener datos de la cita y mascota
$stmt = $conn->prepare("
    SELECT c.*, m.nombre AS mascota, m.especie, m.raza, m.tamano, m.peso, m.alergias, 
           u.nombre AS cliente_nombre
    FROM cita c
    JOIN mascota m ON c.id_mascota = m.id_mascota
    JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
    JOIN usuario u ON cl.id_cliente = u.id_usuario
    WHERE c.id_cita = ? AND c.id_groomer = ?
");
$stmt->execute([$id_cita, $userId]);
$cita = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cita) {
    header('Location: /petspa/public/empleado/groomer/agenda.php?error=not_allowed');
    exit();
}

// Obtener servicios de la cita
$serviciosCita = getServiciosCita($conn, $id_cita);

// Obtener si existe una ficha de grooming para esta cita
$stmt = $conn->prepare("
    SELECT id_ficha, estado_cierre FROM ficha_grooming WHERE id_cita = ?
");
$stmt->execute([$id_cita]);
$fichExistente = $stmt->fetch(PDO::FETCH_ASSOC);
$id_ficha = $fichExistente ? $fichExistente['id_ficha'] : null;
$estado_cierre_anterior = $fichExistente ? $fichExistente['estado_cierre'] : null;

// Obtener servicios completados (si ya hay ficha)
$serviciosCompletados = $id_ficha ? getServiciosCompletados($conn, $id_ficha) : [];
$serviciosCompletadosMap = [];
foreach ($serviciosCompletados as $sc) {
    $serviciosCompletadosMap[$sc['id_servicio']] = $sc;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atender Cita - Grooming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .mascota-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .servicio-tab {
            background: white;
            border-left: 4px solid #0d6efd;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .servicio-tab.completado {
            border-left-color: #198754;
            background-color: #f1f3f5;
            opacity: 0.9;
        }
        .servicio-tab.en-progreso {
            border-left-color: #ffc107;
            background-color: #fffbf0;
        }
        .servicio-header {
            padding: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .servicio-header:hover {
            background-color: #f8f9fa;
        }
        .servicio-body {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
            max-height: 600px;
            overflow-y: auto;
        }
        .producto-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            border-left: 3px solid #0d6efd;
        }
        .checklist-servicio {
            display: none;
        }
        .checklist-servicio.activo {
            display: block;
        }
        .barra-progreso {
            height: 25px;
            line-height: 25px;
            font-size: 0.85rem;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .badge-tamaño {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header con navegación -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3"><i class="bi bi-play-circle"></i> Atender Cita</h1>
        </div>
        <a href="agenda.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Card con info de la mascota -->
    <div class="mascota-card">
        <div class="row">
            <div class="col-md-6">
                <h4 class="mb-1"><i class="bi bi-paw"></i> <?php echo htmlspecialchars($cita['mascota']); ?></h4>
                <p class="mb-3">
                    <small>
                        <?php echo htmlspecialchars($cita['especie']); ?> • 
                        <?php echo htmlspecialchars($cita['raza']); ?> •
                        <strong><?php echo htmlspecialchars($cita['tamano']); ?></strong>
                    </small>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-1"><small><i class="bi bi-person"></i> <?php echo htmlspecialchars($cita['cliente_nombre']); ?></small></p>
                <p class="mb-0"><small><i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y H:i', strtotime($cita['fecha_inicio'])); ?></small></p>
            </div>
        </div>
        <?php if ($cita['alergias']): ?>
            <hr class="my-2">
            <div class="alert alert-warning mb-0" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);">
                <i class="bi bi-exclamation-triangle"></i> <strong>Alergias:</strong> <?php echo htmlspecialchars($cita['alergias']); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Barra de progreso general -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title">Progreso de la Cita</h6>
            <?php 
                $totalServicios = count($serviciosCita);
                $serviciosCompletadosCount = count(array_filter($serviciosCompletadosMap, fn($s) => $s['completado'] === 1));
                $porcentajeProgress = $totalServicios > 0 ? round(($serviciosCompletadosCount / $totalServicios) * 100) : 0;
            ?>
            <div class="progress mb-2" style="height: 30px;">
                <div class="progress-bar barra-progreso" role="progressbar" 
                     style="width: <?php echo $porcentajeProgress; ?>%">
                    <?php echo $serviciosCompletadosCount; ?>/<?php echo $totalServicios; ?> servicios
                </div>
            </div>
            <small class="text-muted">
                <?php echo $porcentajeProgress; ?>% completado
                <?php if ($estado_cierre_anterior): ?>
                    | Estado anterior: <strong><?php echo ucfirst($estado_cierre_anterior); ?></strong>
                <?php endif; ?>
            </small>
        </div>
    </div>

    <form id="form-grooming" action="/petspa/api/empleado/grooming.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
        <input type="hidden" name="id_ficha" value="<?php echo $id_ficha ?? ''; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">

        <!-- Tabs para cada servicio -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-list-check"></i> Servicios a Realizar</h6>
            </div>
            <div class="card-body p-0">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" role="tablist" style="border-bottom: 1px solid #dee2e6;">
                    <?php foreach ($serviciosCita as $idx => $servicio): 
                        $estaCompletado = isset($serviciosCompletadosMap[$servicio['id_servicio']]) && $serviciosCompletadosMap[$servicio['id_servicio']]['completado'] === 1;
                        $tabId = 'servicio-' . $servicio['id_servicio'];
                    ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $idx === 0 ? 'active' : ''; ?> <?php echo $estaCompletado ? 'opacity-50' : ''; ?>" 
                                    id="<?php echo $tabId; ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#<?php echo $tabId; ?>" 
                                    type="button" 
                                    role="tab">
                                <span class="<?php echo $estaCompletado ? 'text-success' : ''; ?>">
                                    <?php echo $estaCompletado ? '<i class="bi bi-check-circle"></i>' : '<i class="bi bi-circle"></i>'; ?>
                                    <?php echo htmlspecialchars($servicio['nombre']); ?>
                                </span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Tab content -->
                <div class="tab-content">
                    <?php foreach ($serviciosCita as $idx => $servicio): 
                        $estaCompletado = isset($serviciosCompletadosMap[$servicio['id_servicio']]) && $serviciosCompletadosMap[$servicio['id_servicio']]['completado'] === 1;
                        $tabId = 'servicio-' . $servicio['id_servicio'];
                        $productosReservados = getProductosReservadosServicio($conn, $id_cita, $servicio['id_servicio']);
                    ?>
                        <div class="tab-pane <?php echo $idx === 0 ? 'active' : ''; ?>" id="<?php echo $tabId; ?>" role="tabpanel">
                            <div class="p-3">
                                <!-- Info del servicio -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="mb-1"><small class="text-muted">Duración base:</small></p>
                                        <p class="mb-0"><strong><?php echo $servicio['duracion_base_minutos']; ?> minutos</strong></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><small class="text-muted">Precio:</small></p>
                                        <p class="mb-0"><strong>$<?php echo number_format($servicio['precio_base'], 2); ?></strong></p>
                                    </div>
                                </div>

                                <hr>

                                <!-- Productos para este servicio -->
                                <h6 class="mb-3">
                                    <i class="bi bi-box"></i> Productos Reservados
                                    <?php if (count($productosReservados) > 0): ?>
                                        <span class="badge bg-success"><?php echo count($productosReservados); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sin productos</span>
                                    <?php endif; ?>
                                </h6>

                                <?php if (!empty($productosReservados)): ?>
                                    <div class="productos-list mb-3">
                                        <?php foreach ($productosReservados as $prod): ?>
                                            <div class="producto-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-6">
                                                        <strong><?php echo htmlspecialchars($prod['producto_nombre']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">Reservado: <?php echo $prod['cantidad_reservada']; ?></small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label form-label-sm mb-1">Cantidad Usada:</label>
                                                        <input type="number" 
                                                               class="form-control form-control-sm cantidad-usada"
                                                               name="productos_usados[<?php echo $servicio['id_servicio']; ?>][<?php echo $prod['id_reserva_servicio']; ?>]"
                                                               value="<?php echo $prod['cantidad_reservada']; ?>"
                                                               min="0"
                                                               max="<?php echo $prod['cantidad_reservada']; ?>">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary alert-sm">No hay productos reservados para este servicio.</div>
                                <?php endif; ?>

                                <hr>

                                <!-- Checklist por servicio -->
                                <h6 class="mb-3"><i class="bi bi-clipboard-check"></i> Checklist del Servicio</h6>
                                <?php 
                                    // Obtener items del checklist para este servicio
                                    $stmt = $conn->prepare("
                                        SELECT si.id_item, ci.nombre FROM servicio_checklist_template si 
                                        JOIN checklist_item ci ON si.id_item = ci.id_item 
                                        WHERE si.id_servicio = ? 
                                        ORDER BY si.orden
                                    ");
                                    $stmt->execute([$servicio['id_servicio']]);
                                    $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    if (empty($checklistItems)) {
                                        $stmt = $conn->prepare("SELECT id_item, nombre FROM checklist_item ORDER BY id_item LIMIT 5");
                                        $stmt->execute();
                                        $checklistItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                ?>
                                <div class="row row-cols-1 row-cols-md-2 g-2 mb-3">
                                    <?php foreach ($checklistItems as $item): ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       id="checklist_<?php echo $servicio['id_servicio']; ?>_<?php echo $item['id_item']; ?>"
                                                       name="checklist[<?php echo $servicio['id_servicio']; ?>][]"
                                                       value="<?php echo $item['id_item']; ?>">
                                                <label class="form-check-label" 
                                                       for="checklist_<?php echo $servicio['id_servicio']; ?>_<?php echo $item['id_item']; ?>">
                                                    <?php echo htmlspecialchars($item['nombre']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <hr>

                                <!-- Estado del servicio -->
                                <h6 class="mb-3"><i class="bi bi-toggles"></i> Estado del Servicio</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input estado-servicio" 
                                           type="checkbox" 
                                           id="servicio_completado_<?php echo $servicio['id_servicio']; ?>"
                                           name="servicios_completados[<?php echo $servicio['id_servicio']; ?>]"
                                           value="1"
                                           <?php echo isset($serviciosCompletadosMap[$servicio['id_servicio']]) && $serviciosCompletadosMap[$servicio['id_servicio']]['completado'] === 1 ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="servicio_completado_<?php echo $servicio['id_servicio']; ?>">
                                        <strong>Marcar como completado</strong>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Datos generales de la ficha -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-file-medical"></i> Datos de la Ficha</h6>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Temperatura corporal (°C)</label>
                        <input type="number" step="0.1" min="30" max="45" 
                               name="temperatura_animal" class="form-control" 
                               placeholder="37.5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" step="0.1" min="0" 
                               name="peso_kg" class="form-control" 
                               placeholder="12.5">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Raza / Talla</label>
                        <input type="text" 
                               name="raza_talla" class="form-control" 
                               value="<?php echo htmlspecialchars($cita['raza']); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Estado General de la Mascota</label>
                    <textarea class="form-control" name="estado_mascota" rows="3" 
                              placeholder="Describir comportamiento, aspecto y bienestar"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="3" 
                              placeholder="Notas del servicio, recomendaciones, cuidados especiales"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notas Internas</label>
                    <textarea class="form-control" name="notas_internas" rows="2" 
                              placeholder="Comentarios para el equipo, alertas o recomendaciones posteriores"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Foto Final</label>
                    <input type="file" name="foto_final" class="form-control" accept="image/png,image/jpeg,image/webp">
                </div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="row g-2 mb-4">
            <div class="col-md-6">
                <button type="submit" name="tipo_guardado" value="parcial" class="btn btn-warning w-100">
                    <i class="bi bi-pause-circle"></i> Guardar Parcialmente (Continuar después)
                </button>
            </div>
            <div class="col-md-6">
                <button type="submit" name="tipo_guardado" value="completada" class="btn btn-success w-100">
                    <i class="bi bi-check-circle"></i> Completar Cita
                </button>
            </div>
        </div>

        <a href="agenda.php" class="btn btn-outline-secondary w-100">
            <i class="bi bi-x-circle"></i> Cancelar
        </a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cambiar color de tabs según estado
        document.querySelectorAll('.estado-servicio').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const servicioId = this.id.replace('servicio_completado_', '');
                const tab = document.getElementById('servicio-' + servicioId + '-tab');
                
                if (this.checked) {
                    tab.classList.add('opacity-50');
                } else {
                    tab.classList.remove('opacity-50');
                }
            });
        });

        // Validar que al menos un servicio esté completado al enviar
        document.getElementById('form-grooming').addEventListener('submit', function(e) {
            const tipoGuardado = document.querySelector('button[name="tipo_guardado"]:focus')?.value || 'parcial';
            
            if (tipoGuardado === 'completada') {
                const serviciosCompletados = Array.from(document.querySelectorAll('.estado-servicio:checked')).length;
                const totalServicios = document.querySelectorAll('.estado-servicio').length;
                
                if (serviciosCompletados === 0) {
                    e.preventDefault();
                    alert('Debe completar al menos un servicio para finalizar la cita');
                    return false;
                }
            }
        });
    });
</script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Grooming</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Finalizar Grooming</h1>
            <p class="text-muted mb-0">Cierre de servicio para <?php echo htmlspecialchars($cita['mascota']); ?>.</p>
        </div>
        <a href="agenda.php" class="btn btn-secondary">Volver a Agenda</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Mascota:</strong> <?php echo htmlspecialchars($cita['mascota']); ?></p>
                    <p><strong>Especie:</strong> <?php echo htmlspecialchars($cita['especie']); ?></p>
                    <p><strong>Raza:</strong> <?php echo htmlspecialchars($cita['raza']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Servicios programados:</strong> <?php echo htmlspecialchars(implode(', ', $serviciosProgramados) ?: 'N/A'); ?></p>
                    <p><strong>Fecha:</strong> <?php echo htmlspecialchars($cita['fecha_inicio']); ?></p>
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($cita['cliente_nombre']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <form action="/petspa/api/empleado/grooming.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_cita" value="<?php echo $cita['id_cita']; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">

        <div class="mb-4">
            <h5>Checklist de Grooming</h5>
            <div class="row row-cols-1 row-cols-md-2 g-3">
                <?php foreach ($checklistOptions as $item): ?>
                    <div class="col">
                        <div class="form-check card p-3 h-100">
                            <input class="form-check-input checklist-checkbox" type="checkbox" name="checklist[]" id="item_<?php echo $item['id_item']; ?>" value="<?php echo $item['id_item']; ?>">
                            <label class="form-check-label" for="item_<?php echo $item['id_item']; ?>"><?php echo htmlspecialchars($item['nombre']); ?></label>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="form-label">Temperatura animal</label>
                <input type="number" step="0.1" min="30" max="45" name="temperatura_animal" class="form-control" placeholder="37.5">
            </div>
            <div class="col-md-4">
                <label class="form-label">Peso (kg)</label>
                <input type="number" step="0.1" min="0" name="peso_kg" class="form-control" placeholder="12.5">
            </div>
            <div class="col-md-4">
                <label class="form-label">Raza / talla</label>
                <input type="text" name="raza_talla" class="form-control" placeholder="Mediano / Poodle">
            </div>
        </div>

        <div class="mb-4">
            <h5>Insumos reservados para esta cita</h5>
            <?php if (!empty($reservasInsumos)): ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Insumo</th>
                                <th>Reservado</th>
                                <th>Usado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservasInsumos as $reserva): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reserva['producto_nombre']); ?></td>
                                    <td><?php echo intval($reserva['cantidad']); ?></td>
                                    <td>
                                        <input type="number" min="0" max="<?php echo intval($reserva['cantidad']); ?>" name="insumos[<?php echo $reserva['id_reserva']; ?>]" class="form-control form-control-sm" value="<?php echo intval($reserva['cantidad']); ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-secondary mb-0">No hay insumos reservados para esta cita.</div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label class="form-label">Estado general de la mascota</label>
            <textarea class="form-control" name="estado_mascota" rows="3" required placeholder="Describir comportamiento, aspecto y bienestar"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="observaciones" rows="4" placeholder="Notas del servicio, recomendaciones, cuidados especiales"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Notas internas</label>
            <textarea class="form-control" name="notas_internas" rows="3" placeholder="Comentarios para el equipo, alertas o recomendaciones posteriores"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Subir foto final</label>
            <input type="file" name="foto_final" class="form-control" accept="image/png,image/jpeg,image/webp">
        </div>

        <button type="submit" id="submitClose" class="btn btn-success">Cerrar Grooming</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function(){
        const minRequired = 4;
        const submitBtn = document.getElementById('submitClose');
        const checkboxes = Array.from(document.querySelectorAll('.checklist-checkbox'));

        function updateState() {
            const checked = checkboxes.filter(cb => cb.checked).length;
            submitBtn.disabled = checked < minRequired;
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateState));
        updateState();
    })();
</script>
</body>
</html>
