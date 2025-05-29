<?php
session_start();
include('conexion.php');

if (!isset($_SESSION['admin_id'])) {
    header('Location: login_admin.php'); // Or your admin login page
     exit();
}
 $admin_id = $_SESSION['admin_id']; // For use in procedures like desbloquear_cliente
$admin_usuario = $_SESSION['admin_usuario']; // For display or logging

$page = $_GET['page'] ?? 'dashboard'; // Default page
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1E1E1E;
            color: #EAEAEA;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            width: 250px;
            background-color: #2C2C2C;
            padding: 20px;
            height: 100%;
            box-shadow: 2px 0 5px rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .sidebar h2 {
            color: #FF8C00;
            text-align: center;
            margin-bottom: 30px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
        }

        .sidebar ul li a {
            display: block;
            color: #EAEAEA;
            padding: 12px 15px;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 8px;
            transition: background-color 0.3s, color 0.3s;
        }

        .sidebar ul li a:hover,
        .sidebar ul li a.active {
            background-color: #FF8C00;
            color: #FFFFFF;
        }

        .main-content {
            flex-grow: 1;
            padding: 25px;
            overflow-y: auto;
            height: calc(100vh - 50px); /* Adjust if you have a top bar too */
        }

        .content-header {
            border-bottom: 1px solid #444;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .content-header h1 {
            color: #FF8C00;
            margin: 0;
        }

        /* Generic table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #2C2C2C;
        }

        th, td {
            border: 1px solid #444;
            padding: 12px;
            text-align: left;
            color: #EAEAEA;
        }

        th {
            background-color: #383838;
            color: #FF8C00;
        }

        tr:nth-child(even) {
            background-color: #333333;
        }

        .btn {
            background-color: #FF8C00;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9em;
        }
        .btn:hover {
            background-color: #E07B00;
        }
        .btn-danger {
            background-color: #D32F2F;
        }
        .btn-danger:hover {
            background-color: #B71C1C;
        }

        /* Card styling for dashboard */
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .card {
            background-color: #2C2C2C;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            flex: 1;
            min-width: 200px;
        }
        .card h3 {
            margin-top: 0;
            color: #FF8C00;
        }
        .card p {
            font-size: 1.8em;
            margin-bottom: 0;
            color: #FFFFFF;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="admin_panel.php?page=dashboard" class="<?= $page == 'dashboard' ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="admin_panel.php?page=turnos" class="<?= $page == 'turnos' ? 'active' : '' ?>">Turnos</a></li>
            <li><a href="admin_panel.php?page=clientes" class="<?= $page == 'clientes' ? 'active' : '' ?>">Clientes</a></li>
            <li><a href="admin_panel.php?page=usuarios_bloqueados" class="<?= $page == 'usuarios_bloqueados' ? 'active' : '' ?>">Usuarios Bloqueados</a></li>
            <li><a href="admin_panel.php?page=sucursales" class="<?= $page == 'sucursales' ? 'active' : '' ?>">Sucursales</a></li>
            <li><a href="admin_panel.php?page=empleados" class="<?= $page == 'empleados' ? 'active' : '' ?>">Empleados</a></li>
            <li><a href="admin_panel.php?page=reportes" class="<?= $page == 'reportes' ? 'active' : '' ?>">Reportes</a></li>
            <li><a href="admin_panel.php?page=configuracion" class="<?= $page == 'configuracion' ? 'active' : '' ?>">Configuración</a></li>
            <li><a href="admin_panel.php?page=auditoria" class="<?= $page == 'auditoria' ? 'active' : '' ?>">Auditoría</a></li>
            <li><a href="logout_admin.php">Cerrar Sesión</a></li> </ul>
    </div>

    <div class="main-content">
        <?php
        // Load page content based on $page variable
        // For a real application, you would use a more robust routing system or include files
        if ($page == 'dashboard') {
            include 'admin_dashboard.php';
        } elseif ($page == 'usuarios_bloqueados') {
            include 'admin_usuarios_bloqueados.php';
        } elseif ($page == 'clientes') {
            // Placeholder for clientes page
            echo "<div class='content-header'><h1>Gestión de Clientes</h1></div><p>Contenido de gestión de clientes...</p>";
            // You would use vista_clientes and procedures like registrar_cliente, verificar_cliente.
        } elseif ($page == 'turnos') {
            echo "<div class='content-header'><h1>Gestión de Turnos</h1></div><p>Contenido de gestión de turnos...</p>";
            // You would use vista_turnos_diarios, cambiar_estado_turno, asignar_turno.
        }
        // Add other pages similarly
        // else {
        //     include 'admin_dashboard.php'; // Default or 404 page
        // }
        ?>
    </div>
</body>
</html>