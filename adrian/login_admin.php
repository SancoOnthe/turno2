<?php
session_start();
include('conexion.php'); // Usando tu archivo de conexión existente

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['usuario']) || empty($_POST['contrasena'])) {
        $error = "Usuario y contraseña son requeridos";
    } else {
        $usuario = trim($_POST['usuario']);

        // Consulta usando tu conexión existente
        $query = "SELECT id_admin, nombre, contrasena FROM administradores WHERE usuario = '$usuario'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);
            
            // Verificación de contraseña (compatible con password_hash y SHA256)
            if (password_verify($_POST['contrasena'], $admin['contrasena']) || 
                hash('sha256', $_POST['contrasena']) === $admin['contrasena']) {
                
                $_SESSION['admin_id'] = $admin['id_admin'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['admin_logged_in'] = true;
                
                header("Location: panel_admin.php");
                exit();
            }
        }

        $error = "Credenciales incorrectas";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrador - Interrapidísimo</title>
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
                        url('../assets/img/int.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .login-container {
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

        .login-logo {
            width: 180px;
            margin-bottom: 1rem;
        }

        .login-title {
            color: var(--color-texto);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-form {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .form-group {
            width: 100%;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--color-texto);
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--color-borde);
            border-radius: 10px;
            font-size: 1rem;
            transition: 0.3s;
        }

        .form-group input:focus {
            border-color: var(--color-primario);
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 170, 0, 0.15);
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: var(--color-primario);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 1rem;
        }

        .btn-login:hover {
            background: var(--color-acento);
            transform: translateY(-1px);
        }

        .error-message {
            color: var(--color-error);
            background-color: rgba(231, 76, 60, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            width: 100%;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .btn-loading {
            position: relative;
            pointer-events: none;
        }
        
        .btn-loading::after {
            content: "";
            position: absolute;
            width: 16px;
            height: 16px;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            margin: auto;
            border: 3px solid transparent;
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="../assets/img/logo_interrapidisimo.png" alt="Interrapidísimo" class="login-logo">
        <h1 class="login-title">Acceso Administrativo</h1>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form" id="loginForm">
            <div class="form-group">
                <label for="usuario">Usuario:</label>
                <input type="text" id="usuario" name="usuario" required autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login" id="submitBtn">
                Ingresar al Sistema
            </button>
        </form>
    </div>

    <script>
        // Mejora UX: Mostrar loading al enviar formulario
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('btn-loading');
            btn.disabled = true;
            btn.innerHTML = "Verificando...";
        });
    </script>
</body>
</html>