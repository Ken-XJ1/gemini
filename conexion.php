<?php
// conexion.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "electiva_p"; // El nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    // Usar die() detiene la ejecución y muestra un mensaje. Es útil en desarrollo.
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres a utf8mb4 para soportar emojis y caracteres especiales
$conn->set_charset("utf8mb4");

?>