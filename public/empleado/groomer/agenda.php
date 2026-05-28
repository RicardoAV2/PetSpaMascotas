<?php
require_once "../../../config/database.php";
require_once "../../../config/constants.php";
require_once "../../../core/Auth.php";
require_once "../../../core/middleware.php";
require_once "../../../core/helpers.php";
require_once "../../../api/empleado/helpers_grooming.php";

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_GROOMER);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

$stmt = $conn->prepare("SELECT id_groomer FROM groomer WHERE id_groomer = ? AND estado_activo = 1");
$stmt->execute([$userId]);
$groomer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$groomer) {
    header('Location: /petspa/public/empleado/groomer/dashboard.php?error=groomer_not_found');
    exit();
}

// Obtener citas del groomer con servicios
$stmt = $conn->prepare("
    SELECT c.*, m.nombre AS mascota, m.tamano, m.peso, m.especie, m.raza,
           cl.id_cliente, u.nombre AS cliente_nombre,
           COUNT(cs.id_servicio) AS cantidad_servicios
    FROM cita c
    JOIN mascota m ON c.id_mascota = m.id_mascota
    JOIN cliente cl ON m.id_cliente_principal = cl.id_cliente
    JOIN usuario u ON cl.id_cliente = u.id_usuario
    LEFT JOIN cita_servicio cs ON c.id_cita = cs.id_cita
    WHERE c.id_groomer = :id_groomer AND c.estado IN ('confirmada', 'pendiente', 'en_progreso')
    GROUP BY c.id_cita
    ORDER BY c.fecha_inicio ASC
");
$stmt->bindParam(':id_groomer', $groomer['id_groomer'], PDO::PARAM_INT);
$stmt->execute();
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos disponibles
$productosDisponibles = getProductosDisponibles($conn);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda - Groomer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cita-card {
            border-left: 5px solid #0d6efd;
            transition: all 0.3s ease;
        }
        .cita-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .cita-card.completada {
            border-left-color: #198754;
            opacity: 0.7;
        }
        .cita-card.en-progreso {
            border-left-color: #ffc107;
        }
        .servicio-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 0.75rem;
        }
        .producto-selector {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
        }
        .badge-tamaño {
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
        }
        .reserva-indicador {
            font-size: 0.75rem;
            color: #6c757d;
        }
        .panel-servicios {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3">Mi Agenda</h1>
            <p class="text-muted mb-0">Gestiona tus citas y selecciona los productos para cada servicio</p>
        </div>
        <a class="btn btn-secondary" href="/petspa/public/empleado/groomer/dashboard.php">Volver al Panel</a>
    </div>

    <?php if (empty($citas)): ?>
        <div class="alert alert-info" role="alert">
            <i class="bi bi-info-circle"></i> No tienes citas asignadas por el momento.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($citas as $c): 
                $serviciosCita = getServiciosCita($conn, $c['id_cita']);
                $estadoBadgeClass = $c['estado'] === 'completada' ? 'bg-success' : 
                                   ($c['estado'] === 'en_progreso' ? 'bg-warning text-dark' : 
                                   ($c['estado'] === 'confirmada' ? 'bg-primary' : 'bg-secondary'));
            ?>
                <div class="col-lg-6">
                    <div class="card cita-card <?php echo $c['estado']; ?>">
                        <!-- Header de la Cita -->
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="card-title mb-1">
                                        <i class="bi bi-paw"></i> <?php echo htmlspecialchars($c['mascota']); ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($c['especie']); ?> - 
                                        <?php echo htmlspecialchars($c['raza']); ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $estadoBadgeClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $c['estado'])); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Detalles de la Cita -->
                        <div class="card-body pb-2">
                            <!-- Info Cliente -->
                            <p class="mb-2">
                                <strong>Cliente:</strong> <?php echo htmlspecialchars($c['cliente_nombre']); ?>
                            </p>

                            <!-- Info Mascota -->
                            <p class="mb-2">
                                <strong>Tamaño:</strong>
                                <span class="badge bg-info badge-tamaño">
                                    <?php echo ucfirst($c['tamano'] ?? 'No especificado'); ?>
                                </span>
                                <strong class="ms-2">Peso:</strong> <?php echo $c['peso'] ?? 'N/A'; ?> kg
                            </p>

                            <!-- Fecha y Hora -->
                            <p class="mb-3">
                                <strong>Fecha/Hora:</strong>
                                <br>
                                <i class="bi bi-calendar-event"></i> 
                                <?php echo date('d/m/Y H:i', strtotime($c['fecha_inicio'])); ?>
                            </p>

                            <!-- Servicios y Selección de Productos -->
                            <hr>
                            <h6 class="mb-3">
                                <i class="bi bi-clipboard-check"></i> Servicios a realizar (<?php echo count($serviciosCita); ?>)
                            </h6>

                            <?php if (!empty($serviciosCita)): ?>
                                <div class="panel-servicios">
                                    <?php foreach ($serviciosCita as $idx => $servicio): 
                                        $productosReservados = getProductosReservadosServicio($conn, $c['id_cita'], $servicio['id_servicio']);
                                    ?>
                                        <div class="servicio-item">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-0">
                                                        <span class="badge bg-secondary me-2"><?php echo $idx + 1; ?></span>
                                                        <?php echo htmlspecialchars($servicio['nombre']); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo $servicio['duracion_base_minutos']; ?> min | 
                                                        $<?php echo number_format($servicio['precio_base'], 2); ?>
                                                    </small>
                                                </div>
                                                <?php if (!empty($productosReservados)): ?>
                                                    <span class="badge bg-success" title="Productos seleccionados">
                                                        <?php echo count($productosReservados); ?> items
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Toggle para mostrar selector de productos -->
                                            <button class="btn btn-sm btn-outline-primary w-100 mb-2" 
                                                    type="button" 
                                                    data-bs-toggle="collapse" 
                                                    data-bs-target="#servicios-<?php echo $c['id_cita']; ?>-<?php echo $servicio['id_servicio']; ?>">
                                                <i class="bi bi-plus-circle"></i> 
                                                <?php echo empty($productosReservados) ? 'Seleccionar productos' : 'Editar selección'; ?>
                                            </button>

                                            <!-- Formulario de Selección de Productos -->
                                            <div class="collapse" id="servicios-<?php echo $c['id_cita']; ?>-<?php echo $servicio['id_servicio']; ?>">
                                                <form class="form-reserva-productos" 
                                                      action="/petspa/api/empleado/reservar_productos_servicio.php" 
                                                      method="POST"
                                                      data-cita-id="<?php echo $c['id_cita']; ?>"
                                                      data-servicio-id="<?php echo $servicio['id_servicio']; ?>"
                                                      data-tamano="<?php echo $c['tamano']; ?>">
                                                    
                                                    <input type="hidden" name="id_cita" value="<?php echo $c['id_cita']; ?>">
                                                    <input type="hidden" name="id_servicio" value="<?php echo $servicio['id_servicio']; ?>">
                                                    <input type="hidden" name="tamano_mascota" value="<?php echo htmlspecialchars($c['tamano']); ?>">

                                                    <div class="row g-2 mb-2">
                                                        <?php foreach ($productosDisponibles as $prod): 
                                                            // Verificar si ya está seleccionado
                                                            $yaReservado = array_filter($productosReservados, fn($p) => $p['id_producto'] == $prod['id_producto']);
                                                            $cantidadReservada = !empty($yaReservado) ? reset($yaReservado)['cantidad_reservada'] : 0;
                                                        ?>
                                                            <div class="col-12">
                                                                <div class="producto-selector">
                                                                    <div class="form-check">
                                                                        <input class="form-check-input producto-checkbox" 
                                                                               type="checkbox" 
                                                                               id="prod-<?php echo $c['id_cita']; ?>-<?php echo $servicio['id_servicio']; ?>-<?php echo $prod['id_producto']; ?>"
                                                                               name="productos[<?php echo $prod['id_producto']; ?>][id_producto]"
                                                                               value="<?php echo $prod['id_producto']; ?>"
                                                                               <?php echo $cantidadReservada > 0 ? 'checked' : ''; ?>
                                                                               data-disponible="<?php echo $prod['cantidad_disponible']; ?>">
                                                                        <label class="form-check-label flex-grow-1" 
                                                                               for="prod-<?php echo $c['id_cita']; ?>-<?php echo $servicio['id_servicio']; ?>-<?php echo $prod['id_producto']; ?>">
                                                                            <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong>
                                                                            <br>
                                                                            <small class="text-muted">
                                                                                Stock: <?php echo $prod['cantidad_disponible']; ?> | 
                                                                                $<?php echo number_format($prod['precio_base'], 2); ?>
                                                                            </small>
                                                                        </label>
                                                                    </div>
                                                                    <div class="mt-2">
                                                                        <label class="form-label form-label-sm mb-1">Cantidad:</label>
                                                                        <input type="number" 
                                                                               class="form-control form-control-sm cantidad-producto"
                                                                               name="productos[<?php echo $prod['id_producto']; ?>][cantidad]"
                                                                               value="<?php echo $cantidadReservada > 0 ? $cantidadReservada : 1; ?>"
                                                                               min="1"
                                                                               max="<?php echo $prod['cantidad_disponible']; ?>"
                                                                               <?php echo $cantidadReservada > 0 ? '' : 'disabled'; ?>>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>

                                                    <div class="d-grid gap-2">
                                                        <button type="submit" class="btn btn-success btn-sm">
                                                            <i class="bi bi-check-circle"></i> Guardar Selección
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>

                                            <!-- Mostrar productos ya reservados -->
                                            <?php if (!empty($productosReservados)): ?>
                                                <div class="mt-2 pt-2 border-top">
                                                    <small class="text-muted d-block mb-2">Productos seleccionados:</small>
                                                    <?php foreach ($productosReservados as $prod): ?>
                                                        <span class="badge bg-light text-dark me-1 mb-1">
                                                            <?php echo htmlspecialchars($prod['producto_nombre']); ?> (<?php echo $prod['cantidad_reservada']; ?>)
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer con acciones -->
                        <div class="card-footer bg-light">
                            <div class="d-grid gap-2">
                                <?php if ($c['estado'] !== 'completada'): ?>
                                    <a href="grooming.php?id=<?php echo $c['id_cita']; ?>" class="btn btn-primary">
                                        <i class="bi bi-play-circle"></i> Atender Cita
                                    </a>
                                <?php else: ?>
                                    <span class="text-success text-center py-2">
                                        <i class="bi bi-check-circle"></i> Cita completada
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle cantidad input cuando se checkea producto
    document.querySelectorAll('.producto-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const form = this.closest('.form-reserva-productos');
            const productoId = this.value;
            const cantidadInput = form.querySelector(`input[name="productos[${productoId}][cantidad]"]`);
            
            if (this.checked) {
                cantidadInput.disabled = false;
            } else {
                cantidadInput.disabled = true;
            }
        });
    });

    // Enviar formularios con AJAX
    document.querySelectorAll('.form-reserva-productos').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const citaId = this.dataset.citaId;
            const servicioId = this.dataset.servicioId;
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        alert('Productos guardados correctamente');
                        // Aquí puedes refrescar la página o actualizar dinámicamente
                        location.reload();
                    } else {
                        alert('Error: ' + (result.message || 'No se pudieron guardar los productos'));
                    }
                } else {
                    alert('Error al guardar');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error de conexión');
            }
        });
    });
});
</script>
</body>
</html>

