<?php
require_once('conexion.php');
session_start();

// Mensajes de retroalimentación
if (isset($_SESSION['mensaje_login'])) {
    echo "<div class='mensaje-exito'>".$_SESSION['mensaje_login']."</div>";
    unset($_SESSION['mensaje_login']);
}

$error_message = ""; // Inicializamos la variable de error

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['iniciar_sesion'])) {
    // Verificamos que los campos existan y no estén vacíos
    if (empty($_POST['correo'])) {
        $error_message = "❌ Por favor ingresa tu correo electrónico";
    } elseif (empty($_POST['contrasena'])) {
        $error_message = "❌ Por favor ingresa tu contraseña";
    } else {
        $correo = trim($_POST['correo']);
        $contrasena = trim($_POST['contrasena']);

        try {
            $stmt = $conn->prepare("SELECT * FROM empleados WHERE correo = ? AND activo = 1");
            $stmt->bind_param("s", $correo);
            $stmt->execute();
            $resultado = $stmt->get_result();

            if ($resultado->num_rows > 0) {
                $empleado = $resultado->fetch_assoc();
                
                if (password_verify($contrasena, $empleado['contrasena'])) {
                    $_SESSION['id_empleado'] = $empleado['id_empleado'];
                    $_SESSION['correo'] = $empleado['correo'];
                    $_SESSION['nombre'] = $empleado['nombre'];
                    $_SESSION['apellido'] = $empleado['apellido'];
                    $_SESSION['rol'] = $empleado['rol'];
                    $_SESSION['id_sucursal'] = $empleado['id_sucursal'];
                    
                    header("Location: panel_empleado.php");
                    exit();
                } else {
                    $error_message = "❌ Contraseña incorrecta";
                }
            } else {
                $error_message = "❌ Correo no registrado o cuenta inactiva";
            }
        } catch (Exception $e) {
            $error_message = "Error en el sistema: ".$e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login Empleados - Interrapidísimo</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primario: #FFAA00;
      --color-acento: #FF7900;
      --color-texto: #343A40;
      --color-error: #e74c3c;
      --color-exito: #27ae60;
      --color-borde: #ddd;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.95)), 
                  url('img/int.jpg') no-repeat center center;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .login-box {
      background-color: #fff;
      width: 100%;
      max-width: 380px;
      padding: 2.5rem;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1.2rem;
      text-align: center;
    }

    h1 {
      color: var(--color-texto);
      font-size: 1.5rem;
      font-weight: 600;
    }

    .subtitulo {
      color: #666;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .input-group {
      width: 100%;
      text-align: left;
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
      color: var(--color-texto);
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid var(--color-borde);
      border-radius: 10px;
      font-size: 1rem;
      transition: 0.3s;
    }

    input[type="email"]:focus,
    input[type="password"]:focus {
      border-color: var(--color-primario);
      outline: none;
      box-shadow: 0 0 0 3px rgba(255, 170, 0, 0.15);
    }

    .btn {
      width: 100%;
      padding: 0.9rem;
      background: var(--color-primario);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: 0.3s;
    }

    .btn:hover {
      background: var(--color-acento);
      transform: translateY(-1px);
    }

    .mensaje-error {
      color: var(--color-error);
      font-size: 0.9rem;
      line-height: 1.4;
      margin-bottom: 1rem;
    }

    .mensaje-exito {
      color: var(--color-exito);
      background-color: rgba(39, 174, 96, 0.1);
      padding: 0.8rem;
      border-radius: 8px;
      width: 100%;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .forgot-link {
      font-size: 0.9rem;
      color: var(--color-texto);
      text-align: right;
      width: 100%;
      margin-top: -0.5rem;
    }

    .forgot-link a {
      color: var(--color-primario);
      text-decoration: none;
      font-weight: 500;
    }

    .forgot-link a:hover {
      text-decoration: underline;
    }

    .register-link {
      font-size: 0.9rem;
      color: var(--color-texto);
      margin-top: 0.5rem;
    }

    .register-link a {
      color: var(--color-primario);
      text-decoration: none;
      font-weight: 500;
    }

    .register-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>Acceso Empleados</h1>
    <p class="subtitulo">Ingresa con tu correo corporativo</p>

    <?php if (isset($error_message)): ?>
      <div class="mensaje-error"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form method="POST" style="width: 100%;">
      <div class="input-group">
        <label for="correo">Correo Electrónico</label>
        <input type="email" id="correo" name="correo" placeholder="tu@correo.interrapidisimo.com" required>
      </div>

      <div class="input-group">
        <label for="contrasena">Contraseña</label>
        <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>
      </div>

      <div class="forgot-link">
        <a href="recuperar_contrasena.php">¿Olvidaste tu contraseña?</a>
      </div>

      <button type="submit" name="iniciar_sesion" class="btn">Ingresar al Sistema</button>
    </form>

    <div class="register-link">
      ¿No tienes cuenta? <a href="registro_empleado.php">Regístrate</a>
    </div>
  </div>
</body>
</html>