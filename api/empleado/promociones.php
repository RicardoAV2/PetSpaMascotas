<?php
require_once "../../config/database.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/helpers.php";
require_once "../../core/Logger.php";

session_start();
Auth::setConnection($conn);
Middleware::requireAuth();
Middleware::requireRole([ROLE_ADMIN, ROLE_RECEPCION]);
Middleware::checkSessionTimeout();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método inválido.');
    }

    $mensaje = trim($_POST['mensaje'] ?? '');
    $canal = trim($_POST['canal'] ?? 'email');
    $programarFecha = trim($_POST['programar_fecha'] ?? '');
    $enviarTodos = isset($_POST['enviar_todos']);
    $clienteId = intval($_POST['cliente_id'] ?? 0);

    if ($mensaje === '') {
        throw new Exception('El mensaje de la promoción es obligatorio.');
    }

    $allowedChannels = ['email', 'whatsapp', 'sms'];
    if (!in_array($canal, $allowedChannels, true)) {
        $canal = 'email';
    }

    if ($programarFecha !== '') {
        $fechaProgramacion = new DateTime($programarFecha);
        if ($fechaProgramacion < new DateTime()) {
            throw new Exception('La fecha de programación debe ser futura.');
        }
        $fechaProgramacion = $fechaProgramacion->format('Y-m-d H:i:s');
    } else {
        $fechaProgramacion = date('Y-m-d H:i:s');
    }

    $insertStmt = $conn->prepare(
        "INSERT INTO notificacion (tipo_evento, canal, mensaje, destino, fecha_programacion, estado_envio, id_cliente) VALUES ('promocion', :canal, :mensaje, :destino, :fecha, 'pendiente', :cliente)"
    );

    $created = 0;
    if ($enviarTodos) {
        $stmt = $conn->prepare(
            "SELECT cl.id_cliente, u.email, u.telefono, cl.recibe_promociones
                FROM cliente cl
                JOIN usuario u ON cl.id_cliente = u.id_usuario
                WHERE cl.recibe_promociones = 1"
        );
        $stmt->execute();
        $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($clientes as $cliente) {
            $destino = $canal === 'whatsapp' ? $cliente['telefono'] : $cliente['email'];
            if (!$destino) {
                continue;
            }
            $insertStmt->execute([
                ':canal' => $canal,
                ':mensaje' => $mensaje,
                ':destino' => $destino,
                ':fecha' => $fechaProgramacion,
                ':cliente' => $cliente['id_cliente']
            ]);
            $created++;
        }
        if ($created === 0) {
            throw new Exception('No se encontró ningún cliente suscrito con información de contacto válida.');
        }
    } else {
        if ($clienteId <= 0) {
            throw new Exception('Selecciona un cliente o marca "Enviar a todos" para continuar.');
        }
        $stmt = $conn->prepare(
            "SELECT u.email, u.telefono FROM cliente cl JOIN usuario u ON cl.id_cliente = u.id_usuario WHERE cl.id_cliente = ?"
        );
        $stmt->execute([$clienteId]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$cliente) {
            throw new Exception('Cliente no encontrado.');
        }
        $destino = $canal === 'whatsapp' ? $cliente['telefono'] : $cliente['email'];
        if (!$destino) {
            throw new Exception('El cliente seleccionado no tiene el canal de contacto configurado para este tipo de notificación.');
        }
        $insertStmt->execute([
            ':canal' => $canal,
            ':mensaje' => $mensaje,
            ':destino' => $destino,
            ':fecha' => $fechaProgramacion,
            ':cliente' => $clienteId
        ]);
        $created = 1;
    }

    Logger::log('crear_promocion', Auth::getCurrentUser()['id'], Auth::getCurrentUser()['rol'], "Promoción registrada: {$created} destinatarios.");
    header('Location: /petspa/public/empleado/promociones.php?success=1');
    exit();
} catch (Exception $e) {
    header('Location: /petspa/public/empleado/promociones.php?error=' . urlencode($e->getMessage()));
    exit();
}
