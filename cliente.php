<?php
require_once 'check_role.php';

if ($_SESSION['rol'] != 'cliente') {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cliente-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            text-align: center;
        }
        .cliente-title {
            color: #B8860B;
            font-size: 2.5em;
            margin-bottom: 30px;
        }
        .cliente-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
        }
        .cliente-btn {
            background: #FAF0E6;
            border: 3px solid #DAA520;
            border-radius: 10px;
            padding: 30px;
            transition: all 0.3s;
            text-decoration: none;
            color: #000;
        }
        .cliente-btn:hover {
            background: #DAA520;
            transform: translateY(-5px);
        }
        .cliente-btn i {
            font-size: 3em;
            color: #A0522D;
            margin-bottom: 15px;
        }
        .welcome-message {
            color: #A0522D;
            margin-bottom: 40px;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="cliente-container">
        <h1 class="cliente-title">Bienvenido, <?= $_SESSION['user']; ?></h1>
        <p class="welcome-message">Panel de cliente - Joyeras Suárez</p>
        
        <div class="cliente-options">
            <!-- Botón 1: Gestionar Pedidos (nueva función) -->
            <a href="pedidos.php" class="cliente-btn">
                <i class="fas fa-shopping-bag"></i>
                <h3>Mis Pedidos</h3>
                <p>Crear y ver tus pedidos</p>
            </a>
            
            <!-- Botón 2: Perfil del Cliente (existente) -->
            <a href="perfil.php" class="cliente-btn">
                <i class="fas fa-user-edit"></i>
                <h3>Mi Perfil</h3>
                <p>Actualizar tus datos</p>
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>