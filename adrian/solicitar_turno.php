<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['id_cliente'])) {
    header("Location: login_cliente.php");
    exit;
}

$id_cliente = $_SESSION['id_cliente'];
$error = '';
$success = '';

// Obtener datos del cliente
$stmt = $conn->prepare("SELECT nombre FROM clientes WHERE id_cliente = ?");
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$stmt->bind_result($nombre_cliente);
$stmt->fetch();
$stmt->close();

// Obtener sucursales
$sucursales = [];
$result = $conn->query("SELECT id_sucursal, nombre FROM sucursales WHERE activo = 1");
while ($row = $result->fetch_assoc()) {
    $sucursales[] = $row;
}

// Solicitar turno
if (isset($_POST['solicitar'])) {
    if (empty($_POST['tipo_tramite']) || empty($_POST['id_sucursal'])) {
        $error = "❌ Por favor selecciona tipo de trámite y sucursal";
    } else {
        $tipo = $_POST['tipo_tramite'];
        $id_sucursal = $_POST['id_sucursal'];
        $hoy = date('Y-m-d');
        $hora_actual = date('H:i:s');

        // Verificar si el cliente ya tiene un turno pendiente hoy
        $stmt = $conn->prepare("SELECT COUNT(*) FROM turnos WHERE id_cliente = ? AND fecha_turno = ? AND estado = 'pendiente'");
        $stmt->bind_param("is", $id_cliente, $hoy);
        $stmt->execute();
        $stmt->bind_result($cantidad_turnos);
        $stmt->fetch();
        $stmt->close();

        if ($cantidad_turnos > 0) {
            $error = "❌ Ya tienes un turno pendiente para hoy.";
        } else {
            // Verificar horario de atención (usando los nombres correctos de columnas)
            $stmt = $conn->prepare("SELECT horario_apertura, horario_cierre FROM sucursales WHERE id_sucursal = ?");
            $stmt->bind_param("i", $id_sucursal);
            $stmt->execute();
            $stmt->bind_result($hora_apertura, $hora_cierre);
            $stmt->fetch();
            $stmt->close();

            if ($hora_actual < $hora_apertura || $hora_actual > $hora_cierre) {
                $error = "❌ No puedes solicitar un turno fuera del horario de atención: " . 
                         date('h:i A', strtotime($hora_apertura)) . " a " . 
                         date('h:i A', strtotime($hora_cierre));
            } else {
                // Verificar capacidad máxima de la sucursal
                $stmt = $conn->prepare("SELECT COUNT(*) FROM turnos WHERE id_sucursal = ? AND fecha_turno = ?");
                $stmt->bind_param("is", $id_sucursal, $hoy);
                $stmt->execute();
                $stmt->bind_result($turnos_hoy);
                $stmt->fetch();
                $stmt->close();

                $stmt = $conn->prepare("SELECT capacidad_maxima FROM sucursales WHERE id_sucursal = ?");
                $stmt->bind_param("i", $id_sucursal);
                $stmt->execute();
                $stmt->bind_result($capacidad_maxima);
                $stmt->fetch();
                $stmt->close();

                if ($turnos_hoy >= $capacidad_maxima) {
                    $error = "❌ Lo sentimos, esta sucursal ha alcanzado su capacidad máxima de turnos para hoy.";
                } else {
                    // Obtener el último número de turno de hoy para la sucursal
                    $stmt = $conn->prepare("SELECT MAX(numero_turno) FROM turnos WHERE fecha_turno = ? AND id_sucursal = ?");
                    $stmt->bind_param("si", $hoy, $id_sucursal);
                    $stmt->execute();
                    $stmt->bind_result($ultimo_turno);
                    $stmt->fetch();
                    $stmt->close();

                    $nuevo_turno = $ultimo_turno ? $ultimo_turno + 1 : 1;

                    // Insertar el nuevo turno
                    $stmt = $conn->prepare("INSERT INTO turnos (id_cliente, tipo_tramite, id_sucursal, numero_turno, fecha_turno, hora_turno) VALUES (?, ?, ?, ?, ?, CURTIME())");
                    $stmt->bind_param("isiss", $id_cliente, $tipo, $id_sucursal, $nuevo_turno, $hoy);

                    if ($stmt->execute()) {
                        $_SESSION['numero_turno'] = $nuevo_turno;
                        header("Location: turno_generado.php");
                        exit;
                    } else {
                        $error = "❌ Error al generar el turno. Por favor intenta nuevamente.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Turno - Interrapidísimo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #FFAA00;
            --color-acento: #FF6B00;
            --color-texto: #343A40;
            --color-borde: #ddd;
            --color-error: #e74c3c;
            --color-exito: #27ae60;
        }

        body {
            background: #f7f7f7;
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .turno-container {
            width: 90%;
            max-width: 450px;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            text-align: center;
        }

        h2 {
            font-size: 1.5rem;
            color: var(--color-texto);
            margin-bottom: 1.8rem;
            font-weight: 600;
        }

        .input-group {
            margin-bottom: 1.2rem;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--color-texto);
            font-weight: 500;
        }

        select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        select:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(255, 170, 0, 0.15);
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 0.9rem;
            background-color: var(--color-primario);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .btn:hover {
            background-color: var(--color-acento);
            transform: translateY(-2px);
        }

        .mensaje-error {
            color: var(--color-error);
            font-size: 0.85rem;
            margin: 1rem 0;
            text-align: center;
            padding: 0.5rem;
            border-radius: 6px;
            background-color: rgba(231, 76, 60, 0.1);
        }

        .back-link {
            display: inline-block;
            margin-top: 1.5rem;
            color: var(--color-primario);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .turno-container {
                padding: 1.8rem 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="turno-container">
        <h2>Solicitar Turno</h2>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label for="tipo_tramite">Tipo de trámite</label>
                <select id="tipo_tramite" name="tipo_tramite" required>
                    <option value="">-- Selecciona --</option>
                    <option value="envío">Envío</option>
                    <option value="retiro">Retiro</option>
                </select>
            </div>
            
            <div class="input-group">
                <label for="id_sucursal">Sucursal</label>
                <select id="id_sucursal" name="id_sucursal" required>
                    <option value="">-- Selecciona --</option>
                    <?php foreach ($sucursales as $sucursal): ?>
                        <option value="<?= htmlspecialchars($sucursal['id_sucursal']) ?>">
                            <?= htmlspecialchars($sucursal['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" name="solicitar" class="btn">Solicitar Turno</button>
        </form>
        
        <a href="index.php" class="back-link">← Volver al inicio</a>
    </div>
</body>
</html>