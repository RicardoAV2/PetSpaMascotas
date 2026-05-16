<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/middleware.php';
require_once __DIR__ . '/../core/helpers.php';

Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole(ROLE_CLIENTE);
Middleware::checkSessionTimeout();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

try {
    $stmt = $conn->prepare("SELECT u.*, c.direccion, c.ci FROM usuario u LEFT JOIN cliente c ON u.id_usuario = c.id_cliente WHERE u.id_usuario = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    $user = null;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::validateCSRF();

    $telefono = getPost('telefono');
    $direccion = getPost('direccion');
    $ci = getPost('ci');

    if (!empty($telefono) && !Security::isValidPhone($telefono)) {
        $error = 'Teléfono inválido';
    }

    if (!empty($ci) && !Security::isValidCI($ci)) {
        $error = 'Cédula de identidad inválida';
    }

    if (empty($error)) {
        try {
            $stmt = $conn->prepare("UPDATE usuario SET telefono = ? WHERE id_usuario = ?");
            $stmt->execute([$telefono, $userId]);

            $stmt = $conn->prepare("SELECT id_cliente FROM cliente WHERE id_cliente = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($exists) {
                $stmt = $conn->prepare("UPDATE cliente SET direccion = ?, ci = ? WHERE id_cliente = ?");
                $stmt->execute([$direccion, $ci, $userId]);
            } else {
                $stmt = $conn->prepare("INSERT INTO cliente (id_cliente, direccion, ci) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $direccion, $ci]);
            }

            $success = 'Tus datos se guardaron correctamente. Redirigiendo al dashboard...';
            header('Refresh: 2; url=/petspa/public/cliente/dashboard.php');
            exit;
        } catch (Exception $e) {
            error_log($e->getMessage());
            $error = 'No se pudo guardar la información. Intenta de nuevo.';
        }
    }
}

$telefonoValue = htmlspecialchars($user['telefono'] ?? '');
$direccionValue = htmlspecialchars($user['direccion'] ?? '');
$ciValue = htmlspecialchars($user['ci'] ?? '');
$nombreValue = htmlspecialchars($user['nombre'] . ' ' . $user['apellido']);
$emailValue = htmlspecialchars($user['email']);
$csrf_token = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar datos - Pet Spa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body style="background: #f4f7fc;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-user-check me-2"></i> Completa tus datos</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <p>Bienvenido, <strong><?php echo $nombreValue; ?></strong>.</p>
                        <p>Tu correo es <strong><?php echo $emailValue; ?></strong>. Completa los datos que faltan para terminar tu registro.</p>

                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <div class="mb-3">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="telefono" class="form-control" value="<?php echo $telefonoValue; ?>" placeholder="Ej: +59171234567">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="direccion" class="form-control" value="<?php echo $direccionValue; ?>" placeholder="Calle, número, barrio">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Cédula de Identidad</label>
                                <input type="text" name="ci" class="form-control" value="<?php echo $ciValue; ?>" placeholder="Ej: 1234567">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Guardar y continuar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
