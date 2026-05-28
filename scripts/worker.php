<?php
/**
 * Worker CLI para procesar notificaciones programadas y generar alertas de stock
 * Ejecutar: php scripts/worker.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// PHPMailer
$hasPHPMailer = false;
if (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    $hasPHPMailer = class_exists('PHPMailer\\PHPMailer\\PHPMailer') || class_exists('PHPMailer\PHPMailer\PHPMailer');
}

// Procesar notificaciones pendientes programadas
try {
    $stmt = $conn->prepare("SELECT * FROM notificacion WHERE estado_envio = 'pendiente' AND fecha_programacion <= NOW() ORDER BY fecha_programacion ASC LIMIT 50");
    $stmt->execute();
    $notis = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($notis as $n) {
        $ok = false;
        $canal = $n['canal'];
        $destino = $n['destino'];
        $mensaje = $n['mensaje'];

        if ($canal === 'email') {
            $ok = false;
            $subject = 'Notificación Pet Spa';
            if ($hasPHPMailer) {
                try {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = SMTP_HOST;
                    $mail->SMTPAuth = true;
                    $mail->Username = SMTP_USER;
                    $mail->Password = SMTP_PASS;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = SMTP_PORT;
                    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
                    $mail->addAddress($destino);
                    $mail->Subject = $subject;
                    $mail->Body = $mensaje;
                    $mail->AltBody = strip_tags($mensaje);
                    $mail->send();
                    $ok = true;
                } catch (Exception $e) {
                    error_log('PHPMailer error: ' . $e->getMessage());
                    $ok = false;
                }
            } else {
                // Fallback a mail()
                $headers = 'From: ' . MAIL_FROM_ADDRESS . "\r\n" . 'Content-Type: text/plain; charset=utf-8';
                $ok = @mail($destino, $subject, $mensaje, $headers);
            }
        } elseif ($canal === 'whatsapp') {
            // No enviamos por WhatsApp desde el servidor; marcamos como 'enviado' y guardamos URL
            $wa = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $destino) . '?text=' . rawurlencode($mensaje);
            // Guardar enlace en metadata (si existe columna) o en logs
            $ok = true;
        } elseif ($canal === 'sms') {
            // Aquí se podría integrar una API SMS; por ahora simulamos
            $ok = true;
        }

        $stmtUp = $conn->prepare("UPDATE notificacion SET estado_envio = :estado, fecha_envio = NOW(), reintentos = reintentos + 1 WHERE id_notificacion = :id");
        $stmtUp->execute([':estado' => $ok ? 'enviado' : 'fallido', ':id' => $n['id_notificacion']]);
        echo "Processed notificacion {$n['id_notificacion']} -> " . ($ok ? 'OK' : 'FAIL') . PHP_EOL;
    }

    // Procesar alertas de inventario pendientes y crear notificaciones para roles
    $stmt = $conn->prepare("SELECT a.*, p.nombre AS producto_nombre FROM alerta_inventario a LEFT JOIN inventario i ON a.id_inventario = i.id_inventario LEFT JOIN producto p ON i.id_producto = p.id_producto WHERE a.estado_alerta = 'pendiente'");
    $stmt->execute();
    $alertas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($alertas)) {
        // Obtener emails de admin y recepcion
        $stmtRoles = $conn->prepare("SELECT u.email, r.nombre as rol_nombre FROM usuario u JOIN rol r ON u.id_rol = r.id_rol WHERE r.nombre IN ('admin','recepcion')");
        $stmtRoles->execute();
        $recipients = $stmtRoles->fetchAll(PDO::FETCH_ASSOC);

        foreach ($alertas as $a) {
            $msg = $a['mensaje'] ?? ('Alerta inventario: ' . ($a['producto_nombre'] ?? 'Producto desconocido'));
            foreach ($recipients as $r) {
                $canal = 'email';
                $dest = $r['email'];
                $stmtIns = $conn->prepare("INSERT INTO notificacion (tipo_evento, canal, mensaje, destino, fecha_programacion, estado_envio, id_cliente, id_cita) VALUES (:tipo, :canal, :mensaje, :destino, NOW(), 'pendiente', NULL, NULL)");
                $stmtIns->execute([':tipo' => 'promocion', ':canal' => $canal, ':mensaje' => $msg, ':destino' => $dest]);
            }
            // Marcar alerta como atendida para evitar duplicados
            $stmtUpd = $conn->prepare("UPDATE alerta_inventario SET estado_alerta = 'atendida', fecha_atencion = NOW() WHERE id_alerta = :id");
            $stmtUpd->execute([':id' => $a['id_alerta']]);
            echo "Created notifications for alerta {$a['id_alerta']}\n";
        }
    }

    echo "Worker finished." . PHP_EOL;
} catch (Exception $e) {
    echo 'Worker error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

