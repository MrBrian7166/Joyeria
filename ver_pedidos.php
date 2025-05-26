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

// Obtener pedidos del cliente
$query = "SELECT p.*, COUNT(d.id_detalle) as items 
          FROM pedidos p
          LEFT JOIN pedido_detalles d ON p.id_pedido = d.id_pedido
          WHERE p.id_cliente = ?
          GROUP BY p.id_pedido
          ORDER BY p.fecha DESC";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$pedidos = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Pedidos | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .pedidos-list {
            max-width: 1000px;
            margin: 30px auto;
        }
        .pedido-card {
            background: #FAF0E6;
            border: 1px solid #DAA520;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .pedido-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #DAA520;
        }
        .pedido-items {
            margin-top: 15px;
        }
        .item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .pedido-total {
            text-align: right;
            font-weight: bold;
            margin-top: 10px;
            font-size: 1.1em;
        }
        .estado {
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        .pendiente {
            background: #FFF3CD;
            color: #856404;
        }
        .completado {
            background: #D4EDDA;
            color: #155724;
        }
        .btn-ticket {
            background: #A0522D;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="pedidos-list">
        <h1>Mis Pedidos Anteriores</h1>
        
        <?php if ($pedidos->num_rows === 0): ?>
            <p>Aún no has realizado ningún pedido.</p>
        <?php else: ?>
            <?php while ($pedido = $pedidos->fetch_assoc()): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div>
                            <h3>Pedido #<?= str_pad($pedido['id_pedido'], 6, '0', STR_PAD_LEFT) ?></h3>
                            <small><?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></small>
                        </div>
                        <div>
                            <span class="estado <?= $pedido['estado'] ?>"><?= ucfirst($pedido['estado']) ?></span>
                        </div>
                    </div>
                    
                    <div class="pedido-items">
                        <?php 
                        $query = "SELECT d.*, a.descripcion 
                                 FROM pedido_detalles d
                                 JOIN articulos a ON d.id_articulo = a.id_articulo
                                 WHERE d.id_pedido = ?";
                        $stmt = $mysql->connection->prepare($query);
                        $stmt->bind_param("i", $pedido['id_pedido']);
                        $stmt->execute();
                        $items = $stmt->get_result();
                        
                        while ($item = $items->fetch_assoc()): ?>
                            <div class="item">
                                <span><?= $item['cantidad'] ?> x <?= htmlspecialchars($item['descripcion']) ?></span>
                                <span>€<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
                            </div>
                        <?php endwhile; ?>
                        
                        <div class="pedido-total">
                            Total: €<?= number_format($pedido['total'], 2) ?>
                        </div>
                        
                        <a href="ticket_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn-ticket">
                            <i class="fas fa-receipt"></i> Ver Ticket
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>