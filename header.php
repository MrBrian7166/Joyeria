<?php
// header.php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>
    <div id="wrap">
        <header id="header">
            <div class="logo">
                <h1>Joyeras Suárez</h1>
                <h2>
                    <?= isset($_SESSION['rol']) && $_SESSION['rol'] == 'admin' ? 
                        'Panel de Administración' : 'Panel de Cliente' ?>
                </h2>
            </div>
            <nav id="menu">
                <ul>
                    <?php if (isset($_SESSION['user'])): ?>
                        <?php if ($_SESSION['rol'] == 'admin'): ?>
                            <li><a href="index.php">Admin</a></li>
                        <?php else: ?>
                            <li><a href="cliente.php">Inicio</a></li>
                        <?php endif; ?>
                        <li><a href="perfil.php">Mi Perfil</a></li>
                        <li><a href="logout.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </header>
        <main>