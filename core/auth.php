<?php
/**
 * CLASE DE AUTENTICACIÓN
 * ======================
 * Maneja login, register, 2FA, sesiones, etc.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/middleware.php';

// Iniciar sesión con configuración segura
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'] ?? '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

class Auth {

    private static $conn = null;

    /**
     * Inicializar conexión a BD
     */
    public static function setConnection($pdo) {
        self::$conn = $pdo;
    }

    /**
     * Login con email y contraseña
     * @param string $email Email del usuario
     * @param string $password Contraseña
     * @return array ['success' => bool, 'message' => string, 'require_2fa' => bool, 'temp_token' => string]
     */
    public static function login($email, $password) {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Error de conexión a la base de datos'];
        }

        // Sanitizar input
        $email = Security::sanitizeInput($email);

        // Validar email
        if (!Security::isValidEmail($email)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }

        // Validar rate limit
        if (!Middleware::checkRateLimit('login', 10, 300)) {
            Logger::log(LOG_EVENT_FAILED_LOGIN, null, null, "Rate limit excedido para: $email");
            return ['success' => false, 'message' => 'Demasiados intentos. Espere unos minutos.'];
        }

        // Verificar bloqueo por intentos fallidos
        $lockCheck = Middleware::checkLoginAttempts(self::$conn, $email);
        if (!$lockCheck['can_login']) {
            Logger::logAccountLocked(null, $email);
            return ['success' => false, 'message' => $lockCheck['message']];
        }

        // Buscar usuario
        try {
            $stmt = self::$conn->prepare("
                SELECT u.*, r.nombre as rol_nombre
                FROM usuario u
                JOIN rol r ON u.id_rol = r.id_rol
                WHERE u.email = ? AND u.estado = ?
                LIMIT 1
            ");

            $stmt->execute([$email, USER_STATUS_ACTIVE]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                Logger::logFailedLogin($email, $lockCheck['attempts'] + 1);
                Middleware::recordFailedLogin(self::$conn, $email);
                return ['success' => false, 'message' => 'Credenciales inválidas'];
            }

            if (isset($user['email_verified']) && !$user['email_verified']) {
                return ['success' => false, 'message' => 'Debes verificar tu correo antes de iniciar sesión.'];
            }

            // Si el usuario se registró con Google (password_hash vacío), no permitir login normal
            if (empty($user['password_hash'])) {
                return ['success' => false, 'message' => 'Esta cuenta se registró con Google. Usa el botón de Google para iniciar sesión.', 'use_google' => true];
            }

            // Verificar contraseña
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                Logger::logFailedLogin($email, $lockCheck['attempts'] + 1);
                Middleware::recordFailedLogin(self::$conn, $email);
                return ['success' => false, 'message' => 'Credenciales inválidas'];
            }

            // Si es admin, requiere 2FA
            if ($user['id_rol'] === '1' && TWO_FA_ENABLED_FOR_ADMIN && $user['two_factor_enabled']) {
                // Generar token temporal para 2FA
                $tempToken = Security::generateToken();
                $_SESSION['temp_token'] = $tempToken;
                $_SESSION['temp_user_id'] = $user['id_usuario'];
                $_SESSION['temp_email'] = $email;

                return [
                    'success' => true,
                    'require_2fa' => true,
                    'message' => 'Verificación de dos factores requerida',
                    'temp_token' => $tempToken
                ];
            }

            // Login exitoso
            self::createSession($user);
            Middleware::clearFailedLogin(self::$conn, $email);
            Logger::logLogin($user['id_usuario'], $user['rol_nombre']);

            return ['success' => true, 'message' => 'Login exitoso', 'user' => $user];

        } catch (Exception $e) {
            error_log('Error en login: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error en la autenticación'];
        }
    }

    /**
     * Verificar código 2FA
     * @param string $code Código de 6 dígitos
     * @return array ['success' => bool, 'message' => string]
     */
    public static function verify2FA($code) {
        if (!isset($_SESSION['temp_user_id'])) {
            return ['success' => false, 'message' => 'Sesión temporal no encontrada'];
        }

        try {
            $stmt = self::$conn->prepare("
                SELECT id_usuario, two_factor_secret, rol_nombre
                FROM usuario u
                JOIN rol r ON u.id_rol = r.id_rol
                WHERE u.id_usuario = ?
            ");

            $stmt->execute([$_SESSION['temp_user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }

            // Verificar código TOTP
            if (!Security::verify2FACode($user['two_factor_secret'], $code)) {
                Logger::log(LOG_EVENT_FAILED_LOGIN, $user['id_usuario'], null, 'Código 2FA inválido');
                return ['success' => false, 'message' => 'Código 2FA inválido'];
            }

            // Crear sesión
            self::createSession($user);
            Logger::logLogin($user['id_usuario'], $user['rol_nombre']);

            // Limpiar sesión temporal
            unset($_SESSION['temp_token']);
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_email']);

            return ['success' => true, 'message' => 'Autenticación 2FA completada'];

        } catch (Exception $e) {
            error_log('Error en verify2FA: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error verificando 2FA'];
        }
    }

    /**
     * Registrar nuevo usuario (SOLO CLIENTES)
     * @param array $data Datos del usuario
     * @return array ['success' => bool, 'message' => string, 'user_id' => int]
     */
    public static function register($data) {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Error de conexión'];
        }

        // Sanitizar datos
        $email = Security::sanitizeInput($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $nombre = Security::sanitizeInput($data['nombre'] ?? '');
        $apellido = Security::sanitizeInput($data['apellido'] ?? '');
        $telefono = Security::sanitizeInput($data['telefono'] ?? '');
        $ci = Security::sanitizeInput($data['ci'] ?? '');
        $direccion = Security::sanitizeInput($data['direccion'] ?? '');

        // Determinar si el esquema soporta verificación por email por token
        $tokenEnabled = false;
        try {
            $stmt = self::$conn->prepare("SHOW COLUMNS FROM usuario LIKE 'email_verification_token'");
            $stmt->execute();
            $tokenEnabled = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $tokenEnabled = false;
        }

        // Validaciones
        if (empty($email) || empty($password) || empty($nombre)) {
            return ['success' => false, 'message' => 'Campos requeridos incompletos'];
        }

        if (!Security::isValidEmail($email)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }

        $passValidation = Security::validatePassword($password);
        if (!$passValidation['valid']) {
            return [
                'success' => false,
                'message' => 'Contraseña débil',
                'errors' => $passValidation['errors']
            ];
        }

        if (!empty($telefono) && !Security::isValidPhone($telefono)) {
            return ['success' => false, 'message' => 'Teléfono inválido'];
        }

        if (!empty($ci) && !Security::isValidCI($ci)) {
            return ['success' => false, 'message' => 'Cédula inválida'];
        }

        try {
            // Verificar email único
            $stmt = self::$conn->prepare("SELECT id_usuario FROM usuario WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }

            // Hash de contraseña
            $passwordHash = Security::hashPassword($password);

            // Obtener ID de rol "cliente"
            $stmt = self::$conn->prepare("SELECT id_rol FROM rol WHERE nombre = ?");
            $stmt->execute([ROLE_CLIENTE]);
            $roleResult = $stmt->fetch();
            $roleId = $roleResult['id_rol'] ?? 4;

            if ($tokenEnabled) {
                // Generar token de verificación
                $emailToken = Security::generateToken();
                $tokenHash = Security::hashToken($emailToken);
                $tokenExpiry = date('Y-m-d H:i:s', time() + TOKEN_EXPIRY);

                $stmt = self::$conn->prepare("
                    INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado, email_verification_token, email_verification_expiry, email_verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $email,
                    $passwordHash,
                    $nombre,
                    $apellido,
                    $telefono,
                    $roleId,
                    USER_STATUS_ACTIVE,
                    $tokenHash,
                    $tokenExpiry,
                    false
                ]);
            } else {
                $stmt = self::$conn->prepare("
                    INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado, email_verified)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $email,
                    $passwordHash,
                    $nombre,
                    $apellido,
                    $telefono,
                    $roleId,
                    USER_STATUS_ACTIVE,
                    true
                ]);
            }

            $userId = self::$conn->lastInsertId();

            if ($tokenEnabled) {
                $mailSent = Mailer::sendVerificationEmail($email, $nombre ?: $email, $emailToken);
                if (!$mailSent) {
                    error_log('No se pudo enviar el email de verificación a: ' . $email);
                }
            }

            // Insertar datos de cliente
            $stmt = self::$conn->prepare("
                INSERT INTO cliente (id_cliente, direccion, ci)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([$userId, $direccion, $ci]);

            // Log del registro
            Logger::logRegister($userId, $email, ROLE_CLIENTE);

            $response = [
                'success' => true,
                'message' => $tokenEnabled
                    ? 'Registro exitoso. Verifique su email para activar la cuenta.'
                    : 'Registro exitoso. Ya puedes iniciar sesión.',
                'user_id' => $userId
            ];

            if ($tokenEnabled) {
                $response['email_token'] = $emailToken; // Solo para pruebas en entornos que soportan verificación
            }

            return $response;

        } catch (Exception $e) {
            error_log('Error en register: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }

    /**
     * Verificar email del nuevo usuario
     * @param string $token Token de verificación
     * @return array ['success' => bool, 'message' => string]
     */
    public static function verifyEmail($token) {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Error de conexión'];
        }

        // Verificación de email no está soportada si el esquema no tiene los campos necesarios
        try {
            $stmt = self::$conn->prepare("SHOW COLUMNS FROM usuario LIKE 'email_verification_token'");
            $stmt->execute();
            $hasEmailToken = (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $hasEmailToken = false;
        }

        if (!$hasEmailToken) {
            return ['success' => false, 'message' => 'Verificación de email no disponible en este sistema.'];
        }

        $validation = Middleware::validateEmailToken(self::$conn, $token);
        
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $userId = $validation['user_id'];

            // Activar cuenta
            $stmt = self::$conn->prepare("
                UPDATE usuario 
                SET email_verified = ?, email_verification_token = NULL, email_verification_expiry = NULL
                WHERE id_usuario = ?
            ");

            $stmt->execute([true, $userId]);

            Logger::log(LOG_EVENT_REGISTER, $userId, ROLE_CLIENTE, 'Email verificado y cuenta activada');

            return ['success' => true, 'message' => 'Email verificado. Su cuenta está activada.'];

        } catch (Exception $e) {
            error_log('Error en verifyEmail: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error verificando email'];
        }
    }

    /**
     * Logout
     */
    public static function logout() {
        if (isset($_SESSION['usuario_id'])) {
            Logger::logLogout($_SESSION['usuario_id'], $_SESSION['rol'] ?? null);
        }
        
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Login directo usando datos de usuario ya cargados
     */
    public static function loginUser(array $user) {
        if (!self::$conn) {
            return false;
        }

        if (!isset($user['rol_nombre']) && isset($user['rol'])) {
            $user['rol_nombre'] = $user['rol'];
        }

        self::createSession($user);

        if (isset($user['id_usuario'], $user['rol_nombre'])) {
            Logger::logLogin($user['id_usuario'], $user['rol_nombre']);
        }

        return true;
    }

    /**
     * Crear sesión de usuario
     */
    private static function createSession($user) {
        $_SESSION['usuario_id'] = $user['id_usuario'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['nombre'] = $user['nombre'] ?? $user['email'] ?? '';
        $_SESSION['rol'] = $user['rol_nombre'] ?? $user['rol'] ?? '';
        $_SESSION['last_activity'] = time();
        $_SESSION['login_ip'] = Security::getClientIP();
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
    }

    /**
     * Obtener usuario actual
     */
    public static function getCurrentUser() {
        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        return [
            'id' => $_SESSION['usuario_id'],
            'email' => $_SESSION['email'],
            'nombre' => $_SESSION['nombre'],
            'rol' => $_SESSION['rol']
        ];
    }

    /**
     * Cambiar contraseña
     * @param int $userId ID del usuario
     * @param string $oldPassword Contraseña antigua
     * @param string $newPassword Nueva contraseña
     * @return array ['success' => bool, 'message' => string]
     */
    public static function changePassword($userId, $oldPassword, $newPassword) {
        if (!self::$conn) {
            return ['success' => false, 'message' => 'Error de conexión'];
        }

        // Validar nueva contraseña
        $validation = Security::validatePassword($newPassword);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => 'Contraseña débil', 'errors' => $validation['errors']];
        }

        try {
            $stmt = self::$conn->prepare("SELECT password_hash FROM usuario WHERE id_usuario = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user || !Security::verifyPassword($oldPassword, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Contraseña antigua incorrecta'];
            }

            $newHash = Security::hashPassword($newPassword);
            $stmt = self::$conn->prepare("UPDATE usuario SET password_hash = ? WHERE id_usuario = ?");
            $stmt->execute([$newHash, $userId]);

            Logger::logPasswordChange($userId, $_SESSION['rol'] ?? null);

            return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];

        } catch (Exception $e) {
            error_log('Error en changePassword: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error al cambiar contraseña'];
        }
    }
}

// Compatibilidad con versiones anteriores
function login($usuario) {
    $_SESSION['usuario_id'] = $usuario['id_usuario'];
    $_SESSION['rol'] = $usuario['rol'];
}
?>