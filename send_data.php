<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = $_POST['service'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $sql = "INSERT INTO solicitudes (servicio, latitud, longitud) VALUES (:service, :latitude, :longitude)";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':service', $service);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);

    if ($stmt->execute()) {
        echo "Solicitud enviada exitosamente.";
    } else {
        echo "Error al enviar la solicitud.";
    }
}
?>
