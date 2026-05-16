<?php
/**
 * FUNCIONES AUXILIARES GLOBALES
 * ==============================
 */

require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/Auth.php';

/**
 * Renderizar error bootstrap
 */
function showAlert($message, $type = 'danger') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

/**
 * Validar que el usuario pertenezca a un rol específico
 */
function hasRole($role) {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === $role;
}

/**
 * Obtener nombre de rol en español
 */
function getRoleLabel($role) {
    $labels = [
        'admin' => 'Administrador',
        'groomer' => 'Groomer/Estilista',
        'recepcion' => 'Recepción',
        'cliente' => 'Cliente'
    ];
    return $labels[$role] ?? ucfirst($role);
}

/**
 * Formatear fecha a formato legible
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

/**
 * Formatear dinero
 */
function formatCurrency($amount, $currency = 'Bs.') {
    return $currency . ' ' . number_format($amount, 2, ',', '.');
}

/**
 * Convertir array a opciones HTML
 */
function arrayToOptions($array, $selected = null) {
    $options = '';
    foreach ($array as $key => $value) {
        $sel = ($key === $selected) ? 'selected' : '';
        $options .= "<option value='$key' $sel>$value</option>";
    }
    return $options;
}

/**
 * Obtener badge de estado
 */
function getStatusBadge($status) {
    if ($status === true || $status === '1' || $status === 1) {
        return "<span class='badge bg-success'>Activo</span>";
    } else if ($status === false || $status === '0' || $status === 0) {
        return "<span class='badge bg-danger'>Inactivo</span>";
    } else if ($status === 'pending') {
        return "<span class='badge bg-warning'>Pendiente</span>";
    }
    return "<span class='badge bg-secondary'>$status</span>";
}

/**
 * Generar saludo según la hora
 */
function getGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return 'Buenos días';
    } else if ($hour >= 12 && $hour < 17) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
}

/**
 * Verificar si una URL es activa (para menús)
 */
function isActivePage($page) {
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    return ($currentPage === $page) ? 'active' : '';
}

/**
 * Redirigir a una página
 */
function redirect($url) {
    header('Location: ' . $url);
    exit();
}

/**
 * Obtener dato de formulario de forma segura
 */
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? Security::sanitizeInput($_POST[$key]) : $default;
}

function getGet($key, $default = '') {
    return isset($_GET[$key]) ? Security::sanitizeInput($_GET[$key]) : $default;
}

/**
 * Validar CI (cédula de identidad)
 */
function isValidCI($ci) {
    return Security::isValidCI($ci);
}

/**
 * Validar teléfono
 */
function isValidPhone($phone) {
    return Security::isValidPhone($phone);
}

/**
 * Generar contraseña fuerte sugerida
 */
function suggestPassword() {
    return Security::generateStrongPassword();
}

/**
 * Calcular fortaleza de contraseña
 */
function getPasswordStrength($password) {
    $validation = Security::validatePassword($password);
    
    if (!$validation['valid']) {
        return ['strength' => 'weak', 'label' => 'Débil', 'color' => 'danger'];
    }
    
    $score = 0;
    
    // Longitud
    if (strlen($password) >= 12) $score++;
    if (strlen($password) >= 16) $score++;
    
    // Caracteres especiales
    if (preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\/\\|`~]/u', $password)) $score++;
    
    // Números y letras
    if (preg_match('/[0-9]/', $password) && preg_match('/[a-z]/', $password)) $score++;
    
    if ($score <= 1) {
        return ['strength' => 'weak', 'label' => 'Débil', 'color' => 'danger'];
    } else if ($score <= 2) {
        return ['strength' => 'medium', 'label' => 'Medio', 'color' => 'warning'];
    } else if ($score <= 3) {
        return ['strength' => 'strong', 'label' => 'Fuerte', 'color' => 'success'];
    } else {
        return ['strength' => 'very-strong', 'label' => 'Muy Fuerte', 'color' => 'success'];
    }
}

/**
 * Obtener initiales del nombre
 */
function getInitials($nombre, $apellido = '') {
    $initials = substr($nombre, 0, 1);
    if ($apellido) {
        $initials .= substr($apellido, 0, 1);
    }
    return strtoupper($initials);
}

/**
 * Truncar texto
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Obtener lista de roles para admin
 */
function getRolesList() {
    return [
        'admin' => 'Administrador',
        'groomer' => 'Groomer/Estilista',
        'recepcion' => 'Personal de Recepción',
        'cliente' => 'Cliente'
    ];
}

/**
 * Obtener lista de especializaciones para groomers
 */
function getGroomerSpecialties() {
    return [
        'baño' => 'Baño y Secado',
        'corte' => 'Corte de Pelo',
        'uñas' => 'Corte de Uñas',
        'oidos' => 'Limpieza de Oídos',
        'completo' => 'Servicio Completo',
        'spa' => 'Spa y Masajes'
    ];
}

/**
 * Obtener lista de especies de mascotas
 */
function getPetSpecies() {
    return [
        'perro' => 'Perro',
        'gato' => 'Gato',
        'conejo' => 'Conejo',
        'pajaro' => 'Pájaro',
        'hamster' => 'Hámster',
        'otro' => 'Otro'
    ];
}

/**
 * Formatear tamaño de archivo
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Obtener avatar placeholder
 */
function getAvatarURL($nombre = 'Usuario', $apellido = '') {
    $initials = getInitials($nombre, $apellido);
    return "https://ui-avatars.com/api/?name=$initials&background=random";
}

/**
 * Validar permisos para acción
 */
function canPerformAction($action, $userRole = null) {
    if (!$userRole && isset($_SESSION['rol'])) {
        $userRole = $_SESSION['rol'];
    }

    $permissions = [
        'crear_usuario' => ['admin'],
        'editar_usuario' => ['admin'],
        'eliminar_usuario' => ['admin'],
        'ver_reportes' => ['admin'],
        'gestionar_inventario' => ['admin', 'recepcion', 'groomer'],
        'crear_cita' => ['cliente', 'recepcion'],
        'calificar_servicio' => ['cliente'],
    ];

    return in_array($userRole, $permissions[$action] ?? []);
}

/**
 * Crear token CSRF y devolverlo en HTML
 */
function getCSRFField() {
    $token = Security::generateCSRFToken();
    return "<input type='hidden' name='csrf_token' value='$token'>";
}

/**
 * Obtener IP del cliente
 */
function getClientIP() {
    return Security::getClientIP();
}

/**
 * Log simple (para debugging)
 */
function logDebug($message, $data = null) {
    $msg = "[" . date('Y-m-d H:i:s') . "] $message";
    if ($data) {
        $msg .= " | " . json_encode($data);
    }
    error_log($msg);
}
?>
