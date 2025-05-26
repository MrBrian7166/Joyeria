<?php
session_start();
require_once 'check_role.php';
require_once 'MysqlConnector.php';

// Verificar autenticación y rol
if ($_SESSION['rol'] != 'cliente') {
    header("Location: index.php");
    exit;
}

// Obtener ID del pedido
$id_pedido = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_pedido || $id_pedido <= 0) {
    die("ID de pedido no válido");
}

$mysql = new MysqlConnector();
$mysql->Connect();

// 1. Verificar que el pedido pertenece al cliente actual
$query = "SELECT p.*, c.nombre, c.apellido 
          FROM pedidos p
          JOIN cliente c ON p.id_cliente = c.idcliente
          JOIN seguridad s ON c.correo = s.correo
          WHERE s.usuario = ? AND p.id_pedido = ?";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("si", $_SESSION['user'], $id_pedido);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();

if (!$pedido) {
    die("<div class='error'>Pedido no encontrado o no tienes permisos para verlo</div>");
}

// 2. Obtener los detalles del pedido
$query = "SELECT d.*, a.descripcion 
          FROM pedido_detalles d
          JOIN articulos a ON d.id_articulo = a.id_articulo
          WHERE d.id_pedido = ?";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("i", $id_pedido);
$stmt->execute();
$detalles = $stmt->get_result();

// Calcular total (como verificación adicional)
$total_verificado = 0;
while ($item = $detalles->fetch_assoc()) {
    $total_verificado += $item['precio_unitario'] * $item['cantidad'];
}
$detalles->data_seek(0); // Resetear puntero para mostrarlos después

// Verificar coincidencia con el total registrado
if (abs($total_verificado - $pedido['total']) > 0.01) {
    error_log("Advertencia: Total no coincide para pedido $id_pedido");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Pedido #<?= str_pad($pedido['id_pedido'], 6, '0', STR_PAD_LEFT) ?> | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Arial', sans-serif;
        }
        .ticket-container {
            max-width: 600px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border: 1px solid #DAA520;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .ticket-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #DAA520;
        }
        .ticket-header h1 {
            color: #A0522D;
            margin-bottom: 5px;
        }
        .ticket-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }
        .info-box {
            background: #FAF0E6;
            padding: 15px;
            border-radius: 5px;
        }
        .ticket-items {
            margin: 30px 0;
        }
        .item-header, .ticket-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            padding: 10px 0;
            border-bottom: 1px dashed #DAA520;
        }
        .item-header {
            font-weight: bold;
            border-bottom: 2px solid #DAA520;
        }
        .ticket-total {
            text-align: right;
            font-weight: bold;
            font-size: 1.3em;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #DAA520;
        }
        .estado-pedido {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .pendiente {
            background: #FFF3CD;
            color: #856404;
        }
        .completado {
            background: #D4EDDA;
            color: #155724;
        }
        .btn-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-print {
            background: #A0522D;
            color: white;
        }
        .btn-back {
            background: #DAA520;
            color: #000;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                background: white;
                font-size: 12pt;
            }
            .ticket-container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>Joyeras Suárez</h1>
            <p>Calle Principal 123, Ciudad</p>
            <p>Teléfono: (555) 123-4567</p>
            <p>RFC: JSU123456ABC</p>
        </div>

        <div class="ticket-info">
            <div class="info-box">
                <h3>Información del Pedido</h3>
                <p><strong>No. Pedido:</strong> <?= str_pad($pedido['id_pedido'], 6, '0', STR_PAD_LEFT) ?></p>
                <p><strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($pedido['fecha'])) ?></p>
                <span class="estado-pedido <?= $pedido['estado'] ?>">
                    <?= ucfirst($pedido['estado']) ?>
                </span>
            </div>

            <div class="info-box">
                <h3>Información del Cliente</h3>
                <p><strong>Nombre:</strong> <?= htmlspecialchars($pedido['nombre'] . ' ' . $pedido['apellido']) ?></p>
                <p><strong>Pedido #:</strong> <?= $pedido['id_pedido'] ?></p>
            </div>
        </div>

        <div class="ticket-items">
            <h2>Detalles del Pedido</h2>
            <div class="item-header">
                <div>Artículo</div>
                <div>Cantidad</div>
                <div>Subtotal</div>
            </div>

            <?php while ($item = $detalles->fetch_assoc()): ?>
                <div class="ticket-item">
                    <div><?= htmlspecialchars($item['descripcion']) ?></div>
                    <div><?= $item['cantidad'] ?></div>
                    <div>€<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></div>
                </div>
            <?php endwhile; ?>

            <div class="ticket-total">
                Total: €<?= number_format($pedido['total'], 2) ?>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; font-style: italic; color: #666;">
            <p>Gracias por su preferencia. Para cualquier aclaración, presente este ticket.</p>
            <p>Pedido válido por 30 días a partir de la fecha de emisión.</p>
        </div>

        <div class="btn-actions no-print">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Imprimir Ticket
            </button>
            <a href="ver_pedidos.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Volver a Pedidos
            </a>
        </div>
    </div>

    <script>
        // Auto-imprimir si se abre en una nueva ventana
        if(window.location.search.includes('print=true')) {
            window.print();
        }
    </script>
</body>
</html>