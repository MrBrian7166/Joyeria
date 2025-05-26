<?php
require_once 'check_role.php';
if ($_SESSION['rol'] != 'cliente') {
    header("Location: index.php");
    exit;
}

require_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

// Obtener ID del cliente
$query = "SELECT idcliente FROM cliente WHERE correo = (SELECT correo FROM seguridad WHERE usuario = ?)";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$id_cliente = $cliente['idcliente'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .pedidos-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .pedidos-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 30px;
            margin-bottom: 40px;
        }
        .pedido-btn {
            background: #FAF0E6;
            border: 3px solid #DAA520;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            text-decoration: none;
            color: #000;
            transition: all 0.3s;
        }
        .pedido-btn:hover {
            background: #DAA520;
            transform: translateY(-5px);
        }
        .pedido-btn i {
            font-size: 3em;
            color: #A0522D;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="pedidos-container">
        <h1>Gestión de Pedidos</h1>
        
        <div class="pedidos-options">
            <a href="crear_pedido.php" class="pedido-btn">
                <i class="fas fa-plus-circle"></i>
                <h3>Crear Nuevo Pedido</h3>
            </a>
            
            <a href="ver_pedidos.php" class="pedido-btn">
                <i class="fas fa-list-alt"></i>
                <h3>Mis Pedidos Anteriores</h3>
            </a>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>