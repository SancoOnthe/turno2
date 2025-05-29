<?php
require_once('conexion.php');
session_start();

$error = '';
$show_verification = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['registrar'])) {
        $nombre = trim($_POST['nombre']);
        $cedula = trim($_POST['cedula']);
        $telefono = trim($_POST['telefono']);
        
        // Validaciones
        if (empty($nombre) || empty($cedula) || empty($telefono)) {
            $error = "Todos los campos son obligatorios";
        } elseif (!preg_match('/^[0-9]{6,12}$/', $cedula)) {
            $error = "La cédula debe contener entre 6 y 12 dígitos";
        } elseif (!preg_match('/^[0-9]{10}$/', $telefono)) {
            $error = "El teléfono debe contener exactamente 10 dígitos";
        } else {
            try {
                // Verificar si el teléfono o cédula ya existen
                $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE telefono = ? OR cedula = ?");
                $stmt->bind_param("ss", $telefono, $cedula);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows > 0) {
                    $error = "Este número o cédula ya está registrado. <a href='login_cliente.php' style='color:#FFAA00;'>¿Iniciar sesión?</a>";
                } else {
                    // Generar código de verificación
                    $codigo_verificacion = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    
                    // Insertar nuevo cliente
                    $stmt = $conn->prepare("INSERT INTO clientes (nombre, cedula, telefono, codigo_verificacion, verificado, estado) VALUES (?, ?, ?, ?, 0, 'pendiente')");
                    $stmt->bind_param("ssss", $nombre, $cedula, $telefono, $codigo_verificacion);
                    
                    if ($stmt->execute()) {
                        $_SESSION['telefono_registro'] = $telefono;
                        $_SESSION['codigo_verificacion'] = $codigo_verificacion;
                        $show_verification = true;
                    } else {
                        $error = "Error al registrar: " . $conn->error;
                    }
                }
            } catch (Exception $e) {
                $error = "Error en el sistema: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['verificar'])) {
        $codigo_ingresado = trim($_POST['codigo']);
        $telefono = $_SESSION['telefono_registro'];
        $codigo_correcto = $_SESSION['codigo_verificacion'];
        
        if ($codigo_ingresado === $codigo_correcto) {
            // Actualizar estado de verificación
            $stmt = $conn->prepare("UPDATE clientes SET verificado = 1, estado = 'activo' WHERE telefono = ?");
            $stmt->bind_param("s", $telefono);
            $stmt->execute();
            
           $_SESSION['mensaje_registro'] = "✅ ¡Verificación exitosa! Ya puedes iniciar sesión.";
           header("Location: login_cliente.php");
            exit();

        } else {
            $error = "Código incorrecto. Intenta nuevamente.";
            $show_verification = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Interrapidísimo</title>
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
            max-width: 420px;
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

        input {
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

        .login-link {
            display: block;
            margin-top: 1.5rem;
            color: var(--color-texto);
            font-size: 0.9rem;
        }

        .login-link a {
            color: var(--color-primario);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .login-link a:hover {
            text-decoration: underline;
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
        }

        .mensaje-exito {
            color: var(--color-exito);
            background-color: rgba(39, 174, 96, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            border-left: 4px solid var(--color-exito);
        }

        .digit-counter {
            font-size: 0.75rem;
            color: #95a5a6;
            text-align: right;
            margin-top: 0.3rem;
        }

        .verification-section {
            display: <?php echo $show_verification ? 'block' : 'none'; ?>;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }

        .input-hint {
            font-size: 0.75rem;
            color: #7f8c8d;
            margin-top: 0.3rem;
            font-style: italic;
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 1.8rem 1.5rem;
            }
            
            h2 {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2><?php echo $show_verification ? 'Verifica tu número' : 'Registro de Cliente'; ?></h2>
        
        <?php if (!empty($error)): ?>
            <div class="mensaje-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$show_verification): ?>
        <form method="POST" autocomplete="off">
            <div class="input-group">
                <label for="nombre">Nombre completo</label>
                <input type="text" id="nombre" name="nombre" placeholder="Ingresa tu nombre completo" required
                       value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>">
            </div>
            
            <div class="input-group">
                <label for="cedula">Número de cédula</label>
                <input type="text" id="cedula" name="cedula" 
                       placeholder="Ej: 1234567890"
                       pattern="[0-9]{6,12}"
                       title="Debe contener entre 6 y 12 dígitos"
                       required
                       value="<?= isset($_POST['cedula']) ? htmlspecialchars($_POST['cedula']) : '' ?>"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                <div class="input-hint">Sin puntos ni guiones</div>
            </div>
            
            <div class="input-group">
                <label for="telefono">Número de teléfono</label>
                <input type="tel" id="telefono" name="telefono" 
                       placeholder="Ej: 3001234567" 
                       maxlength="10" 
                       pattern="[0-9]{10}" 
                       title="Debe contener exactamente 10 dígitos" 
                       required
                       value="<?= isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : '' ?>"
                       oninput="this.value = this.value.replace(/[^0-9]/g, ''); updateCounter(this)">
                <div class="digit-counter" id="counter">0/10 dígitos</div>
            </div>
            
            <button type="submit" name="registrar" class="btn">Registrarme</button>
        </form>
        <?php endif; ?>
        
        <div class="verification-section">
            <p style="margin-bottom: 1rem;">Hemos enviado un código de verificación al teléfono registrado</p>
            
            <form method="POST" autocomplete="off">
                <div class="input-group">
                    <label for="codigo">Código de verificación (6 dígitos)</label>
                    <input type="text" id="codigo" name="codigo" 
                           placeholder="Ingresa el código" 
                           maxlength="6" 
                           pattern="[0-9]{6}" 
                           required
                           oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
                
                <button type="submit" name="verificar" class="btn">Verificar</button>
            </form>
        </div>
        
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login_cliente.php">Inicia sesión aquí</a>
        </div>
    </div>

    <script>
        // Actualizar contador de dígitos para teléfono
        function updateCounter(input) {
            const counter = document.getElementById('counter');
            const length = input.value.length;
            counter.textContent = `${length}/10 dígitos`;
            
            if (length === 10) {
                counter.style.color = '#27ae60';
            } else {
                counter.style.color = '#95a5a6';
            }
        }

        // Bloquear caracteres no numéricos en campos relevantes
        document.querySelectorAll('input[type="tel"], input[name="cedula"], input[name="codigo"]').forEach(input => {
            input.addEventListener('keydown', function(e) {
                // Permitir teclas de control
                if ([46, 8, 9, 27, 13, 110, 190].includes(e.keyCode) || 
                    (e.keyCode === 65 && (e.ctrlKey || e.metaKey)) || 
                    (e.keyCode >= 35 && e.keyCode <= 39)) {
                    return;
                }
                
                // Bloquear caracteres no numéricos
                if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                    e.preventDefault();
                }
            });
        });

        // Auto-enfoque en el primer campo vacío
        document.addEventListener('DOMContentLoaded', function() {
            const emptyInput = document.querySelector('input:not([value]):not([type="hidden"])');
            if (emptyInput) {
                emptyInput.focus();
            }
        });
    </script>
</body>
</html>