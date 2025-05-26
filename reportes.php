<?php
session_start();
require_once 'check_role.php';
require_once 'MysqlConnector.php';

if ($_SESSION['rol'] != 'admin') {
    header("Location: index.php");
    exit;
}

$mysql = new MysqlConnector();
$mysql->Connect();

// Procesar filtros
$fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_POST['fecha_fin'] ?? date('Y-m-d');
$tipo_reporte = $_POST['tipo_reporte'] ?? 'ventas_tienda';

// Obtener datos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($tipo_reporte) {
        case 'ventas_tienda':
            $titulo = "VENTAS POR TIENDA";
            $query = "SELECT t.descripcion as tienda, COUNT(v.folio) as total_ventas, 
                     SUM(a.precio) as monto_total
                     FROM ventas v
                     JOIN tiendas t ON v.id_tienda = t.id_tienda
                     JOIN articulos a ON v.id_articulo = a.id_articulo
                     WHERE v.fecha BETWEEN ? AND ?
                     GROUP BY t.id_tienda";
            break;
            
        case 'articulos_vendidos':
            $titulo = "ARTÍCULOS VENDIDOS";
            $query = "SELECT a.descripcion, COUNT(v.folio) as cantidad_vendida, 
                     SUM(a.precio) as monto_total
                     FROM ventas v
                     JOIN articulos a ON v.id_articulo = a.id_articulo
                     WHERE v.fecha BETWEEN ? AND ?
                     GROUP BY a.id_articulo
                     ORDER BY cantidad_vendida DESC";
            break;
            
        case 'articulos_deshabilitados':
            $titulo = "ARTÍCULOS DESHABILITADOS";
            $query = "SELECT a.*, l.descripcion as linea
                     FROM articulos a
                     JOIN linea_de_articulos l ON a.idlinea = l.idlinea
                     WHERE a.activo = 0";
            break;
            
        case 'pedidos':
            $titulo = "PEDIDOS REGISTRADOS";
            $query = "SELECT p.id_pedido, p.fecha, c.nombre, c.apellido, 
                     COUNT(pd.id_detalle) as total_articulos, p.total
                     FROM pedidos p
                     JOIN cliente c ON p.id_cliente = c.idcliente
                     JOIN pedido_detalles pd ON p.id_pedido = pd.id_pedido
                     WHERE p.fecha BETWEEN ? AND ?
                     GROUP BY p.id_pedido
                     ORDER BY p.fecha DESC";
            break;
    }
    
    $stmt = $mysql->connection->prepare($query);
    if ($stmt) {
        if ($tipo_reporte != 'articulos_deshabilitados') {
            $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
        }
        $stmt->execute();
        $resultados = $stmt->get_result();
    } else {
        $error = "Error en la consulta: " . $mysql->connection->error;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reportes | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        /* [Mantener todos los estilos anteriores] */
        /* Solo agregamos este nuevo estilo para pedidos */
        .ticket-table .small-col {
            width: 15%;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="ticket-container">
        <h1>Generar Reportes</h1>
        
        <!-- Formulario de filtros -->
        <div class="filtros">
            <form method="POST">
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Tipo de Reporte</label>
                        <select name="tipo_reporte" class="form-control" required>
                            <option value="ventas_tienda" <?= $tipo_reporte == 'ventas_tienda' ? 'selected' : '' ?>>Ventas por Tienda</option>
                            <option value="articulos_vendidos" <?= $tipo_reporte == 'articulos_vendidos' ? 'selected' : '' ?>>Artículos Vendidos</option>
                            <option value="articulos_deshabilitados" <?= $tipo_reporte == 'articulos_deshabilitados' ? 'selected' : '' ?>>Artículos Deshabilitados</option>
                            <option value="pedidos" <?= $tipo_reporte == 'pedidos' ? 'selected' : '' ?>>Pedidos Registrados</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-generar">Generar Reporte</button>
            </form>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?= $error ?></div>
        <?php elseif (isset($resultados) && $resultados->num_rows > 0): ?>
        <!-- Ticket de Reporte -->
        <div class="ticket-reporte">
            <div class="ticket-header">
                <div class="ticket-title">Joyeras Suárez</div>
                <div class="ticket-subtitle"><?= $titulo ?></div>
                <div class="ticket-subtitle">
                    <?= $tipo_reporte != 'articulos_deshabilitados' ? 'Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) : '' ?>
                </div>
            </div>
            
            <div class="ticket-details">
                <table class="ticket-table">
                    <thead>
                        <tr>
                            <?php if ($tipo_reporte == 'ventas_tienda'): ?>
                                <th>Tienda</th>
                                <th>Ventas</th>
                                <th>Total</th>
                            <?php elseif ($tipo_reporte == 'articulos_vendidos'): ?>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                                <th>Total</th>
                            <?php elseif ($tipo_reporte == 'articulos_deshabilitados'): ?>
                                <th>Artículo</th>
                                <th>Línea</th>
                                <th>Precio</th>
                            <?php else: ?>
                                <th class="small-col">ID Pedido</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Artículos</th>
                                <th>Total</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_general = 0;
                        while ($fila = $resultados->fetch_assoc()): 
                            $total_general += $fila['monto_total'] ?? $fila['total'] ?? 0;
                        ?>
                            <tr>
                                <?php if ($tipo_reporte == 'ventas_tienda'): ?>
                                    <td><?= htmlspecialchars($fila['tienda']) ?></td>
                                    <td><?= $fila['total_ventas'] ?></td>
                                    <td>€<?= number_format($fila['monto_total'], 2) ?></td>
                                <?php elseif ($tipo_reporte == 'articulos_vendidos'): ?>
                                    <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                                    <td><?= $fila['cantidad_vendida'] ?></td>
                                    <td>€<?= number_format($fila['monto_total'], 2) ?></td>
                                <?php elseif ($tipo_reporte == 'articulos_deshabilitados'): ?>
                                    <td><?= htmlspecialchars($fila['descripcion']) ?></td>
                                    <td><?= htmlspecialchars($fila['linea']) ?></td>
                                    <td>€<?= number_format($fila['precio'], 2) ?></td>
                                <?php else: ?>
                                    <td class="small-col"><?= str_pad($fila['id_pedido'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($fila['fecha'])) ?></td>
                                    <td><?= htmlspecialchars($fila['nombre'] . ' ' . $fila['apellido']) ?></td>
                                    <td><?= $fila['total_articulos'] ?></td>
                                    <td>€<?= number_format($fila['total'], 2) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <?php if ($tipo_reporte != 'articulos_deshabilitados'): ?>
                <div class="ticket-total">
                    TOTAL GENERAL: €<?= number_format($total_general, 2) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="ticket-footer">
                Reporte generado el <?= date('d/m/Y H:i') ?><br>
                Por el sistema administrativo de Joyeras Suárez
            </div>
            
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Imprimir Reporte
            </button>
        </div>
        <?php elseif (isset($resultados)): ?>
            <div class="notification error">No se encontraron resultados para los filtros seleccionados</div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>