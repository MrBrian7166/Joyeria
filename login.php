<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php"); // Redirige si ya está logueado
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joyeras Suárez - Iniciar Sesión</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .auth-options {
            margin-top: 20px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .btn-register {
            background: #A0522D;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-home {
            background: #DAA520;
            color: #000;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>
<body>
    <div id="wrap">
        <form method="POST" action="auth_login.php" class="auth-form">
            <h2>Iniciar Sesión</h2>
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-submit">Acceder</button>
            
            <div class="auth-options">
                <?php if (isset($_GET['error'])): ?>
                    <p class="error">Usuario o contraseña incorrectos.</p>
                <?php endif; ?>
                
                <div class="btn-group">
                    <a href="registro.php" class="btn-register">
                        <i class="fas fa-user-plus"></i> Registrarse
                    </a>
                    <a href="index.html" class="btn-home">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Iconos de Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>