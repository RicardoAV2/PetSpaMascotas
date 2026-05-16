<?php
/**
 * CLASE MIDDLEWARE
 * =================
 * Valida permisos, roles, sesiones, tokens, etc.
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Security.php';

class Middleware {

    public static function requireLogin() {
        if (!isset($_SESSION['usuario_id'])) {
            header('Location: /petspa/public/login.php');
            exit();
        }
    }

    public static function requireAuth() {
        self::requireLogin();
    }

    public static function requireRole($roles, $dieOnFail = true) {
        self::requireAuth();

        $roles = is_array($roles) ? $roles : [$roles];
        $userRole = $_SESSION['rol'] ?? null;

        if (!in_array($userRole, $roles)) {
            Logger::logAccessDenied(
                $_SESSION['usuario_id'] ?? null,
                $userRole,
                'Recurso con rol restringido'
            );

            if ($dieOnFail) {
                http_response_code(403);
                die(json_encode(['error' => 'Acceso denegado. Rol insuficiente.']));
            }
            return false;
        }

        return true;
    }

    public static function requireAdmin() {
        self::requireRole(ROLE_ADMIN);
    }

    public static function checkSessionTimeout() {
        if (!isset($_SESSION['usuario_id'])) {
            return;
        }

        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        $elapsed = time() - $_SESSION['last_activity'];

        if ($elapsed > SESSION_TIMEOUT) {
            Logger::log(
                'session_timeout',
                $_SESSION['usuario_id'] ?? null,
                $_SESSION['rol'] ?? null,
                'Sesión expirada por inactividad'
            );

            session_destroy();
            header('Location: /petspa/public/login.php?expired=1');
            exit();
        }

        $_SESSION['last_activity'] = time();
    }

    public static function validateAccountStatus($conn, $userId) {
        try {
            $stmt = $conn->prepare("
                SELECT id_usuario, estado, email
                FROM usuario 
                WHERE id_usuario = ?
            ");
            
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return [
                    'valid' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }

            if ($user['estado'] == USER_STATUS_INACTIVE) {
                return [
                    'valid' => false,
                    'message' => 'La cuenta está inactiva'
                ];
            }

            return [
                'valid' => true,
                'message' => 'OK',
                'user' => $user
            ];
        } catch (Exception $e) {
            error_log('Error en validateAccountStatus: ' . $e->getMessage());
            return [
                'valid' => false,
                'message' => 'Error en la validación'
            ];
        }
    }

    public static function validateEmailToken($conn, $token) {
        if (empty($token)) {
            return ['valid' => false, 'message' => 'Token no proporcionado'];
        }

        try {
            $stmt = $conn->prepare("SHOW COLUMNS FROM usuario LIKE 'email_verification_token'");
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return ['valid' => false, 'message' => 'Verificación de email no soportada'];
            }
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Verificación de email no soportada'];
        }

        $tokenHash = Security::hashToken($token);

        try {
            $stmt = $conn->prepare("
                SELECT id_usuario, email_verification_expiry
                FROM usuario 
                WHERE email_verification_token = ? 
                LIMIT 1
            ");
            
            $stmt->execute([$tokenHash]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['valid' => false, 'message' => 'Token inválido'];
            }

            $expiryTime = strtotime($user['email_verification_expiry']);
            if (time() > $expiryTime) {
                return ['valid' => false, 'message' => 'El token ha expirado. Solicite uno nuevo.'];
            }

            return ['valid' => true, 'user_id' => $user['id_usuario'], 'message' => 'Token válido'];
        } catch (Exception $e) {
            error_log('Error en validateEmailToken: ' . $e->getMessage());
            return ['valid' => false, 'message' => 'Error en la validación del token'];
        }
    }

    public static function checkLoginAttempts($conn, $email) {
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as attempts
                FROM intentos_login_fallidos
                WHERE email = ? AND fecha_intento > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            
            $stmt->execute([$email]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $attempts = (int) $result['attempts'];

            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                return [
                    'can_login' => false,
                    'attempts' => $attempts,
                    'message' => 'Cuenta bloqueada. Intente en 15 minutos.'
                ];
            }

            return ['can_login' => true, 'attempts' => $attempts, 'message' => 'OK'];
        } catch (Exception $e) {
            return ['can_login' => true, 'attempts' => 0, 'message' => 'OK'];
        }
    }

    public static function recordFailedLogin($conn, $email) {
        try {
            $conn->exec("
                CREATE TABLE IF NOT EXISTS intentos_login_fallidos (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    email VARCHAR(100),
                    ip VARCHAR(45),
                    user_agent TEXT,
                    fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (email, fecha_intento)
                )
            ");

            $stmt = $conn->prepare("
                INSERT INTO intentos_login_fallidos (email, ip, user_agent)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $email,
                Security::getClientIP(),
                Security::getUserAgent()
            ]);
        } catch (Exception $e) {
            error_log('Error recordFailedLogin: ' . $e->getMessage());
        }
    }

    public static function clearFailedLogin($conn, $email) {
        try {
            $stmt = $conn->prepare("DELETE FROM intentos_login_fallidos WHERE email = ?");
            $stmt->execute([$email]);
        } catch (Exception $e) {
            error_log('Error clearFailedLogin: ' . $e->getMessage());
        }
    }

    public static function requirePOST() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            die(json_encode(['error' => 'Método no permitido']));
        }
    }

    public static function validateCSRF() {
        self::requirePOST();
        $token = $_POST['csrf_token'] ?? '';

        if (!Security::validateCSRFToken($token)) {
            http_response_code(403);
            die(json_encode(['error' => 'Token CSRF inválido']));
        }
    }

    public static function sendError($message, $code = 400) {
        http_response_code($code);
        die(json_encode(['error' => $message]));
    }

    public static function sendSuccess($data = [], $message = 'OK') {
        http_response_code(200);
        die(json_encode(['success' => true, 'message' => $message, 'data' => $data]));
    }

    public static function requireAJAX() {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            self::sendError('Solicitud inválida', 403);
        }
    }

    public static function checkRateLimit($action, $maxRequests = 10, $timeWindow = 3600) {
        $ip = Security::getClientIP();
        $key = "ratelimit_{$action}_{$ip}";
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }

        if (time() > $_SESSION[$key]['reset_time']) {
            $_SESSION[$key] = ['count' => 0, 'reset_time' => time() + $timeWindow];
        }

        $_SESSION[$key]['count']++;

        return $_SESSION[$key]['count'] <= $maxRequests;
    }
}

// Funciones compatibles con versiones anteriores
function requireLogin() {
    Middleware::requireLogin();
}

function requireRole($rol) {
    Middleware::requireRole($rol);
}

function requireAdmin() {
    Middleware::requireAdmin();
}

if (!function_exists('isLogged')) {
    function isLogged() {
        return isset($_SESSION['usuario_id']);
    }
}
?>

