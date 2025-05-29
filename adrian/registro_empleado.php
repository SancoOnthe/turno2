<?php
require 'conexion.php';

$error = '';
$exito = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];
    $id_sucursal = $_POST['id_sucursal'];

    // Validaciones
    if (empty($nombre) || empty($correo) || empty($contrasena)) {
        $error = "Todos los campos son obligatorios";
    } elseif ($contrasena !== $confirmar_contrasena) {
        $error = "Las contraseñas no coinciden";
    } elseif (strlen($contrasena) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT id_empleado FROM empleados WHERE correo = ?");
        $stmt->bind_param("s", $correo);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = "Este correo ya está registrado";
        } else {
            // Hash de la contraseña
            $hash_contrasena = password_hash($contrasena, PASSWORD_DEFAULT);
            
            // Insertar nuevo empleado
            $stmt = $conn->prepare("INSERT INTO empleados (nombre, correo, contrasena, id_sucursal) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $nombre, $correo, $hash_contrasena, $id_sucursal);
            
            if ($stmt->execute()) {
                $exito = "Empleado registrado exitosamente";
            } else {
                $error = "Error al registrar el empleado: " . $conn->error;
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Empleados - Interrapidísimo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-primario: #FFAA00;
            --color-acento:rgb(21, 20, 19);
            --color-texto: #343A40;
            --color-borde: #ddd;
            --color-error: #e74c3c;
            --color-exito: #27ae60;
            --color-advertencia: #f39c12;
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

        .register-container {
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        h2 {
            font-size: 1.8rem;
            color: var(--color-acento);
            margin-bottom: 1.8rem;
            font-weight: 600;
            text-align: center;
        }

        .input-group {
            margin-bottom: 1.2rem;
            text-align: left;
        }

        .input-group.full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--color-texto);
            font-weight: 500;
        }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        input:focus, select:focus {
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
            line-height: 1.4;
            padding: 0.8rem;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--color-error);
            text-align: center;
        }

        .mensaje-exito {
            color: var(--color-exito);
            background-color: rgba(39, 174, 96, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid var(--color-exito);
            text-align: center;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
            color: var(--color-texto);
        }

        .login-link a {
            color: var(--color-primario);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            text-decoration: underline;
            color: var(--color-acento);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.8rem 1.5rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Registro de Empleados</h2>

        <?php if ($error): ?>
            <div class="mensaje-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($exito): ?>
            <div class="mensaje-exito"><?php echo $exito; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-grid">
            <div class="input-group full">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <div class="input-group">
                <label for="correo">Correo Electrónico</label>
                <input type="email" id="correo" name="correo" required>
            </div>

            <div class="input-group">
                <label for="id_sucursal">Sucursal</label>
                <select id="id_sucursal" name="id_sucursal" required>
                    <option value="">Seleccione una sucursal</option>
                    <?php
                    $sucursales = $conn->query("SELECT id_sucursal, nombre FROM sucursales");
                    while ($sucursal = $sucursales->fetch_assoc()) {
                        echo "<option value='{$sucursal['id_sucursal']}'>{$sucursal['nombre']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="input-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
            </div>

            <div class="input-group">
                <label for="confirmar_contrasena">Confirmar Contraseña</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
            </div>

            <div class="input-group full">
                <button type="submit" class="btn">Registrar Empleado</button>
            </div>
        </form>

        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login_empleado.php">Inicia sesión</a>
        </div>
    </div>
</body>
</html>