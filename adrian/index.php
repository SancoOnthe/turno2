

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Interrapidísimo - Sistema de Turnos</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
 <style>
:root {
  --color-primario: #FFAA00;
  --color-secundario: #ffb700;
  --color-acento: #FF6B00;
  --color-fondo: #F7F7F7;
  --color-texto: #343A40;
  --color-negro: #495057;
  --color-footer: #343A40;
  --hover: rgba(0,0,0,0.05);
  --sombra: 0 4px 15px rgba(0,0,0,0.1);
  --sombra-hover: 0 6px 20px rgba(0,0,0,0.15);
  --transicion: all 0.3s ease;
}

* { 
  box-sizing: border-box; 
  margin: 0; 
  padding: 0; 
  font-family: 'Poppins', sans-serif;
}

body {
  background: linear-gradient(rgba(247, 247, 247, 0.9), rgba(247, 247, 247, 0.9)), 
              url('img/int.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  min-height: 100vh;
  color: var(--color-texto);
}

/* === HEADER MEJORADO === */
.header-container {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 5%;
  background-color: white;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-left, .header-right {
  display: flex;
  gap: 15px;
}

.logo-container {
  flex-grow: 1;
  text-align: center;
}

.logo {
  height: 60px;
  transition: var(--transicion);
}

.logo:hover {
  transform: scale(1.05);
}

.nav-button {
  padding: 8px 20px;
  border-radius: 20px;
  font-weight: 500;
  font-size: 14px;
  text-decoration: none;
  transition: var(--transicion);
  color: var(--color-negro);
}

.nav-button:hover {
  background-color: rgba(255, 170, 0, 0.1);
}

.contact-button {
  background-color: var(--color-negro);
  color: white;
}

.contact-button:hover {
  background-color: var(--color-acento);
}

.logout-button {
  color: #e74c3c;
  border: 1px solid #e74c3c;
}

.logout-button:hover {
  background-color: rgba(231, 76, 60, 0.1);
}

/* === HERO SECTION === */
.contenedor {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.hero {
  text-align: center;
  padding: 60px 30px;
  margin: 30px 0;
  background-color: white;
  border-radius: 12px;
  box-shadow: var(--sombra);
}

.hero h1 {
  font-size: 2.2rem;
  margin-bottom: 15px;
  color: var(--color-negro);
  font-weight: 600;
}

.hero p {
  font-size: 1.1rem;
  color: var(--color-negro);
  margin-bottom: 30px;
  opacity: 0.9;
}

.botones-container {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
}

.boton-principal {
  background-color: var(--color-primario);
  color: white;
  border: none;
  padding: 12px 30px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: var(--transicion);
  box-shadow: var(--sombra);
  min-width: 180px;
}

.boton-principal:hover {
  background-color: var(--color-acento);
  transform: translateY(-3px);
  box-shadow: var(--sombra-hover);
}

.boton-secundario {
  background-color: white;
  color: var(--color-primario);
  border: 2px solid var(--color-primario);
  padding: 12px 30px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: var(--transicion);
  min-width: 180px;
}

.boton-secundario:hover {
  background-color: var(--color-primario);
  color: white;
  transform: translateY(-3px);
}

/* === PASOS MEJORADOS === */
#como-funciona {
  padding: 50px 0;
  text-align: center;
}

#como-funciona h2 {
  font-size: 1.8rem;
  color: var(--color-negro);
  margin-bottom: 15px;
  font-weight: 600;
}

.pasos {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 25px;
  margin: 30px 0;
}

.paso {
  flex: 1;
  min-width: 250px;
  max-width: 300px;
  text-align: center;
  padding: 25px;
  background: white;
  border-radius: 10px;
  box-shadow: var(--sombra);
  transition: var(--transicion);
}

.paso:hover {
  transform: translateY(-5px);
  box-shadow: var(--sombra-hover);
}

.paso img {
  width: 70px;
  margin-bottom: 15px;
}

.paso h3 {
  font-size: 1.2rem;
  margin-bottom: 12px;
  color: var(--color-negro);
}

.paso p {
  color: var(--color-negro);
  opacity: 0.8;
  line-height: 1.5;
  font-size: 0.95rem;
}

/* === FOOTER === */
footer {
  background-color: var(--color-footer);
  color: white;
  text-align: center;
  padding: 25px 20px;
  margin-top: 50px;
}

footer p {
  margin-bottom: 8px;
  font-size: 0.9rem;
}

footer a {
  color: white;
  text-decoration: none;
  transition: var(--transicion);
}

footer a:hover {
  color: var(--color-primario);
  text-decoration: underline;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .header-container {
    flex-direction: column;
    gap: 15px;
    padding: 15px;
  }
  
  .header-left, .header-right {
    width: 100%;
    justify-content: center;
  }
  
  .hero {
    padding: 40px 20px;
  }
  
  .hero h1 {
    font-size: 1.8rem;
  }
  
  .botones-container {
    flex-direction: column;
    align-items: center;
  }
  
  .boton-principal, .boton-secundario {
    width: 100%;
    max-width: 250px;
  }
  
  .paso {
    min-width: 100%;
    max-width: 350px;
  }
}

@media (max-width: 480px) {
  .header-left, .header-right {
    flex-direction: column;
    align-items: center;
  }
  
  .nav-button {
    width: 100%;
    text-align: center;
  }
  
  .hero h1 {
    font-size: 1.6rem;
  }
  
  #como-funciona h2 {
    font-size: 1.5rem;
  }
}

  /* Animación de flotar suave */
  @keyframes float {
    0% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
    100% { transform: translateY(0); }
  }
  
  .logo-container {
    animation: float 3s ease-in-out infinite;
    transition: all 0.3s ease;
  }
  
  .logo-container:hover {
    animation-play-state: paused;
    transform: scale(1.02);
  }

