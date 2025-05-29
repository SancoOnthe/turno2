<?php
require_once('conexion.php');
session_start();
$exito = false;
// Mensaje de registro exitoso
if (isset($_SESSION['mensaje_registro'])) {
    $exito = True;
    $mensaje_registro = $_SESSION['mensaje_registro'];
}

if (isset($_POST['iniciar_sesion'])) {
    $telefono = trim($_POST['telefono']);
    $cedula = trim($_POST['cedula']);

    try {
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE telefono = ? AND cedula = ? AND verificado = 1 AND estado = 'activo'");
        $stmt->bind_param("ss", $telefono, $cedula);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
    $cliente = $resultado->fetch_assoc();
    $_SESSION['id_cliente'] = $cliente['id_cliente'];
    $_SESSION['telefono'] = $cliente['telefono'];
    $_SESSION['nombre'] = $cliente['nombre'];
    $_SESSION['cedula'] = $cliente['cedula'];
    
    header("Location: index.php");
    exit();
} else {
    $error_message = "❌ Datos incorrectos o no verificado. Intenta nuevamente";
}
    } catch (Exception $e) {
        $error_message = "Error en el sistema: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login - Interrapidísimo</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet" />
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
      padding: 2rem;
      border-radius: 16px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
      text-align: center;
    }

    h1 {
      color: var(--color-texto);
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .input-group {
      width: 100%;
      text-align: left;
      margin-bottom: 0.5rem;
    }

    label {
      display: block;
      margin-bottom: 0.3rem;
      font-weight: 500;
      color: var(--color-texto);
      font-size: 0.9rem;
    }

    input[type="tel"], input[type="text"] {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid var(--color-borde);
      border-radius: 8px;
      font-size: 0.95rem;
      transition: 0.2s;
    }

    input[type="tel"]:focus, input[type="text"]:focus {
      border-color: var(--color-primario);
      outline: none;
      box-shadow: 0 0 0 3px rgba(255, 170, 0, 0.15);
    }

    .btn {
      width: 100%;
      padding: 0.8rem;
      background: var(--color-primario);
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: 0.2s;
      margin-top: 0.5rem;
    }

    .btn:hover {
      background: var(--color-acento);
      transform: translateY(-1px);
    }

    .digit-counter {
      text-align: right;
      font-size: 0.7rem;
      color: #95a5a6;
      margin-top: 0.2rem;
    }

    .mensaje-error {
      color: var(--color-error);
      font-size: 0.85rem;
      line-height: 1.4;
      margin-top: 0.5rem;
    }

    .mensaje-exito {
      color: var(--color-exito);
      background-color: rgba(39, 174, 96, 0.1);
      padding: 0.7rem;
      border-radius: 8px;
      width: 100%;
      font-size: 0.85rem;
      margin: 0.5rem 0;
    }

    .register-link {
      font-size: 0.85rem;
      color: var(--color-texto);
      margin-top: 0.5rem;
    }

    .register-link a {
      color: var(--color-primario);
      text-decoration: none;
      font-weight: 600;
    }

    .register-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <?php if($exito): ?>
      <div class='mensaje-exito'><?php echo $mensaje_registro;?></div>
      <?php endif; ?>
    <h1>Inicio de sesión</h1>

    <form method="POST" style="width: 100%;">
      <div class="input-group">
        <label for="telefono">Número de teléfono</label>
        <input type="tel" id="telefono" name="telefono" maxlength="10" pattern="[0-9]{10}" placeholder="Ej: 3001234567" required
          oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateCounter(this, 'telefono-counter')">
        <div class="digit-counter" id="telefono-counter">0/10 dígitos</div>
      </div>

      <div class="input-group">
        <label for="cedula">Número de cédula</label>
        <input type="text" id="cedula" name="cedula" placeholder="Ej: 1234567890" required
          oninput="this.value = this.value.replace(/[^0-9]/g, '');">
      </div>

      <button type="submit" name="iniciar_sesion" class="btn">Continuar</button>
    </form>

    <div class="register-link">
      ¿No tienes cuenta? <a href="registro_cliente.php">Regístrate aquí</a>
    </div>

    <?php if (isset($error_message)): ?>
      <div class="mensaje-error"><?= $error_message ?></div>
    <?php endif; ?>
  </div>

  <script>
    function updateCounter(input, counterId) {
      const counter = document.getElementById(counterId);
      const maxLength = input.getAttribute('maxlength');
      counter.textContent = `${input.value.length}/${maxLength} dígitos`;
      counter.style.color = input.value.length == maxLength ? '#27ae60' : '#95a5a6';
    }

    // Validación para solo números en teléfono y cédula
    document.getElementById('telefono').addEventListener('keydown', function(e) {
      restrictToNumbers(e);
    });

    document.getElementById('cedula').addEventListener('keydown', function(e) {
      restrictToNumbers(e);
    });

    function restrictToNumbers(e) {
      if ([46, 8, 9, 27, 13].includes(e.keyCode) || 
          (e.keyCode === 65 && (e.ctrlKey || e.metaKey)) ||
          (e.keyCode === 67 && (e.ctrlKey || e.metaKey)) ||
          (e.keyCode === 86 && (e.ctrlKey || e.metaKey)) ||
          (e.keyCode === 88 && (e.ctrlKey || e.metaKey)) ||
          (e.keyCode >= 35 && e.keyCode <= 39)) return;
      
      if ((e.shiftKey || e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
        e.preventDefault();
      }
    }
  </script>
</body>
</html>