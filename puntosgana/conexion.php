<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "puntosgana_quibdo"; // nombre exacto de tu base de datos

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>