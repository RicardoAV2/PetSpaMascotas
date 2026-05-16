<?php
/**
 * VERIFICAR EMAIL
 * ===============
 * Verifica el token de activación de email
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../core/Security.php';
require_once __DIR__ . '/../../core/Logger.php';
require_once __DIR__ . '/../../core/helpers.php';

Auth::setConnection($conn);

// Obtener token del GET
$token = getGet('token', '');

if (empty($token)) {
    die(json_encode(['success' => false, 'message' => 'Token no proporcionado']));
}

$result = Auth::verifyEmail($token);

if ($result['success']) {
    // Redirigir a login con mensaje
    header('Location: /petspa/public/login.php?verified=1');
} else {
    // Redirigir con error
    header('Location: /petspa/public/register.php?error=' . urlencode($result['message']));
}
?>
