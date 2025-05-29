<?php
$servername = "localhost";
$username = "root"; // Usuario común en XAMPP
$password = ""; // Contraseña vacía por defecto en XAMPP
$dbname = "turnos_envio"; // Nombre exacto de tu BD

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer charset (importante para caracteres especiales)
$conn->set_charset("utf8mb4");
?>