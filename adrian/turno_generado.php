<?php
session_start();
include('conexion.php');

// Verificar si hay un turno generado
if (!isset($_SESSION['numero_turno'])) {
    header("Location: index.php");
    exit;
}

// Obtener datos del turno
$numero_turno = $_SESSION['numero_turno'];
$stmt = $conn->prepare("
    SELECT t.tipo_tramite, s.nombre AS sucursal, t.estado, 
           DATE_FORMAT(t.fecha_turno, '%d/%m/%Y') AS fecha_formateada,
           DATE_FORMAT(t.hora_turno, '%h:%i %p') AS hora_formateada
    FROM turnos t
    INNER JOIN sucursales s ON s.id_sucursal = t.id_sucursal
    WHERE t.numero_turno = ?
");
$stmt->bind_param("i", $numero_turno);
$stmt->execute();
$resultado = $stmt->get_result();
$turno = $resultado->fetch_assoc();
$stmt->close();

// Limpiar la sesión después de mostrar
unset($_SESSION['numero_turno']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turno Generado - Interrapidísimo</title>
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

        .resultado {
            margin-top: 1.5rem;
            padding: 1.5rem;
            background-color: #f9f9f9;
            border-radius: 8px;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        .numero-turno {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-primario);
            margin: 1rem 0;
        }

        .detalle-turno {
            text-align: left;
            margin-top: 1.5rem;
        }

        .detalle-turno p {
            margin: 0.8rem 0;
            font-size: 0.95rem;
        }

        .estado {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
            background-color: #D4EDDA;
            color: #155724;
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
            margin-top: 1.5rem;
        }

        .btn:hover {
            background-color: var(--color-acento);
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .turno-container {
                padding: 1.8rem 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
            
            .numero-turno {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="turno-container">
        <h2>¡Turno Generado!</h2>
        
        <div class="resultado">
            <p>Tu número de turno es:</p>
            <div class="numero-turno"><?= $numero_turno ?></div>
            
            <div class="detalle-turno">
                <p><strong>Tipo de trámite:</strong> <?= ucfirst($turno['tipo_tramite']) ?></p>
                <p><strong>Sucursal:</strong> <?= $turno['sucursal'] ?></p>
                <p><strong>Fecha:</strong> <?= $turno['fecha_formateada'] ?></p>
                <p><strong>Hora:</strong> <?= $turno['hora_formateada'] ?></p>
                <span class="estado">Confirmado</span>
            </div>
        </div>
        
        <button onclick="window.location.href='index.php'" class="btn">Volver al Inicio</button>
    </div>
</body>
</html>