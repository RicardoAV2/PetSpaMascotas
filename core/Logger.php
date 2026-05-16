<?php
/**
 * CLASE DE LOGGER Y AUDITORÍA
 * =============================
 * Registra todos los eventos importantes del sistema
 * Quién, Cuándo, Dónde, Qué
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/Security.php';

class Logger {

    private static $logFile = null;

    /**
     * Inicializar el logger
     */
    public static function init() {
        self::$logFile = LOG_FILE;
        
        // Crear directorio de logs si no existe
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        // Rotar logs si exceden el tamaño máximo
        self::rotateLogIfNeeded();
    }

    /**
     * Registrar un evento
     * @param string $event Tipo de evento (LOG_EVENT_*)
     * @param int|null $userId ID del usuario (opcional)
     * @param string|null $rol Rol del usuario
     * @param string $description Descripción detallada del evento
     * @param array $metadata Datos adicionales (opcional)
     * @return bool
     */
    public static function log($event, $userId = null, $rol = null, $description = '', $metadata = []) {
        if (!LOG_ENABLED) {
            return false;
        }

        self::init();

        // Obtener información de la sesión si no se proporciona
        if (is_null($userId) && isset($_SESSION['usuario_id'])) {
            $userId = $_SESSION['usuario_id'];
        }
        if (is_null($rol) && isset($_SESSION['rol'])) {
            $rol = $_SESSION['rol'];
        }

        // Obtener información del cliente
        $ip = Security::getClientIP();
        $userAgent = Security::getUserAgent();
        $timestamp = date('Y-m-d H:i:s');

        // Construir el registro
        $logEntry = [
            'timestamp' => $timestamp,
            'evento' => $event,
            'usuario_id' => $userId ?? 'ANONIMO',
            'rol' => $rol ?? 'N/A',
            'ip' => $ip,
            'user_agent' => $userAgent,
            'descripcion' => $description,
            'metadata' => !empty($metadata) ? json_encode($metadata) : '',
        ];

        // Formatear el registro
        $logLine = self::formatLogEntry($logEntry);

        // Escribir en el archivo
        return self::writeToFile($logLine);
    }

    /**
     * Registrar evento de login
     */
    public static function logLogin($userId, $rol) {
        return self::log(
            LOG_EVENT_LOGIN,
            $userId,
            $rol,
            'Inicio de sesión exitoso',
            ['timestamp' => date('Y-m-d H:i:s')]
        );
    }

    /**
     * Registrar evento de logout
     */
    public static function logLogout($userId, $rol) {
        return self::log(
            LOG_EVENT_LOGOUT,
            $userId,
            $rol,
            'Cierre de sesión'
        );
    }

    /**
     * Registrar registro de nuevo usuario
     */
    public static function logRegister($userId, $email, $rol) {
        return self::log(
            LOG_EVENT_REGISTER,
            $userId,
            $rol,
            'Nuevo usuario registrado',
            ['email' => $email, 'rol' => $rol]
        );
    }

    /**
     * Registrar intento fallido de login
     */
    public static function logFailedLogin($email, $attempts = 1) {
        return self::log(
            LOG_EVENT_FAILED_LOGIN,
            null,
            'N/A',
            "Intento de login fallido para: $email (Intento $attempts)",
            ['email' => $email, 'intento' => $attempts]
        );
    }

    /**
     * Registrar bloqueo de cuenta
     */
    public static function logAccountLocked($userId, $email) {
        return self::log(
            LOG_EVENT_ACCOUNT_LOCKED,
            $userId,
            'N/A',
            "Cuenta bloqueada tras múltiples intentos fallidos: $email",
            ['email' => $email]
        );
    }

    /**
     * Registrar cambio de contraseña
     */
    public static function logPasswordChange($userId, $rol) {
        return self::log(
            LOG_EVENT_PASSWORD_CHANGE,
            $userId,
            $rol,
            'Cambio de contraseña realizado'
        );
    }

    /**
     * Registrar creación de usuario por admin
     */
    public static function logUserCreation($adminId, $newUserId, $email, $rol) {
        return self::log(
            LOG_EVENT_USER_CREATE,
            $adminId,
            ROLE_ADMIN,
            "Nuevo usuario creado: $email (Rol: $rol)",
            ['nuevo_usuario_id' => $newUserId, 'email' => $email, 'rol' => $rol]
        );
    }

    /**
     * Registrar eliminación/inactivación de usuario
     */
    public static function logUserDeactivation($adminId, $userId, $email) {
        return self::log(
            LOG_EVENT_USER_DELETE,
            $adminId,
            ROLE_ADMIN,
            "Usuario desactivado: $email",
            ['usuario_inactivado' => $userId, 'email' => $email]
        );
    }

    /**
     * Registrar actualización de usuario
     */
    public static function logUserUpdate($userId, $changes) {
        return self::log(
            LOG_EVENT_USER_UPDATE,
            $userId,
            null,
            'Datos de usuario actualizados',
            $changes
        );
    }

    /**
     * Registrar acceso denegado
     */
    public static function logAccessDenied($userId, $rol, $recurso) {
        return self::log(
            LOG_EVENT_ACCESS_DENIED,
            $userId,
            $rol,
            "Acceso denegado a recurso: $recurso",
            ['recurso' => $recurso]
        );
    }

    /**
     * Registrar venta/transacción
     */
    public static function logSale($userId, $monto, $items) {
        return self::log(
            LOG_EVENT_SALE,
            $userId,
            ROLE_CLIENTE,
            "Venta registrada por monto: $monto",
            ['monto' => $monto, 'items' => $items]
        );
    }

    /**
     * Registrar cita agendada
     */
    public static function logAppointment($userId, $citaId, $groomer, $fecha) {
        return self::log(
            LOG_EVENT_APPOINTMENT,
            $userId,
            ROLE_CLIENTE,
            "Cita agendada con groomer: $groomer para: $fecha",
            ['cita_id' => $citaId, 'groomer' => $groomer, 'fecha' => $fecha]
        );
    }

    /**
     * Formatear entrada del log
     */
    private static function formatLogEntry($entry) {
        $line = "[{$entry['timestamp']}] ";
        $line .= "[{$entry['evento']}] ";
        $line .= "[Usuario: {$entry['usuario_id']}] ";
        $line .= "[Rol: {$entry['rol']}] ";
        $line .= "[IP: {$entry['ip']}] ";
        $line .= "[UA: {$entry['user_agent']}] ";
        $line .= "{$entry['descripcion']} ";
        
        if (!empty($entry['metadata'])) {
            $line .= "| Metadata: {$entry['metadata']} ";
        }
        
        return $line . "\n";
    }

    /**
     * Escribir en archivo de log
     */
    private static function writeToFile($content) {
        if (!self::$logFile) {
            return false;
        }

        try {
            $fp = fopen(self::$logFile, 'a');
            if ($fp) {
                fwrite($fp, $content);
                fclose($fp);
                return true;
            }
        } catch (Exception $e) {
            error_log('Error escribiendo a log: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Rotar logs si exceden el tamaño máximo
     */
    private static function rotateLogIfNeeded() {
        if (!file_exists(self::$logFile)) {
            return;
        }

        if (filesize(self::$logFile) > LOG_MAX_SIZE) {
            $timestamp = date('Y-m-d_H-i-s');
            $backupFile = self::$logFile . ".$timestamp.bak";
            rename(self::$logFile, $backupFile);
            
            // Opcionalmente, eliminar backups muy antiguos
            self::cleanOldBackups();
        }
    }

    /**
     * Limpiar backups antiguos (mantener solo últimos 30 días)
     */
    private static function cleanOldBackups() {
        $files = glob(self::$logFile . '.*.bak');
        $thirtyDaysAgo = time() - (30 * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $thirtyDaysAgo) {
                unlink($file);
            }
        }
    }

    /**
     * Leer logs filtrados
     * @param array $filters Filtros: ['usuario_id' => id, 'evento' => event, 'fecha_inicio' => date, 'fecha_fin' => date]
     * @param int $limit Máximo de registros
     * @return array
     */
    public static function getLogs($filters = [], $limit = 100) {
        if (!file_exists(self::$logFile)) {
            return [];
        }

        $logs = [];
        $fp = fopen(self::$logFile, 'r');
        $count = 0;

        while (($line = fgets($fp)) !== false && $count < $limit) {
            if (self::matchesFilters($line, $filters)) {
                $logs[] = trim($line);
                $count++;
            }
        }

        fclose($fp);
        return array_reverse($logs); // Mostrar más recientes primero
    }

    /**
     * Verificar si una línea de log cumple los filtros
     */
    private static function matchesFilters($line, $filters) {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $key => $value) {
            if (!empty($value) && strpos($line, $value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Exportar logs a CSV
     * @param string $filename Nombre del archivo a generar
     * @param array $filters Filtros opcionales
     */
    public static function exportToCSV($filename, $filters = []) {
        $logs = self::getLogs($filters, 10000);
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Timestamp', 'Evento', 'Usuario', 'Rol', 'IP', 'Descripción']);

        foreach ($logs as $log) {
            $parts = self::parseLogLine($log);
            fputcsv($fp, $parts);
        }

        fclose($fp);
    }

    /**
     * Parsear línea de log para extraer campos
     */
    private static function parseLogLine($line) {
        if (preg_match('/\[(.*?)\].*?\[(.*?)\].*?\[(.*?)\].*?\[(.*?)\].*?\[(.*?)\].*?\[(.*?)\]/', $line, $matches)) {
            return [
                $matches[1] ?? '', // timestamp
                $matches[2] ?? '', // evento
                $matches[3] ?? '', // usuario
                $matches[4] ?? '', // rol
                $matches[5] ?? '', // IP
                trim(str_replace($matches[0], '', $line))
            ];
        }
        return [];
    }
}

// Inicializar logger
Logger::init();
?>
