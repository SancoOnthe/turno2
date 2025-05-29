<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['id_empleado']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

$id_turno = $_POST['id_turno'] ?? null;
$id_sucursal = $_SESSION['id_sucursal'];

if ($id_turno) {
    $stmt = $conn->prepare("UPDATE turnos SET estado = 'completado', fecha_atencion = NOW() WHERE id_turno = ? AND id_sucursal = ?");
    $stmt->bind_param("ii", $id_turno, $id_sucursal);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
}
?>