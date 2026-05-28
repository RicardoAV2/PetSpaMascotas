<?php
require_once "../../config/database.php";
require_once "../../core/Auth.php";
require_once "../../core/middleware.php";
require_once "../../core/Logger.php";

session_start();
Auth::setConnection($conn);
Middleware::requireAdmin();

$action = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['action'] ?? 'create') : ($_GET['action'] ?? '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'create';

        if ($action === 'create') {
            $productoId = intval($_POST['producto_id'] ?? 0);
            $varianteId = intval($_POST['variante_id'] ?? 0) ?: null;
            $cantidadFisica = intval($_POST['cantidad_fisica'] ?? 0);
            $cantidadReservada = intval($_POST['cantidad_reservada'] ?? 0);
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            $vencimiento = trim($_POST['fecha_vencimiento'] ?? '');

            if (!$productoId || $cantidadFisica < 0) {
                throw new Exception('Producto e inventario válidos son obligatorios.');
            }

            $stmt = $conn->prepare("INSERT INTO inventario (id_producto, id_variante, cantidad_fisica, cantidad_reservada, ubicacion, fecha_vencimiento) VALUES (:producto, :variante, :fisica, :reservada, :ubicacion, :vencimiento)");
            $stmt->execute([
                ':producto' => $productoId,
                ':variante' => $varianteId,
                ':fisica' => $cantidadFisica,
                ':reservada' => $cantidadReservada,
                ':ubicacion' => $ubicacion,
                ':vencimiento' => $vencimiento ?: null
            ]);

            $idInventario = $conn->lastInsertId();
            $stmtMov = $conn->prepare("INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues, cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, motivo, id_usuario_registra) VALUES (:inv, 'entrada_ajuste', :cantidad, 0, :fisica, 0, :reservada, 'ajuste', NULL, 'Creación de lote de inventario', :user)");
            $stmtMov->execute([
                ':inv' => $idInventario,
                ':cantidad' => $cantidadFisica,
                ':fisica' => $cantidadFisica,
                ':reservada' => $cantidadReservada,
                ':user' => Auth::getCurrentUser()['id'] ?? null
            ]);
        } elseif ($action === 'update') {
            $idInventario = intval($_POST['id_inventario'] ?? 0);
            $cantidadFisica = intval($_POST['cantidad_fisica'] ?? 0);
            $cantidadReservada = intval($_POST['cantidad_reservada'] ?? 0);
            $ubicacion = trim($_POST['ubicacion'] ?? '');
            $vencimiento = trim($_POST['fecha_vencimiento'] ?? '');

            $stmt = $conn->prepare("SELECT cantidad_fisica, cantidad_reservada FROM inventario WHERE id_inventario = :id");
            $stmt->execute([':id' => $idInventario]);
            $inventario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inventario) {
                throw new Exception('Inventario no encontrado.');
            }

            $antesFisica = intval($inventario['cantidad_fisica']);
            $antesReservada = intval($inventario['cantidad_reservada']);

            $stmt = $conn->prepare("UPDATE inventario SET cantidad_fisica = :fisica, cantidad_reservada = :reservada, ubicacion = :ubicacion, fecha_vencimiento = :vencimiento WHERE id_inventario = :id");
            $stmt->execute([
                ':fisica' => $cantidadFisica,
                ':reservada' => $cantidadReservada,
                ':ubicacion' => $ubicacion,
                ':vencimiento' => $vencimiento ?: null,
                ':id' => $idInventario
            ]);

            if ($cantidadFisica !== $antesFisica || $cantidadReservada !== $antesReservada) {
                $stmtMov = $conn->prepare("INSERT INTO movimiento_inventario (id_inventario, tipo_movimiento, cantidad, cantidad_fisica_antes, cantidad_fisica_despues, cantidad_reservada_antes, cantidad_reservada_despues, referencia_tipo, referencia_id, motivo, id_usuario_registra) VALUES (:inv, 'ajuste', :cantidad, :antesFisica, :despuesFisica, :antesReservada, :despuesReservada, 'ajuste', NULL, :motivo, :user)");
                $stmtMov->execute([
                    ':inv' => $idInventario,
                    ':cantidad' => abs($cantidadFisica - $antesFisica),
                    ':antesFisica' => $antesFisica,
                    ':despuesFisica' => $cantidadFisica,
                    ':antesReservada' => $antesReservada,
                    ':despuesReservada' => $cantidadReservada,
                    ':motivo' => 'Actualización de inventario',
                    ':user' => Auth::getCurrentUser()['id'] ?? null
                ]);
            }
        }
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $idInventario = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM inventario WHERE id_inventario = :id");
        $stmt->execute([':id' => $idInventario]);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

header('Location: /petspa/public/empleado/admin/productos.php');
exit();
