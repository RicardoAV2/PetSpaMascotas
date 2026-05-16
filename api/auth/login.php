<?php
/**
 * PROCESAR LOGIN
 * ===============
 * Recibe POST: email, password, opcional: 2fa_code
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/helpers.php';

// Inicializar Auth con conexión
Auth::setConnection($conn);

// Permitir CORS para desarrollo (comentar en producción)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

Middleware::requirePOST();

try {
    // Obtener datos del formulario
    $email = getPost('email');
    $password = getPost('password');
    $code2FA = getPost('2fa_code', '');

    // Validar campos requeridos
    if (empty($email) || empty($password)) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Email y contraseña son requeridos'
        ]));
    }

    // Si está intentando completar 2FA
    if (!empty($code2FA) && isset($_SESSION['temp_user_id'])) {
        $result = Auth::verify2FA($code2FA);
        
        if ($result['success']) {
            // Redirigir al dashboard según el rol
            $redirectUrl = '/petspa/public/login.php'; // default
            switch ($_SESSION['rol']) {
                case ROLE_ADMIN:
                    $redirectUrl = '/petspa/public/empleado/admin/dashboard.php';
                    break;
                case ROLE_RECEPCION:
                    $redirectUrl = '/petspa/public/empleado/recepcionista/dashboard.php';
                    break;
                case ROLE_GROOMER:
                    $redirectUrl = '/petspa/public/empleado/groomer/dashboard.php';
                    break;
                case ROLE_CLIENTE:
                    $redirectUrl = '/petspa/public/cliente/dashboard.php';
                    break;
            }
            http_response_code(200);
            die(json_encode([
                'success' => true,
                'message' => '2FA verificado',
                'redirect' => $redirectUrl
            ]));
        } else {
            http_response_code(401);
            die(json_encode($result));
        }
    }

    // Procesar login normal
    $result = Auth::login($email, $password);

    // Responder
    if ($result['success']) {
        if (isset($result['require_2fa']) && $result['require_2fa']) {
            // Requiere 2FA
            http_response_code(202);
            die(json_encode([
                'success' => false,
                'require_2fa' => true,
                'message' => 'Verificación de dos factores requerida',
                'temp_token' => $result['temp_token']
            ]));
        } else {
            // Login exitoso sin 2FA
            // Redirigir al dashboard según el rol
            $redirectUrl = '/petspa/public/login.php'; // default
            switch ($result['user']['rol_nombre']) {
                case ROLE_ADMIN:
                    $redirectUrl = '/petspa/public/empleado/admin/dashboard.php';
                    break;
                case ROLE_RECEPCION:
                    $redirectUrl = '/petspa/public/empleado/recepcionista/dashboard.php';
                    break;
                case ROLE_GROOMER:
                    $redirectUrl = '/petspa/public/empleado/groomer/dashboard.php';
                    break;
                case ROLE_CLIENTE:
                    $redirectUrl = '/petspa/public/cliente/dashboard.php';
                    break;
            }

            http_response_code(200);
            die(json_encode([
                'success' => true,
                'message' => 'Login exitoso',
                'redirect' => $redirectUrl,
                'user' => [
                    'id' => $result['user']['id_usuario'],
                    'nombre' => $result['user']['nombre'],
                    'rol' => $result['user']['rol_nombre'],
                    'email' => $result['user']['email']
                ]
            ]));
        }
    } else {
        http_response_code(401);
        die(json_encode($result));
    }

} catch (Exception $e) {
    error_log('Error en login API: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Error en el servidor'
    ]));
}
?>
