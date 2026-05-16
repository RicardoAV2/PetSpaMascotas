<?php
/**
 * PRUEBA DE ENVÍO DE CORREO
 * =========================
 * Script para probar la configuración SMTP
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/core/Mailer.php';

echo "Probando envío de correo...\n\n";

$testEmail = 'ricardoatanaciovillca@gmail.com'; // Cambia esto por tu correo real
$testName = 'juan';

$result = Mailer::sendEmail(
    $testEmail,
    $testName,
    'Prueba de SMTP - Pet Spa',
    '<h1>¡Hola!</h1><p>Esta es una prueba de envío de correo desde Pet Spa.</p><p>Si recibes este mensaje, la configuración SMTP funciona correctamente.</p>',
    'Hola! Esta es una prueba de envío de correo desde Pet Spa. Si recibes este mensaje, la configuración SMTP funciona correctamente.'
);

if ($result) {
    echo "✅ Correo enviado exitosamente a: $testEmail\n";
    echo "Revisa tu bandeja de entrada (y spam).\n";
} else {
    echo "❌ Error al enviar el correo.\n";
    echo "Revisa la configuración SMTP en config/constants.php\n";
    echo "También verifica los logs en logs/ para más detalles.\n";
}
?>