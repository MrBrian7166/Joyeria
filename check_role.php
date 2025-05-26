<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Definir qué roles pueden acceder a qué
$allowedRoles = [
    'admin' => ['index.php', 'crear.php', 'consultar.php', 'actualizar.php', 'deshabilitar.php', 'reportes.php', ''],
    'cliente' => ['cliente.php', 'perfil.php', 'pedidos.php', 'crear_pedido.php', 'ver_pedidos.php', 'ticket_pedido.php']
];

$currentPage = basename($_SERVER['PHP_SELF']);

if ($_SESSION['rol'] == 'admin' && !in_array($currentPage, $allowedRoles['admin'])) {
    header("Location: index.php");
    exit;
}

if ($_SESSION['rol'] == 'cliente' && !in_array($currentPage, $allowedRoles['cliente'])) {
    header("Location: cliente.php");
    exit;
}
?>