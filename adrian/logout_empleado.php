<?php
session_start();

// Destruir solo las variables de sesión de empleado
unset($_SESSION['id_empleado']);
unset($_SESSION['nombre_empleado']);
unset($_SESSION['id_sucursal']);

// Redirigir al login de empleados
header("Location: login_empleado.php");
exit;
?>