<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joyeras Suárez - Panel de Administración</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* Estilos específicos para el panel CRUD */
        .crud-container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
        }
        
        .crud-title {
            color: #B8860B;
            font-size: 2.5em;
            margin-bottom: 30px;
        }
        
        .crud-buttons {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        
        .crud-btn {
            background: #FAF0E6;
            border: 3px solid #DAA520;
            border-radius: 10px;
            padding: 30px;
            transition: all 0.3s;
            text-decoration: none;
            color: #000;
        }
        
        .crud-btn:hover {
            background: #DAA520;
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        
        .crud-btn i {
            font-size: 3em;
            color: #A0522D;
            margin-bottom: 15px;
        }
        
        .crud-btn h3 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .welcome-message {
            color: #A0522D;
            margin-bottom: 40px;
            font-size: 1.2em;
        }
    </style>
    <!-- Iconos de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="wrap">
        <header id="header">
            <div class="logo">
                <h1>Joyeras Suárez</h1>
                <h2>Panel de Administración</h2>
            </div>
            <nav id="menu">
                <ul>
                    <li><a href="logout.php" class="btn-logout">Cerrar Sesión</a></li>
                </ul>
            </nav>
        </header>

        <div class="crud-container">
            <h1 class="crud-title">Gestión de Joyería</h1>
            <p class="welcome-message">Bienvenido, <?php echo $_SESSION['user']; ?></p>
            
            <div class="crud-buttons">
                <!-- Crear -->
                <a href="crear.php" class="crud-btn">
                    <i class="fas fa-plus-circle"></i>
                    <h3>Crear</h3>
                </a>
                
                <!-- Leer/Consultar -->
                <a href="consultar.php" class="crud-btn">
                    <i class="fas fa-search"></i>
                    <h3>Consultar </h3>
                </a>
            </div>

            <div class="crud-buttons">
                <!-- Botón Actualizar (ejemplo con parámetros) -->
                <a href="consultar.php?table=articulos" class="crud-btn">
                    <i class="fas fa-edit"></i>
                    <h3>Editar</h3>
                </a>
                
                <!-- Botón Borrar (ejemplo con parámetros) -->
                <a href="consultar.php?table=articulos" class="crud-btn">
                    <i class="fas fa-trash-alt"></i>
                    <h3>Deshabilitar</h3>
                </a>

                <a href="reportes.php" class="crud-btn">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Reportes</h3>
                    <p>Generar reportes administrativos</p>
                </a>
            </div>
        </div>

        <footer id="footer">
            <p>© 2023 Joyeras Suárez. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>