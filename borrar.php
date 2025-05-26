<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

// Tablas permitidas
$allowedTables = [
    'tiendas', 'articulos', 'linea_de_articulos', 
    'existencias', 'cliente', 'ventas', 'seguridad'
];

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? 0;

// Validaciones
if (!in_array($table, $allowedTables) || empty($id)) {
    die("Parámetros inválidos");
}
if (empty($_GET['id'])) {
    header("Location: consultar.php?table=" . ($_GET['table'] ?? 'articulos'));
    exit;
}

// Obtener clave primaria
$primaryKey = $table === 'seguridad' ? 'id_seguridad' : 'id_' . substr($table, 0, -1);

// Procesar eliminación si se confirma
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query = "DELETE FROM $table WHERE $primaryKey = ?";
    $stmt = $mysql->connection->prepare($query);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: consultar.php?table=$table&deleted=1");
        exit;
    } else {
        $error = "Error al eliminar: " . $stmt->error;
    }
}

// Obtener datos para mostrar información
$query = "SELECT * FROM $table WHERE $primaryKey = ? LIMIT 1";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    die("Registro no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Eliminar Registro - Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #FAF0E6;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .warning-message {
            background: #FFF3CD;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        
        .data-preview {
            text-align: left;
            margin: 20px 0;
            padding: 15px;
            background: #FFF;
            border-radius: 5px;
        }
        
        .btn-group {
            margin-top: 30px;
            display: flex;
            justify-content: center;
            gap: 15px;
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
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="delete-container">
        <h1>Eliminar Registro</h1>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="warning-message">
            <h3>¿Está seguro que desea eliminar este registro?</h3>
            <p>Esta acción no se puede deshacer.</p>
        </div>
        
        <div class="data-preview">
            <h3>Detalles del registro:</h3>
            <ul>
                <?php foreach ($data as $field => $value): ?>
                    <?php if ($field === $primaryKey) continue; ?>
                    <li><strong><?= ucfirst(str_replace('_', ' ', $field)) ?>:</strong> 
                        <?= htmlspecialchars(substr($value, 0, 100)) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <form method="POST">
            <div class="btn-group">
                <button type="submit" class="btn-confirm">Confirmar Eliminación</button>
                <a href="consultar.php?table=<?= $table ?>" class="btn-cancel">Cancelar</a>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>