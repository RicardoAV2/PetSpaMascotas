<?php
$host = "localhost:3310";
$dbname = "sap_mascotas"; // tu base de datos
$user = "root";
$password = ""; // por defecto en XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>