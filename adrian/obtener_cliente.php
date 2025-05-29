<?php
session_start();
require_once 'conexion.php'; // Archivo con la conexión a DB

if (!isset($_SESSION['admin_id'])) {
    die(json_encode(['error' => 'No autorizado']));
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die(json_encode(['error' => 'ID inválido']));
}

$stmt = $conn->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die(json_encode(['error' => 'Cliente no encontrado']));
}

echo json_encode($result->fetch_assoc());
?>