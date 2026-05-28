<?php
/**
 * CONSTANTES GLOBALES DEL SISTEMA PET SPA
 * ==========================================
 */

// ===== CONFIGURACIÓN DE BASE DE DATOS =====
define('DB_HOST', 'localhost:3310');
define('DB_NAME', 'sap_mascotas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8');

// ===== CONFIGURACIÓN DE SEGURIDAD =====
define('BCRYPT_COST', 12); // Nivel de encriptación BCrypt (10-12 recomendado)
define('TOKEN_EXPIRY', 15 * 60); // Token de activación válido 15 minutos
define('SESSION_TIMEOUT', 30 * 60); // Sesión expira en 30 minutos sin actividad
define('MAX_LOGIN_ATTEMPTS', 5); // Máximo de intentos fallidos
define('LOCKOUT_DURATION', 15 * 60); // Bloqueo por 15 minutos tras 5 intentos

// ===== CONTRASEÑA MÍNIMOS REQUERIMIENTOS =====
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SYMBOLS', true);

// ===== ROLES DEL SISTEMA =====
define('ROLE_ADMIN', 'admin');
define('ROLE_GROOMER', 'groomer');
define('ROLE_RECEPCION', 'recepcion');
define('ROLE_CLIENTE', 'cliente');

// ===== ESTADOS DE USUARIO =====
define('USER_STATUS_ACTIVE', true);
define('USER_STATUS_INACTIVE', false);
define('USER_STATUS_PENDING', 'pending'); // Esperando verificación de email

// ===== RUTAS IMPORTANTES =====
define('BASE_PATH', dirname(dirname(__FILE__)));
define('APP_URL', 'http://localhost/petspa');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('API_PATH', BASE_PATH . '/api');
define('LOGS_PATH', BASE_PATH . '/logs');
define('TEMPLATES_PATH', BASE_PATH . '/templates');

// ===== CONFIGURACIÓN 2FA =====
define('TWO_FA_ENABLED_FOR_ADMIN', true);
define('TWO_FA_ISSUER_NAME', 'Pet Spa');

// ===== CONFIGURACIÓN DE EMAIL =====
define('MAIL_FROM_ADDRESS', 'noreply@petspa.com');
define('MAIL_FROM_NAME', 'Pet Spa - Sistema de Gestión');

// CONFIGURACIÓN PARA PRUEBAS CON MAILTRAP (GRATUITO)
// Regístrate en https://mailtrap.io y obtén tus credenciales
define('SMTP_HOST', 'smtp.mailtrap.io');
define('SMTP_PORT', 2525); // Puerto estándar de Mailtrap
define('SMTP_USER', 'cf78c71025179e'); // Usuario de Mailtrap
define('SMTP_PASS', 'c5fd6d08d43d60'); // Password de Mailtrap
//llave de api jey de mailtrap 99431e944555f011e007dce444d49a5b
// ===== CONFIGURACIÓN DE EMAIL =====
//define('MAIL_FROM_ADDRESS', 'noreply@petspa.com');
//define('MAIL_FROM_NAME', 'Pet Spa');
//define('MAILTRAP_API_KEY', '99431e944555f011e007dce444d49a5b'); // tu token real

// ===== CONFIGURACIÓN GOOGLE OAUTH =====
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI'));

// ===== SISTEMA DE LOGS =====
define('LOG_ENABLED', true);
define('LOG_FILE', LOGS_PATH . '/audit.log');
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB, luego rotar

// ===== CONFIGURACIÓN DE PAGINACIÓN =====
define('ITEMS_PER_PAGE', 10);

// ===== CONFIGURACIÓN DE MENSAJES =====
define('SUCCESS_MESSAGE', 'Operación completada exitosamente');
define('ERROR_MESSAGE', 'Ha ocurrido un error, por favor intente nuevamente');

// ===== CONFIGURACIÓN DE TIEMPO =====
date_default_timezone_set('America/La_Paz'); // Cambiar según tu zona horaria

// ===== CONFIGURACIÓN CORS (si es necesario) =====
define('ALLOWED_ORIGINS', ['http://localhost', 'http://localhost:3000']);

// ===== RUTAS DE ACCESO POR ROL =====
$ROLE_ROUTES = [
    ROLE_ADMIN => [
        'dashboard' => '/empleado/admin/dashboard.php',
        'usuarios' => '/empleado/admin/usuarios.php',
        'productos' => '/empleado/admin/productos.php',
        'reportes' => '/empleado/admin/reportes.php',
    ],
    ROLE_GROOMER => [
        'dashboard' => '/empleado/groomer/dashboard.php',
        'citas' => '/empleado/groomer/citas.php',
        'inventario' => '/empleado/groomer/inventario.php',
        'reportes' => '/empleado/groomer/reportes.php',
    ],
    ROLE_RECEPCION => [
        'dashboard' => '/empleado/recepcionista/dashboard.php',
        'inventario' => '/empleado/recepcionista/inventario.php',
        'citas' => '/empleado/recepcionista/citas.php',
        'reportes' => '/empleado/recepcionista/reportes.php',
    ],
    ROLE_CLIENTE => [
        'dashboard' => '/cliente/dashboard.php',
        'tienda' => '/cliente/tienda.php',
        'carrito' => '/cliente/carrito.php',
        'citas' => '/cliente/citas.php',
        'reportes' => '/cliente/reportes.php',
    ]
];

define('ROLE_ROUTES_JSON', json_encode($ROLE_ROUTES));

// ===== TIPOS DE EVENTOS PARA LOGS =====
define('LOG_EVENT_LOGIN', 'login');
define('LOG_EVENT_LOGOUT', 'logout');
define('LOG_EVENT_REGISTER', 'registro');
define('LOG_EVENT_PASSWORD_CHANGE', 'cambio_contraseña');
define('LOG_EVENT_USER_CREATE', 'creacion_usuario');
define('LOG_EVENT_USER_DELETE', 'eliminacion_usuario');
define('LOG_EVENT_USER_UPDATE', 'actualizacion_usuario');
define('LOG_EVENT_ACCESS_DENIED', 'acceso_denegado');
define('LOG_EVENT_FAILED_LOGIN', 'intento_login_fallido');
define('LOG_EVENT_ACCOUNT_LOCKED', 'cuenta_bloqueada');
define('LOG_EVENT_SALE', 'venta');
define('LOG_EVENT_APPOINTMENT', 'cita_agendada');

// ===== MENSAJES DE ERROR PERSONALIZADOS =====
$ERROR_MESSAGES = [
    'email_exists' => 'El correo electrónico ya está registrado',
    'email_invalid' => 'El correo electrónico no es válido',
    'password_weak' => 'La contraseña no cumple con los requisitos de seguridad',
    'password_mismatch' => 'Las contraseñas no coinciden',
    'invalid_credentials' => 'Correo o contraseña incorrectos',
    'account_locked' => 'La cuenta está bloqueada. Intente nuevamente en 15 minutos',
    'account_inactive' => 'La cuenta está inactiva',
    'email_not_verified' => 'El correo no ha sido verificado',
    'token_expired' => 'El token ha expirado',
    'unauthorized' => 'No tiene permiso para acceder a este recurso',
    'user_not_found' => 'Usuario no encontrado',
    'session_expired' => 'Su sesión ha expirado',
    'database_error' => 'Error en la base de datos',
];

define('ERROR_MESSAGES_JSON', json_encode($ERROR_MESSAGES));
?>
