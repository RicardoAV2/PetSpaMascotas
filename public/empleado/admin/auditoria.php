<?php
require_once "../../../config/database.php";
require_once "../../../core/auth.php";
require_once "../../../core/middleware.php";

Auth::setConnection($conn);
Middleware::requireAdmin();

$logFile = "../../../logs/audit.log";

$logs = [];

if (file_exists($logFile)) {

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Mostrar últimos registros primero
    $lines = array_reverse($lines);

    foreach ($lines as $line) {

    preg_match('/^\[(.*?)\]/', $line, $fechaHora);

    preg_match('/\[(login|logout|intento_login_fallido|crear_usuario|editar_usuario|eliminar_usuario)\]/', $line, $accion);

    preg_match('/\[Usuario:\s(.*?)\]/', $line, $usuario);

    preg_match('/\[Rol:\s(.*?)\]/', $line, $rol);

    preg_match('/\[IP:\s(.*?)\]/', $line, $ip);

    preg_match('/\[UA:\s(.*?)\]/', $line, $ua);

    // Extraer descripción después del último ]
    $descripcion = preg_replace('/^.*\]\s/', '', $line);

    $fecha = '';
    $hora = '';

    if (!empty($fechaHora[1])) {

        $dt = explode(' ', $fechaHora[1]);

        $fecha = $dt[0] ?? '';
        $hora = $dt[1] ?? '';
    }

    // Detectar navegador
    $navegador = "Desconocido";

    if (isset($ua[1])) {

        if (str_contains($ua[1], 'Chrome')) {
            $navegador = 'Chrome';
        }

        if (str_contains($ua[1], 'Edg')) {
            $navegador = 'Edge';
        }

        if (str_contains($ua[1], 'Firefox')) {
            $navegador = 'Firefox';
        }
    }

    // Estado visual
    $estado = "Normal";

    if (($accion[1] ?? '') == 'login') {
        $estado = "Exitoso";
    }

    if (($accion[1] ?? '') == 'intento_login_fallido') {
        $estado = "Fallido";
    }

    $logs[] = [

        'fecha' => $fecha,
        'hora' => $hora,
        'accion' => $accion[1] ?? '',
        'usuario' => $usuario[1] ?? '',
        'rol' => $rol[1] ?? '',
        'ip' => $ip[1] ?? '',
        'navegador' => $navegador,
        'estado' => $estado,
        'descripcion' => $descripcion

    ];
}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Auditoría del Sistema</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
    background:#f4f6f9;
}

.card-custom{
    border:none;
    border-radius:15px;
    box-shadow:0 2px 10px rgba(0,0,0,0.1);
}

.table-responsive{
    max-height:600px;
    overflow:auto;
}

.badge-login{
    background:#0d6efd;
}

.badge-error{
    background:#dc3545;
}
.table td {
    vertical-align: middle;
}

.table tbody tr:hover {
    background: #f8f9fa;
}

.badge {
    font-size: 0.85rem;
    padding: 8px 12px;
    border-radius: 10px;
}
</style>

</head>
<body>

<div class="container-fluid mt-4">

    <div class="card card-custom p-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h2>Auditoría de Acciones</h2>

            <a href="dashboard.php" class="btn btn-warning">
                Volver al Dashboard
            </a>

        </div>
        <div class="row mb-3">

            <div class="col-md-4">

                <input
                    type="text"
                    id="searchInput"
                    class="form-control"
                    placeholder="Buscar usuario, acción o IP..."
                >

            </div>

</div>
        <div class="table-responsive">

            <table class="table table-hover table-bordered align-middle">

                <thead class="table-dark">

                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Acción</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>IP</th>
                        <th>Navegador</th>
                        <th>Estado</th>
                        <th>Descripción</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach($logs as $log): ?>

                    <tr>

                        <td><?= $log['fecha'] ?></td>

                        <td><?= $log['hora'] ?></td>

                        <td>

                            <?php

                            $badge = "bg-secondary";

                            if($log['accion'] == 'login'){
                                $badge = "bg-primary";
                            }

                            if($log['accion'] == 'logout'){
                                $badge = "bg-dark";
                            }

                            if($log['accion'] == 'intento_login_fallido'){
                                $badge = "bg-danger";
                            }

                            ?>

                            <span class="badge <?= $badge ?>">
                                <?= $log['accion'] ?>
                            </span>

                        </td>

                        <td><?= $log['usuario'] ?></td>

                        <td>
                            <span class="badge bg-warning text-dark">
                                <?= strtoupper($log['rol']) ?>
                            </span>
                        </td>

                        <td><?= $log['ip'] ?></td>

                        <td><?= $log['navegador'] ?></td>

                        <td>

                            <?php if($log['estado'] == 'Exitoso'): ?>

                                <span class="badge bg-success">
                                    Exitoso
                                </span>

                            <?php elseif($log['estado'] == 'Fallido'): ?>

                                <span class="badge bg-danger">
                                    Fallido
                                </span>

                            <?php else: ?>

                                <span class="badge bg-secondary">
                                    Normal
                                </span>

                            <?php endif; ?>

                        </td>

                        <td style="min-width:300px;">
                            <?= htmlspecialchars($log['descripcion']) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>
<script>

document.getElementById("searchInput").addEventListener("keyup", function() {

    let value = this.value.toLowerCase();

    let rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {

        row.style.display =
            row.innerText.toLowerCase().includes(value)
            ? ""
            : "none";
    });

});

</script>
</body>
</html>