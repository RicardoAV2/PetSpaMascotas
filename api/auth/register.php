<?php
/**
 * PROCESAR REGISTRO DE CLIENTES
 * =============================
 * Solo clientes pueden auto-registrarse
 * Empleados son creados por admin
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../core/Mailer.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/middleware.php';

// Inicializar Auth
Auth::setConnection($conn);

Middleware::requirePOST();

try {
    // Obtener datos del formulario
    $data = [
        'email' => getPost('email'),
        'password' => getPost('password'),
        'password_confirm' => getPost('password_confirm'),
        'nombre' => getPost('nombre'),
        'apellido' => getPost('apellido'),
        'telefono' => getPost('telefono'),
        'ci' => getPost('ci'),
        'direccion' => getPost('direccion'),
    ];

    // Validaciones básicas
    if (empty($data['email']) || empty($data['password']) || empty($data['nombre'])) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Complete todos los campos requeridos'
        ]));
    }

    // Verificar que las contraseñas coincidan
    if ($data['password'] !== $data['password_confirm']) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'message' => 'Las contraseñas no coinciden'
        ]));
    }

    // Registrar nuevo cliente
    $result = Auth::register($data);

    if ($result['success']) {
        http_response_code(201);
        
        $response = [
            'success' => true,
            'message' => $result['message'],
            'user_id' => $result['user_id']
        ];

        if (isset($result['email_token'])) {
            $response['email_token'] = $result['email_token'];
        }

        die(json_encode($response));
    } else {
        $code = isset($result['errors']) ? 400 : 400;
        http_response_code($code);
        die(json_encode($result));
    }

} catch (Exception $e) {
    error_log('Error en registro API: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Error en el servidor'
    ]));
}
?>
