<?php
session_start();
require 'conexion.php';

// Verificar sesión y permisos
if (!isset($_SESSION['id_empleado']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    exit;
}

$id_turno = $_POST['id_turno'] ?? null;
$id_sucursal = $_SESSION['id_sucursal'];

if ($id_turno) {
    // Verificar que el turno pertenece a esta sucursal
    $stmt = $conn->prepare("SELECT numero_turno FROM turnos WHERE id_turno = ? AND id_sucursal = ?");
    $stmt->bind_param("ii", $id_turno, $id_sucursal);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $turno = $resultado->fetch_assoc();
        
        // Aquí iría la lógica para:
        // 1. Mostrar en pantalla grande
        // 2. Reproducir sonido
        // 3. Registrar en bitácora
        
        echo json_encode([
            'success' => true,
            'mensaje' => "Turno #{$turno['numero_turno']} llamado",
            'numero' => $turno['numero_turno']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Turno no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID de turno inválido']);
}
?>