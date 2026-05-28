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
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $idPadre = intval($_POST['id_padre'] ?? 0) ?: null;

        if (!$nombre) {
            throw new Exception('El nombre de la categoría es obligatorio.');
        }

        $stmt = $conn->prepare("INSERT INTO categoria_producto (nombre, descripcion, id_padre) VALUES (:nombre, :descripcion, :id_padre)");
        $stmt->execute([
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':id_padre' => $idPadre
        ]);

        Logger::log('crear_usuario', Auth::getCurrentUser()['id'] ?? null, 'admin', "Categoría creada: $nombre");
    } elseif ($action === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("DELETE FROM categoria_producto WHERE id_categoria = :id");
        $stmt->execute([':id' => $id]);
        Logger::log('editar_usuario', Auth::getCurrentUser()['id'] ?? null, 'admin', "Categoría eliminada: $id");
    }
} catch (Exception $e) {
    error_log($e->getMessage());
}

header('Location: /petspa/public/empleado/admin/productos.php');
exit();
