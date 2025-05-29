<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite peticiones desde cualquier origen

include('conexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido, usa POST']);
    exit();
}

// Recibir datos en JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validaciones básicas
$nombre = $input['nombre_cliente'] ?? null;
$cedula = $input['cedula'] ?? null;
$fecha = $input['fecha'] ?? null;      // formato: 'YYYY-MM-DD'
$hora = $input['hora'] ?? null;        // formato: 'HH:MM'
$sucursal = $input['sucursal'] ?? 'Quibdó';

if (!$nombre || !$cedula || !$fecha || !$hora) {
    http_response_code(400);
    echo json_encode(['error' => 'Faltan datos obligatorios']);
    exit();
}

// (Opcional) Verifica si ya existe un turno igual para esa persona
$sql_check = "SELECT id FROM turnos WHERE cedula = ? AND fecha = ? AND hora = ?";
$stmt = $conn->prepare($sql_check);
$stmt->bind_param("sss", $cedula, $fecha, $hora);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['error' => 'Ya tienes un turno reservado para esa hora.']);
    exit();
}
$stmt->close();

// Insertar el turno
$sql = "INSERT INTO turnos (nombre_cliente, cedula, sucursal, fecha, hora, estado) VALUES (?, ?, ?, ?, ?, 'pendiente')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $nombre, $cedula, $sucursal, $fecha, $hora);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'mensaje' => 'Turno reservado correctamente',
        'turno_id' => $stmt->insert_id
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo reservar el turno']);
}
$stmt->close();
$conn->close();
?>