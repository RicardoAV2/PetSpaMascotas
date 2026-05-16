<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../config/constants.php';

class Mailer {
    public static function sendEmail($to, $toName, $subject, $htmlBody, $altBody = '') {
        $mail = new PHPMailer(true);

        try {
            if (!empty(SMTP_HOST) && !empty(SMTP_USER) && !empty(SMTP_PASS)) {
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = SMTP_PORT;
            } else {
                $mail->isMail();
            }

            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $altBody ?: strip_tags($htmlBody);

            return $mail->send();
        } catch (Exception $e) {
            error_log('Mailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    public static function sendVerificationEmail($to, $name, $token) {
        $verificationUrl = APP_URL . '/api/auth/verify_email.php?token=' . urlencode($token);
        $subject = 'Verifica tu cuenta en Pet Spa';
        $body = '<p>Hola ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>' .
            '<p>Gracias por registrarte en Pet Spa. Para completar tu registro y activar tu cuenta, haz clic en el siguiente botón:</p>' .
            '<p><a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;padding:12px 24px;background:#007bff;color:#ffffff;text-decoration:none;border-radius:4px;">Verificar mi cuenta</a></p>' .
            '<p>Si no funciona el botón, copia y pega esta dirección en tu navegador:</p>' .
            '<p>' . htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p>Este enlace expira en 15 minutos.</p>' .
            '<p>Bienvenido a Pet Spa.</p>';

        return self::sendEmail($to, $name, $subject, $body);
    }
}
