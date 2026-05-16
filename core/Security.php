<?php
/**
 * CLASE DE SEGURIDAD
 * ===================
 * Maneja: BCrypt, Validaciones, Sanitización, Tokens, 2FA
 */

require_once __DIR__ . '/../config/constants.php';

class Security {

    /**
     * ===== HASHING DE CONTRASEÑAS =====
     */

    /**
     * Hashear contraseña con BCrypt
     * @param string $password Contraseña en texto plano
     * @return string Contraseña hasheada
     */
    public static function hashPassword($password) {
        if (empty($password)) {
            throw new Exception('La contraseña no puede estar vacía');
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    }

    /**
     * Verificar contraseña contra su hash
     * @param string $password Contraseña en texto plano
     * @param string $hash Hash almacenado
     * @return bool True si la contraseña es correcta
     */
    public static function verifyPassword($password, $hash) {
        if (empty($password) || empty($hash)) {
            return false;
        }
        return password_verify($password, $hash);
    }

    /**
     * ===== VALIDACIÓN DE CONTRASEÑAS =====
     */

    /**
     * Validar que la contraseña cumpla con los requisitos de seguridad
     * @param string $password Contraseña a validar
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validatePassword($password) {
        $errors = [];
        
        // Longitud mínima
        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors[] = "Mínimo " . MIN_PASSWORD_LENGTH . " caracteres requeridos";
        }

        // Mayúsculas
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Debe incluir al menos una mayúscula (A-Z)";
        }

        // Minúsculas
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Debe incluir al menos una minúscula (a-z)";
        }

        // Números
        if (PASSWORD_REQUIRE_NUMBERS && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Debe incluir al menos un número (0-9)";
        }

        // Símbolos
        if (PASSWORD_REQUIRE_SYMBOLS && !preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = "Debe incluir al menos un símbolo especial (!@#$%^&*...)";
        }

        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }

    /**
     * Generar sugerencia de contraseña fuerte (Passphrase)
     * @return string Contraseña sugerida
     */
    public static function generateStrongPassword() {
        $words = [
            'Gato', 'Perro', 'Luna', 'Lluvia', 'Nube', 'Árbol', 'Flor', 'Café',
            'Libro', 'Música', 'Viaje', 'Montaña', 'Río', 'Playa', 'Cielo', 'Fuego'
        ];
        
        $password = '';
        for ($i = 0; $i < 4; $i++) {
            $password .= $words[array_rand($words)];
        }
        
        $password .= rand(100, 999); // Agregar números
        $password .= array_rand(array_flip(['!', '@', '#', '$', '%', '^', '&', '*'])); // Agregar símbolo
        
        return $password;
    }

    /**
     * ===== VALIDACIÓN DE EMAIL =====
     */

    /**
     * Validar formato de email
     * @param string $email Email a validar
     * @return bool True si es válido
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * ===== SANITIZACIÓN =====
     */

    /**
     * Sanitizar entrada para evitar XSS
     * @param string $input Entrada del usuario
     * @return string Entrada sanitizada
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        
        // Convertir caracteres especiales a entidades HTML
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitizar string para SQL (prevenir SQLi)
     * @param PDO $conn Conexión a la BD
     * @param string $input Entrada
     * @return string String escapado
     */
    public static function sanitizeSQL($conn, $input) {
        // PDO con prepared statements es más seguro que esto,
        // pero como fallback:
        return addslashes($input);
    }

    /**
     * Validar que el input sea solo números
     * @param string $input Entrada
     * @return bool
     */
    public static function isNumeric($input) {
        return is_numeric($input);
    }

    /**
     * Validar que el input sea solo alfanumérico
     * @param string $input Entrada
     * @return bool
     */
    public static function isAlphanumeric($input) {
        return preg_match('/^[a-zA-Z0-9\s\-_àáâãäåèéêëìíîïòóôõöùúûüýÿñçA-Z]+$/u', $input);
    }

    /**
     * ===== GENERACIÓN DE TOKENS =====
     */

    /**
     * Generar token de activación/verificación
     * @return string Token seguro
     */
    public static function generateToken($length = 64) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        }
        // Fallback si random_bytes no está disponible
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    }

    /**
     * Generar hash del token para almacenar en BD (no almacenar token en texto plano)
     * @param string $token Token sin hash
     * @return string Hash SHA-256
     */
    public static function hashToken($token) {
        return hash('sha256', $token);
    }

    /**
     * Verificar token
     * @param string $token Token a verificar
     * @param string $storedHash Hash almacenado en BD
     * @return bool
     */
    public static function verifyToken($token, $storedHash) {
        return hash_equals(self::hashToken($token), $storedHash);
    }

    /**
     * ===== 2FA (TWO-FACTOR AUTHENTICATION) =====
     */

    /**
     * Generar secret para Google Authenticator (TOTP)
     * Requiere librería: composer require spomky-labs/otphp
     * @return string Secret codificado en Base32
     */
    public static function generate2FASecret() {
        // Generar 32 caracteres aleatorios
        $secret = base64_encode(random_bytes(32));
        // Convertir a Base32 (requerido por Google Authenticator)
        return self::toBase32($secret);
    }

    /**
     * Convertir a Base32
     * @param string $input Entrada binaria
     * @return string Base32 encoded
     */
    private static function toBase32($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $b = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $v = ($v << 8) | ord($input[$i]);
            $b += 8;
            
            while ($b >= 5) {
                $b -= 5;
                $output .= $alphabet[($v >> $b) & 31];
            }
        }
        
        if ($b > 0) {
            $output .= $alphabet[($v << (5 - $b)) & 31];
        }
        
        return $output;
    }

    /**
     * Generar código QR para 2FA (requiere librería qrcode)
     * @param string $email Email del usuario
     * @param string $secret Secret de 2FA
     * @return string URL para generar QR
     */
    public static function generate2FAQRCode($email, $secret) {
        $label = urlencode(TWO_FA_ISSUER_NAME . ' (' . $email . ')');
        $encoded_secret = urlencode($secret);
        
        // Usando Google Charts API
        return "https://chart.googleapis.com/chart?chs=300x300&chld=M|0&cht=qr&chl="
            . urlencode("otpauth://totp/$label?secret=$encoded_secret&issuer=" . urlencode(TWO_FA_ISSUER_NAME));
    }

    /**
     * Verificar código TOTP (Google Authenticator)
     * @param string $secret Secret del usuario
     * @param string $code Código de 6 dígitos
     * @param int $timeWindow Ventana de tiempo en pasos (default 1 = ±30 segundos)
     * @return bool
     */
    public static function verify2FACode($secret, $code, $timeWindow = 1) {
        $time = floor(time() / 30);
        
        for ($i = -$timeWindow; $i <= $timeWindow; $i++) {
            if (self::totp($secret, $time + $i) == $code) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generar código TOTP
     * @param string $secret Secret en Base32
     * @param int $time Timestamp
     * @return string Código de 6 dígitos
     */
    private static function totp($secret, $time) {
        $secretBinary = self::base32Decode($secret);
        $hash = hash_hmac('sha1', pack('N', $time), $secretBinary, true);
        $offset = ord($hash[19]) & 0xF;
        $code = unpack('N', substr($hash, $offset, 4))[1];
        $code &= 0x7FFFFFFF;
        return str_pad($code % 1000000, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decodificar Base32
     * @param string $input Base32 encoded
     * @return string Binario
     */
    private static function base32Decode($input) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $b = 0;
        
        for ($i = 0; $i < strlen($input); $i++) {
            $v = ($v << 5) | (strpos($alphabet, $input[$i]) & 31);
            $b += 5;
            
            if ($b >= 8) {
                $b -= 8;
                $output .= chr(($v >> $b) & 255);
            }
        }
        
        return $output;
    }

    /**
     * ===== FUNCIONES AUXILIARES DE SEGURIDAD =====
     */

    /**
     * Obtener IP del cliente
     * @return string Dirección IP
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }

    /**
     * Obtener información del navegador/User Agent
     * @return string User Agent
     */
    public static function getUserAgent() {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
    }

    /**
     * Generar token CSRF para formularios
     * @return string Token único
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validar token CSRF
     * @param string $token Token a validar
     * @return bool
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Validar teléfono
     * @param string $phone Teléfono
     * @return bool
     */
    public static function isValidPhone($phone) {
        $phone = preg_replace('/\D/', '', $phone);
        return strlen($phone) >= 7 && strlen($phone) <= 15;
    }

    /**
     * Validar cédula de identidad (CI) - Formato boliviano/genérico
     * @param string $ci Cédula
     * @return bool
     */
    public static function isValidCI($ci) {
        $ci = preg_replace('/\D/', '', $ci);
        return strlen($ci) >= 6 && strlen($ci) <= 20;
    }
}
?>
