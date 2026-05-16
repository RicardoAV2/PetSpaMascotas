<?php
require_once "../../config/database.php";
require_once "../../config/constants.php";
require_once "../../core/Auth.php";
require_once "../../core/Security.php";
require_once "../../core/Logger.php";

Auth::setConnection($conn);

$clientId = GOOGLE_CLIENT_ID;
$clientSecret = GOOGLE_CLIENT_SECRET;
$redirectUri = GOOGLE_REDIRECT_URI;
$scope = 'openid email profile';

if (empty($clientId) || empty($clientSecret)) {
    die('Google OAuth no está configurado correctamente. Configure GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en config/constants.php');
}

if (!empty($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
    header('Location: /petspa/public/login.php?google_error=' . urlencode($error));
    exit;
}

if (empty($_GET['code'])) {
    $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth';
    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => $scope,
        'access_type' => 'offline',
        'prompt' => 'select_account'
    ];
    header('Location: ' . $authUrl . '?' . http_build_query($params));
    exit;
}

$code = $_GET['code'];

$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    die('Error de conexión con Google: ' . htmlspecialchars($curlError));
}

$tokenData = json_decode($response, true);
if (empty($tokenData['access_token'])) {
    die('Error obteniendo token de Google: ' . htmlspecialchars($response));
}

$accessToken = $tokenData['access_token'];
$userInfoUrl = 'https://openidconnect.googleapis.com/v1/userinfo';

$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$accessToken}"]);
$userResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($userResponse === false) {
    die('Error obteniendo datos de usuario de Google: ' . htmlspecialchars($curlError));
}

$userData = json_decode($userResponse, true);
$email = $userData['email'] ?? '';
$firstName = $userData['given_name'] ?? ($userData['name'] ?? '');
$lastName = $userData['family_name'] ?? '';

if (empty($email)) {
    die('Google no devolvió un correo válido.');
}

try {
    $stmt = $conn->prepare("SELECT u.id_usuario, u.email, u.nombre, u.apellido, u.telefono, u.password_hash, r.nombre AS rol_nombre FROM usuario u JOIN rol r ON u.id_rol = r.id_rol WHERE u.email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $stmt = $conn->prepare("INSERT INTO usuario (email, password_hash, nombre, apellido, telefono, id_rol, estado) VALUES (?, ?, ?, ?, '', (SELECT id_rol FROM rol WHERE nombre = ?), ?)");
        $stmt->execute([$email, '', $firstName, $lastName, ROLE_CLIENTE, USER_STATUS_ACTIVE]);
        $userId = $conn->lastInsertId();

        $stmt = $conn->prepare("INSERT INTO cliente (id_cliente, direccion, ci) VALUES (?, '', '')");
        $stmt->execute([$userId]);

        $user = [
            'id_usuario' => $userId,
            'email' => $email,
            'nombre' => $firstName,
            'apellido' => $lastName,
            'telefono' => '',
            'rol_nombre' => ROLE_CLIENTE
        ];

        Logger::logRegister($userId, $email, ROLE_CLIENTE);
    } else {
        if (empty($user['nombre']) && !empty($firstName)) {
            $stmt = $conn->prepare('UPDATE usuario SET nombre = ? WHERE id_usuario = ?');
            $stmt->execute([$firstName, $user['id_usuario']]);
            $user['nombre'] = $firstName;
        }
        if (empty($user['apellido']) && !empty($lastName)) {
            $stmt = $conn->prepare('UPDATE usuario SET apellido = ? WHERE id_usuario = ?');
            $stmt->execute([$lastName, $user['id_usuario']]);
            $user['apellido'] = $lastName;
        }
    }

    Auth::loginUser($user);

    $redirectPage = '/petspa/public/cliente/dashboard.php';
    if ($user['rol_nombre'] === ROLE_ADMIN) {
        $redirectPage = '/petspa/public/empleado/admin/dashboard.php';
    } elseif ($user['rol_nombre'] === ROLE_RECEPCION) {
        $redirectPage = '/petspa/public/empleado/recepcionista/dashboard.php';
    } elseif ($user['rol_nombre'] === ROLE_GROOMER) {
        $redirectPage = '/petspa/public/empleado/groomer/dashboard.php';
    }

    if ($user['rol_nombre'] === ROLE_CLIENTE) {
        $stmt = $conn->prepare('SELECT direccion, ci FROM cliente WHERE id_cliente = ?');
        $stmt->execute([$user['id_usuario']]);
        $clientData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$clientData || empty($clientData['direccion']) || empty($clientData['ci']) || empty($user['telefono'])) {
            $redirectPage = '/petspa/public/google_complete.php';
        }
    }

    header('Location: ' . $redirectPage);
    exit;
} catch (Exception $e) {
    error_log('Error en Google OAuth: ' . $e->getMessage());
    die('Ocurrió un error al procesar tu inicio de sesión con Google.');
}
?>