<?php
session_start();
require_once 'check_role.php';
require_once 'MysqlConnector.php';
require_once 'vendor/autoload.php'; // Requiere composer y TCPDF

if ($_SESSION['rol'] != 'admin') {
    die("Acceso no autorizado");
}

$mysql = new MysqlConnector();
$mysql->Connect();

// Obtener parámetros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_reporte = $_GET['tipo_reporte'] ?? 'ventas';

// Obtener datos
switch ($tipo_reporte) {
    case 'ventas_tienda':
        $titulo = "VENTAS POR TIENDA";
        $query = "SELECT t.descripcion as tienda, COUNT(v.id_venta) as total_ventas, 
                 SUM(a.precio * d.cantidad) as monto_total
                 FROM ventas v
                 JOIN tiendas t ON v.id_tienda = t.id_tienda
                 JOIN pedido_detalles d ON v.id_pedido = d.id_pedido
                 JOIN articulos a ON d.id_articulo = a.id_articulo
                 WHERE v.fecha BETWEEN ? AND ?
                 GROUP BY t.id_tienda";
        break;
        
    case 'articulos_vendidos':
        $titulo = "ARTÍCULOS VENDIDOS";
        $query = "SELECT a.descripcion, SUM(d.cantidad) as cantidad_vendida, 
                 SUM(d.precio_unitario * d.cantidad) as monto_total
                 FROM pedido_detalles d
                 JOIN articulos a ON d.id_articulo = a.id_articulo
                 JOIN pedidos p ON d.id_pedido = p.id_pedido
                 WHERE p.fecha BETWEEN ? AND ?
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
}

$stmt = $mysql->connection->prepare($query);
if (in_array($tipo_reporte, ['ventas_tienda', 'articulos_vendidos'])) {
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
}
$stmt->execute();
$resultados = $stmt->get_result();

// Crear PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('Joyeras Suárez');
$pdf->SetAuthor('Sistema Administrativo');
$pdf->SetTitle('Reporte - ' . $titulo);
$pdf->AddPage();

// Cabecera
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Joyeras Suárez', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $titulo, 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 10, 'Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)), 0, 1, 'C');
$pdf->Cell(0, 10, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'C');
$pdf->Ln(10);

// Contenido
$pdf->SetFont('helvetica', '', 10);

// Encabezados de tabla
$header = [];
$w = [];
if ($tipo_reporte == 'ventas_tienda') {
    $header = ['Tienda', 'Ventas', 'Monto (€)'];
    $w = [90, 40, 50];
} elseif ($tipo_reporte == 'articulos_vendidos') {
    $header = ['Artículo', 'Cantidad', 'Monto (€)'];
    $w = [100, 40, 40];
} else {
    $header = ['Artículo', 'Línea', 'Precio (€)', 'Características'];
    $w = [60, 40, 30, 60];
}

// Imprimir encabezados
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
}
$pdf->Ln();

// Datos
$total_general = 0;
while ($fila = $resultados->fetch_assoc()) {
    if ($tipo_reporte == 'ventas_tienda') {
        $pdf->Cell($w[0], 6, $fila['tienda'], 'LR');
        $pdf->Cell($w[1], 6, $fila['total_ventas'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, number_format($fila['monto_total'], 2), 'LR', 0, 'R');
        $total_general += $fila['monto_total'];
    } elseif ($tipo_reporte == 'articulos_vendidos') {
        $pdf->Cell($w[0], 6, $fila['descripcion'], 'LR');
        $pdf->Cell($w[1], 6, $fila['cantidad_vendida'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, number_format($fila['monto_total'], 2), 'LR', 0, 'R');
        $total_general += $fila['monto_total'];
    } else {
        $pdf->Cell($w[0], 6, $fila['descripcion'], 'LR');
        $pdf->Cell($w[1], 6, $fila['linea'], 'LR', 0, 'C');
        $pdf->Cell($w[2], 6, number_format($fila['precio'], 2), 'LR', 0, 'R');
        $pdf->Cell($w[3], 6, substr($fila['caracteristicas'], 0, 30) . '...', 'LR');
    }
    $pdf->Ln();
}

// Total general
if (in_array($tipo_reporte, ['ventas_tienda', 'articulos_vendidos'])) {
    $pdf->Cell(array_sum($w) - $w[count($w)-1], 6, 'TOTAL GENERAL', 'LTB', 0, 'R');
    $pdf->Cell($w[count($w)-1], 6, number_format($total_general, 2), 'RTB', 0, 'R');
}

// Salida
$pdf->Output('reporte_' . $tipo_reporte . '_' . date('Ymd') . '.pdf', 'D');
?>