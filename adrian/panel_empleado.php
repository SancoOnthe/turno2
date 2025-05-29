<?php
session_start();
include('conexion.php');

// Verificar sesión de empleado
if (!isset($_SESSION['id_empleado'])) {
    header("Location: login_empleado.php");
    exit;
}

// Obtener ID de sucursal del empleado
$id_sucursal = $_SESSION['id_sucursal'];

// Procesar acciones sobre turnos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && isset($_POST['id_turno'])) {
    $id_turno = $_POST['id_turno'];
    $accion = $_POST['accion'];
    
    // Validar que el turno pertenece a esta sucursal
    $stmt = $conn->prepare("SELECT id_turno FROM turnos WHERE id_turno = ? AND id_sucursal = ?");
    $stmt->bind_param("ii", $id_turno, $id_sucursal);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 1) {
        // Actualizar estado según la acción
        $estado = ($accion === 'atendido') ? 'completado' : 'ausente';
        $update = $conn->prepare("UPDATE turnos SET estado = ?, fecha_atencion = NOW() WHERE id_turno = ?");
        $update->bind_param("si", $estado, $id_turno);
        $update->execute();
    }
    header("Location: panel_empleado.php");
    exit;
}

// Obtener turnos pendientes
$turnos_pendientes = $conn->query("
    SELECT t.*, c.nombre as cliente 
    FROM turnos t
    JOIN clientes c ON t.id_cliente = c.id_cliente
    WHERE t.id_sucursal = $id_sucursal
    AND t.estado = 'pendiente'
    ORDER BY t.fecha_turno, t.hora_turno
");

// Obtener historial de HOY
$historial = $conn->query("
    SELECT t.*, c.nombre as cliente 
    FROM turnos t
    JOIN clientes c ON t.id_cliente = c.id_cliente
    WHERE t.id_sucursal = $id_sucursal
    AND DATE(t.fecha_turno) = CURDATE()
    AND t.estado != 'pendiente'
    ORDER BY t.fecha_atencion DESC
    LIMIT 15
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado - Interrapidísimo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #FFAA00;
            --color-acento: #FF6B00;
            --color-texto: #333;
            --color-fondo: #f5f5f5;
            --color-borde: #e1e1e1;
            --color-pendiente: #FFECB3;
            --color-atendido: #C8E6C9;
            --color-ausente: #FFCDD2;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            background: var(--color-fondo);
            color: var(--color-texto);
        }

        .header-panel {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-weight: 600;
            color: var(--color-primario);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--color-primario);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .tabs-container {
            background: white;
            padding: 0 20px;
            border-bottom: 1px solid var(--color-borde);
            display: flex;
        }

        .tab-btn {
            padding: 12px 20px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .tab-btn.active {
            border-bottom-color: var(--color-primario);
            color: var(--color-primario);
        }

        .tab-btn:hover {
            background: rgba(255, 170, 0, 0.1);
        }

        .main-content {
            padding: 20px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .turno-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 4px solid var(--color-primario);
        }

        .turno-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--color-borde);
        }

        .turno-numero {
            font-weight: 600;
            color: var(--color-primario);
            font-size: 1.1rem;
        }

        .turno-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 0.8rem;
            color: #777;
        }

        .info-value {
            font-weight: 500;
        }

        .acciones {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn-accion {
            padding: 8px 15px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: all 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-llamar {
            background: var(--color-primario);
            color: white;
        }

        .btn-llamar:hover {
            background: var(--color-acento);
            transform: translateY(-1px);
        }

        .btn-atendido {
            background: #28a745;
            color: white;
        }

        .btn-atendido:hover {
            background: #218838;
        }

        .btn-ausente {
            background: #dc3545;
            color: white;
        }

        .btn-ausente:hover {
            background: #c82333;
        }

        .estado {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .estado-pendiente {
            background: var(--color-pendiente);
            color: #E65100;
        }

        .estado-completado {
            background: var(--color-atendido);
            color: #1B5E20;
        }

        .estado-ausente {
            background: var(--color-ausente);
            color: #B71C1C;
        }

        @media (max-width: 768px) {
            .turno-info {
                grid-template-columns: 1fr;
            }
            
            .acciones {
                flex-direction: column;
            }
            
            .btn-accion {
                width: 100%;
                justify-content: center;
            }
            
            .tabs-container {
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header-panel">
        <div class="logo">
            <i class="fas fa-shipping-fast"></i>
            <span>Interrapidísimo</span>
        </div>
        <div class="user-info">
           <!-- En la sección del avatar -->
<div class="user-avatar">
    <?= isset($_SESSION['nombre_empleado']) ? strtoupper(substr($_SESSION['nombre_empleado'], 0, 1)) : 'U' ?>
</div>

<!-- En la sección del nombre -->
<span><?= isset($_SESSION['nombre_empleado']) ? htmlspecialchars($_SESSION['nombre_empleado']) : 'Usuario' ?></span>
        </div>
    </div>

    <!-- Pestañas -->
    <div class="tabs-container">
        <button class="tab-btn active" onclick="mostrarSeccion('atencion')">
            <i class="fas fa-user-clock"></i> Atención de Turnos
        </button>
        <button class="tab-btn" onclick="mostrarSeccion('historial')">
            <i class="fas fa-history"></i> Historial
        </button>
        <a href="logout_empleado.php" class="tab-btn">
            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
        </a>
    </div>

    <!-- Contenido principal -->
    <div class="main-content">
        <!-- Sección de Atención -->
        <div id="atencion" class="tab-content active">
            <h2><i class="fas fa-list"></i> Turnos Pendientes</h2>
            
            <?php if ($turnos_pendientes->num_rows > 0): ?>
                <?php while($turno = $turnos_pendientes->fetch_assoc()): ?>
                    <div class="turno-card">
                        <div class="turno-header">
                            <div class="turno-numero">
                                <i class="fas fa-ticket-alt"></i> Turno #<?= $turno['numero_turno'] ?>
                            </div>
                            <span class="estado estado-pendiente">Pendiente</span>
                        </div>
                        
                        <div class="turno-info">
                            <div>
                                <span class="info-label">Cliente:</span>
                                <span class="info-value"><?= htmlspecialchars($turno['cliente']) ?></span>
                            </div>
                            <div>
                                <span class="info-label">Trámite:</span>
                                <span class="info-value"><?= ucfirst($turno['tipo_tramite']) ?></span>
                            </div>
                            <div>
                                <span class="info-label">Hora:</span>
                                <span class="info-value"><?= date('h:i A', strtotime($turno['hora_turno'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="acciones">
                            <button class="btn-accion btn-llamar" onclick="llamarTurno(<?= $turno['id_turno'] ?>, <?= $turno['numero_turno'] ?>)">
                                <i class="fas fa-bell"></i> Llamar
                            </button>
                            
                            <form method="POST">
                                <input type="hidden" name="id_turno" value="<?= $turno['id_turno'] ?>">
                                <button type="submit" name="accion" value="atendido" class="btn-accion btn-atendido">
                                    <i class="fas fa-check"></i> Atendido
                                </button>
                                <button type="submit" name="accion" value="ausente" class="btn-accion btn-ausente">
                                    <i class="fas fa-times"></i> Ausente
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="turno-card">
                    <p>No hay turnos pendientes en este momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sección de Historial -->
        <div id="historial" class="tab-content">
            <h2><i class="fas fa-history"></i> Historial de Hoy</h2>
            
            <?php if ($historial->num_rows > 0): ?>
                <?php while($item = $historial->fetch_assoc()): ?>
                    <div class="turno-card">
                        <div class="turno-header">
                            <div class="turno-numero">
                                <i class="fas fa-ticket-alt"></i> Turno #<?= $item['numero_turno'] ?>
                            </div>
                            <span class="estado estado-<?= strtolower($item['estado']) ?>">
                                <?= ucfirst($item['estado']) ?>
                            </span>
                        </div>
                        
                        <div class="turno-info">
                            <div>
                                <span class="info-label">Cliente:</span>
                                <span class="info-value"><?= htmlspecialchars($item['cliente']) ?></span>
                            </div>
                            <div>
                                <span class="info-label">Trámite:</span>
                                <span class="info-value"><?= ucfirst($item['tipo_tramite']) ?></span>
                            </div>
                            <div>
                                <span class="info-label">Hora atención:</span>
                                <span class="info-value">
                                    <?= $item['fecha_atencion'] ? date('h:i A', strtotime($item['fecha_atencion'])) : '--' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="turno-card">
                    <p>No hay registros en el historial de hoy.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Función para mostrar secciones
        function mostrarSeccion(seccionId) {
            // Ocultar todas las secciones
            document.querySelectorAll('.tab-content').forEach(seccion => {
                seccion.classList.remove('active');
            });
            
            // Desactivar todos los botones
            document.querySelectorAll('.tab-btn').forEach(boton => {
                boton.classList.remove('active');
            });
            
            // Mostrar sección seleccionada
            document.getElementById(seccionId).classList.add('active');
            
            // Activar botón seleccionado
            event.currentTarget.classList.add('active');
        }
        
        // Función para llamar turnos
        function llamarTurno(idTurno, numeroTurno) {
            // Implementación básica - puedes mejorarla con AJAX
            alert(`Llamando al turno #${numeroTurno}`);
            
            // Opcional: Reproducir sonido
            // const audio = new Audio('sound/llamado.mp3');
            // audio.play();
            
            // Opcional: Mostrar en pantalla grande
            // mostrarPantallaGrande(numeroTurno);
        }
    </script>
</body>
</html>