</style>
</head>
<body>

  <header>
  <header class="header-container">
  <!-- Grupo izquierdo (Nosotros, Contáctanos) -->
  <div class="header-group">
    <a href="#nosotros" class="nav-button">Nosotros</a>
    <a href="#contacto" class="nav-button contact-button">Contáctanos</a>
  </div>

  
<div style="height: 50px; display: flex; justify-content: center; align-items: center;">
  <img src="data:image/png;base64,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" alt="RAPIDISITIO INTER" style="height: 80px; max-width: 100%;">
</div>

  
  <!-- Grupo derecho (Perfil, Cerrar sesión) -->
  <div class="header-group">
    <a href="perfil_cliente.php" class="nav-button">Perfil</a>
    <a href="logout.php" class="nav-button logout-button">Cerrar sesión</a>
  </div>
</header>
</header>
  <main class="contenedor">
    <section class="hero">
      <h1>Gestiona tus turnos fácil y rápido</h1>
      <p>Evita filas y organiza tu visita a nuestras sucursales</p>
      <div>
        <a href="solicitar_turno.php" style="text-decoration:none;">
          <button class="boton-principal" type="button">Solicitar Turno</button>
        </a>
        <form method="GET" action="ver_turno.php" style="display:inline;">
          <button class="boton-principal" type="submit">Ver Mi Turno</button>
        </form>
      </div>
    </section>

    <section id="como-funciona">
      <h2 style="text-align:center;">¿Cómo funciona?</h2>
      <div class="pasos">
        <div class="paso">
          <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAOEAAADhCAMAAAAJbSJIAAAAWlBMVEX///8AAAAXFxfAwMCcnJzg4OCZmZn8/Pzn5+cjIyP4+PgRERFERERNTU3X19efn59sbGzy8vIdHR0KCgq8vLyAgIBxcXEoKCiKiopCQkLJyclcXFzq6uqTk5OdKecmAAAB8UlEQVR4nO3dXU/iYBCAUerCUpDlS0VF/P9/c4PRZAl9ByjJdgrnXI/JPGlUuJkOBgAAAAAAAAAAAAAAACmMfl1m1PXCl5lvqstt5l2vfbbVskXf3nLV9ernmb+2DKyq1148xtG6dWBVrXvw6zieXRFYVbNx1wEnLa4KrKpF1wGnTJ+a1p5sd/WR7aRp9GnadcIJz40P5vnq2TxeGrfeNs5uG2df/vPGl2r+OzOrx8d2hdmuE04o/auYHCtMPnSdcMJDYe/zKeyaQoX9KPwY/m5n+NGTwvYfLRc9KRy2/umhwgQUxhRmoDCmMAOFMYUZKIwpzEBhTGEGCmMKM1AYU5iBwpjCDBTGFGagMKYwA4UxhRkojCnMQGFMYQYKYwozUBhTmIHCmMIMFMYUZqAwpjADhTGFGSiMKcxAYUxhBgpjCjNQGFOYgcKYwgyuuxrRl8K3etRO/daTwuso7JpChT0vfPzWeD3yBgqX9fRHHdzh7W/h5mCufEo5e2H5xG59MFcX59YdbX6u9+KjObzzvHosDb53tPm5mu+X7u0O5nbFuez3S5vvyu59Hsx9Fuey36BtviP85d+vHOV7yunvCIe3oP/8CGbS34K+g3vet3+T/Q7u6t/BuxEGt/9+iy83/o4SAAAAAAAAAAAAAADgdv0FOasary2vo+oAAAAASUVORK5CYII=" alt="Paso 1">
          <h3>Regístrate con tu teléfono</h3>
          <p>Solo necesitas tu número móvil para comenzar</p>
        </div>
        <div class="paso">
          <img src="data:image/png;base64,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" alt="Paso 2">
          <h3>Regístrate con tu teléfono</h3>
          <p>Selecciona entre envío, retiro u otros servicios</p>
        </div>
        <div class="paso">
           <img src="data:image/png;base64,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" alt="Paso 3">
          <h3>Recibe notificaciones</h3>
          <p>Te avisaremos por SMS cuando sea tu turno</p>
        </div>
      </div>
    </section>
  </main>

  <footer>
    <p>© 2024 Interrapidísimo - Todos los derechos reservados</p>
    <p><a href="tel:+573001234567">Línea de atención: 300 123 4567</a></p>
  </footer>

  <script>
    // Toggle menú
    const toggle = document.getElementById('menuToggle');
    const menu   = document.getElementById('dropdownMenu');

    toggle.addEventListener('click', e => {
      menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    });

    // Cerrar si clic fuera
    document.addEventListener('click', e => {
      if (!toggle.contains(e.target)) {
        menu.style.display = 'none';
      }
    });
  </script>

</body>
</html>
