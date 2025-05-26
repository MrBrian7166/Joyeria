<?php
session_start();
require_once 'MysqlConnector.php';

if (!isset($_SESSION['user']) || $_SESSION['rol'] != 'admin') {
    header("Location: login.php");
    exit;
}

$mysql = new MysqlConnector();
$mysql->Connect();

$allowedTables = [
    'tiendas', 'articulos', 'linea_de_articulos', 
    'existencias', 'cliente', 'ventas', 'seguridad'
];

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? 0;

if (!in_array($table, $allowedTables) {
    die("Tabla no permitida");
}

// Obtener clave primaria
$primaryKey = ($table === 'seguridad') ? 'id_seguridad' : 'id_' . substr($table, 0, -1);

// Verificar si el registro existe
$query = "SELECT * FROM $table WHERE $primaryKey = ?";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Registro no encontrado");
}

// Procesar deshabilitación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "UPDATE $table SET activo = 0 WHERE $primaryKey = ?";
    $stmt = $mysql->connection->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: consultar.php?table=$table&disabled=1");
        exit;
    } else {
        $error = "Error al deshabilitar: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Deshabilitar Registro | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .disable-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #FAF0E6;
            border: 2px solid #A0522D;
            border-radius: 10px;
            text-align: center;
        }
        .warning {
            background: #FFF3CD;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 20px;
        }
        .btn-confirm {
            background: #A0522D;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-cancel {
            background: #DAA520;
            color: #000;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="disable-container">
        <h1>Deshabilitar Registro</h1>
        
        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <div class="warning">
            <h3>¿Está seguro de deshabilitar este registro?</h3>
            <p>El registro permanecerá en la base de datos pero no estará visible en las listas.</p>
        </div>

        <form method="POST">
            <div class="btn-group">
                <button type="submit" class="btn-confirm">Confirmar</button>
                <a href="consultar.php?table=<?= $table ?>" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>