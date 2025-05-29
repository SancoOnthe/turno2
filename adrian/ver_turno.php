<?php
include('conexion.php');
session_start();

// Verificar si hay sesión activa para autocompletar
$telefono_sesion = isset($_SESSION['telefono']) ? $_SESSION['telefono'] : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Mi Turno - Interrapidísimo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            background: linear-gradient(rgba(247, 247, 247, 0.93), rgba(247, 247, 247, 0.93)), 
                        url('img/int.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
        }

        .consulta-container {
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

        input[type="tel"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 3px rgba(255, 170, 0, 0.15);
            outline: none;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .btn {
            flex: 1;
            padding: 0.9rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--color-primario);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--color-acento);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #f1f1f1;
            color: var(--color-texto);
        }

        .btn-secondary:hover {
            background-color: #e1e1e1;
        }

        .resultado {
            margin-top: 1.5rem;
            padding: 1.2rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            text-align: left;
            animation: fadeIn 0.5s ease;
        }

        .resultado h3 {
            color: var(--color-primario);
            margin-top: 0;
            font-size: 1.2rem;
        }

        .resultado p {
            margin: 0.5rem 0;
            font-size: 0.95rem;
        }

        .estado {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .estado-pendiente {
            background-color: #FFF3CD;
            color: #856404;
        }

        .estado-confirmado {
            background-color: #D4EDDA;
            color: #155724;
        }

        .estado-completado {
            background-color: #D1ECF1;
            color: #0C5460;
        }

        .mensaje-error {
            color: var(--color-error);
            font-size: 0.85rem;
            text-align: center;
            padding: 0.8rem;
            border-radius: 8px;
            background-color: rgba(231, 76, 60, 0.1);
            margin-top: 1rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .consulta-container {
                padding: 1.8rem 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="consulta-container">
        <h2>Ver Mi Turno</h2>
        
        <form method="POST">
            <div class="input-group">
                <label for="telefono">Número de teléfono</label>
                <input type="tel" id="telefono" name="telefono" 
                       placeholder="Ingresa tu número de 10 dígitos"
                       value="<?= htmlspecialchars($telefono_sesion) ?>"
                       maxlength="10" 
                       pattern="[0-9]{10}" 
                       required
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>
            
            <div class="btn-group">
                <button type="submit" name="consultar" class="btn btn-primary">Consultar</button>
                <button type="button" onclick="window.location.href='index.php'" class="btn btn-secondary">Cancelar</button>
            </div>
        </form>
        
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['consultar'])): ?>
            <?php
            $telefono = trim($_POST['telefono']);
            
            if (!preg_match('/^[0-9]{10}$/', $telefono)) {
                echo '<div class="mensaje-error">El número debe contener exactamente 10 dígitos</div>';
            } else {
                $stmt = $conn->prepare("
                    SELECT t.numero_turno, t.tipo_tramite, s.nombre AS sucursal, t.estado, 
                           DATE_FORMAT(t.fecha_turno, '%d/%m/%Y') AS fecha_formateada,
                           DATE_FORMAT(t.hora_turno, '%h:%i %p') AS hora_formateada
                    FROM turnos t
                    INNER JOIN clientes c ON c.id_cliente = t.id_cliente
                    INNER JOIN sucursales s ON s.id_sucursal = t.id_sucursal
                    WHERE c.telefono = ?
                    ORDER BY t.fecha_creacion DESC
                    LIMIT 1
                ");
                $stmt->bind_param("s", $telefono);
                $stmt->execute();
                $resultado = $stmt->get_result();

                if ($resultado->num_rows > 0) {
                    $turno = $resultado->fetch_assoc();
                    $estado_clase = 'estado-' . strtolower($turno['estado']);
                    ?>
                    <div class="resultado">
                     <h3><i class="fas fa-ticket-alt"></i> Turno #<?= $turno['numero_turno'] ?></h3>
                     <p><i class="fas fa-file-alt"></i> <strong>Tipo:</strong> <?= ucfirst($turno['tipo_tramite']) ?></p>
                     <p><i class="fas fa-map-marker-alt"></i> <strong>Sucursal:</strong> <?= $turno['sucursal'] ?></p>
                     <p><i class="fas fa-calendar-alt"></i> <strong>Fecha:</strong> <?= $turno['fecha_formateada'] ?> a las <?= $turno['hora_formateada'] ?></p>
                    <p><i class="fas fa-info-circle"></i> <strong>Estado:</strong> <span class="estado <?= $estado_clase ?>"><?= ucfirst($turno['estado']) ?></span></p>
                   </div>

                    <?php
                } else {
                    echo '<div class="mensaje-error">No se encontraron turnos con este número</div>';
                }
                $stmt->close();
            }
            ?>
        <?php endif; ?>
    </div>
</body>
</html>