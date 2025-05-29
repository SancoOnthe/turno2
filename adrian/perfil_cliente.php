<?php
session_start();

// Validar que el usuario esté logueado
if (!isset($_SESSION['id'])) {
    header("Location: login_cliente.php");
    exit();
}

include("conexion.php"); // Asegúrate de que este archivo define correctamente $conexion

$id_usuario = $_SESSION['id'];
$mensaje_exito = "";

// Obtener datos del usuario
$query = $conexion->prepare("SELECT nombre, telefono FROM clientes WHERE id = ?");
$query->bind_param("i", $id_usuario);
$query->execute();
$query->bind_result($nombre, $telefono);
$query->fetch();
$query->close();

// Si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_nombre = $_POST['nombre'];
    $nuevo_telefono = $_POST['telefono'];

    $actualizar = $conexion->prepare("UPDATE clientes SET nombre = ?, telefono = ? WHERE id = ?");
    $actualizar->bind_param("ssi", $nuevo_nombre, $nuevo_telefono, $id_usuario);
    $actualizar->execute();
    $actualizar->close();

    // Actualizar variables para mostrar en el formulario
    $nombre = $nuevo_nombre;
    $telefono = $nuevo_telefono;
    $mensaje_exito = "Perfil actualizado correctamente.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil Cliente</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f8f9fa;
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 60px;
            background: #FF9500;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 30px;
        }

        .sidebar i {
            font-size: 20px;
            color: white;
            margin-bottom: 30px;
            cursor: pointer;
        }

        .content {
            flex: 1;
            padding: 40px;
        }

        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
        }

        label {
            font-weight: 600;
            display: block;
            margin-top: 20px;
        }

        input[type="text"], input[type="tel"] {
            width: 100%;
            padding: 12px;
            margin-top: 5px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .btn-guardar {
            background: #FF9500;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            margin-top: 30px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-guardar:hover {
            background: #e88900;
        }

        .volver {
            margin-bottom: 20px;
            display: inline-block;
            color: #212529;
            text-decoration: none;
            font-weight: 600;
        }

        .mensaje-exito {
            color: green;
            margin-top: 20px;
            font-weight: 600;
        }
    </style>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

    <div class="sidebar">
        <i class="fas fa-user"></i>
    </div>

    <div class="content">
        <a class="volver" href="index.php">← Volver</a>

        <div class="card">
            <h2>Perfil</h2>

            <?php if ($mensaje_exito): ?>
                <div class="mensaje-exito"><?php echo $mensaje_exito; ?></div>
            <?php endif; ?>

            <form method="post">
                <label for="nombre">Nombre:</label>
                <input type="text" name="nombre" id="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>

                <label for="telefono">Teléfono:</label>
                <input type="tel" name="telefono" id="telefono" value="<?php echo htmlspecialchars($telefono); ?>" required>

                <button class="btn-guardar" type="submit">Guardar</button>
            </form>
        </div>
    </div>

</body>
</html>
