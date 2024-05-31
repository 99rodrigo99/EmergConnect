<?php
$host = 'localhost';
$db = 'id22230531_gestion_usuarios';
$user = 'id22230531_root';
$password = 'Ro@1075319554';

try {
    $conexion = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
