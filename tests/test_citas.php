<?php
/**
 * Pruebas básicas para flujo de citas (no ejecuta servidor HTTP)
 * Ejecutar: php tests/test_citas.php
 */
require_once __DIR__ . '/../config/database.php';

function logit($m){ echo '['.date('H:i:s').'] '.$m.PHP_EOL; }

try {
    // Buscar un cliente, mascota, servicio y groomer existentes
    $cliente = $conn->query("SELECT id_usuario FROM usuario WHERE id_rol = (SELECT id_rol FROM rol WHERE nombre='cliente') LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $mascota = $conn->query("SELECT id_mascota FROM mascota LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $servicio = $conn->query("SELECT id_servicio FROM servicio WHERE estado_activo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $groomer = $conn->query("SELECT id_groomer FROM groomer WHERE estado_activo = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$cliente || !$mascota || !$servicio || !$groomer) {
        logit('Faltan datos en la BD para ejecutar las pruebas (cliente/mascota/servicio/groomer).');
        exit(1);
    }

    $fecha = date('Y-m-d', strtotime('+2 days'));
    $hora = '09:00';
    $inicio = "$fecha $hora:00";
    $duracion = 60;
    $fin = date('Y-m-d H:i:s', strtotime($inicio) + $duracion*60);

    // Insertar cita
    $stmt = $conn->prepare("INSERT INTO cita (fecha_inicio, fecha_fin, duracion_real, estado, creado_por, fecha_creacion, id_mascota, id_groomer, id_servicio) VALUES (?, ?, ?, 'agendada', ?, NOW(), ?, ?, ?)");
    $stmt->execute([$inicio, $fin, $duracion, $cliente['id_usuario'], $mascota['id_mascota'], $groomer['id_groomer'], $servicio['id_servicio']]);
    $citaId = $conn->lastInsertId();
    logit("Cita creada ID: $citaId");

    // Reprogramar cita al día siguiente
    $newFecha = date('Y-m-d', strtotime('+3 days'));
    $newInicio = "$newFecha 10:00:00";
    $newFin = date('Y-m-d H:i:s', strtotime($newInicio) + $duracion*60);
    $stmt = $conn->prepare("UPDATE cita SET fecha_inicio = ?, fecha_fin = ?, fecha_reprogramacion = NOW() WHERE id_cita = ?");
    $stmt->execute([$newInicio, $newFin, $citaId]);
    logit("Cita reprogramada a $newInicio");

    // Borrar la cita de prueba
    $stmt = $conn->prepare("DELETE FROM cita WHERE id_cita = ?");
    $stmt->execute([$citaId]);
    logit('Cita eliminada (prueba limpia).');
    logit('Pruebas completadas.');
} catch (Exception $e) {
    logit('Error en pruebas: ' . $e->getMessage());
    exit(1);
}

