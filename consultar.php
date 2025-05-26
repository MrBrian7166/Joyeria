<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

// Tablas permitidas para consulta
$allowedTables = [
    'tiendas' => 'Tiendas',
    'articulos' => 'Artículos',
    'linea_de_articulos' => 'Líneas de Artículos',
    'cliente' => 'Clientes',
    'ventas' => 'Ventas',
    'seguridad' => 'Usuarios'
];

$selectedTable = $_GET['table'] ?? 'tiendas';
$searchTerm = $_POST['search'] ?? '';

// Validar tabla seleccionada
if (!array_key_exists($selectedTable, $allowedTables)) {
    die("Tabla no válida");
}

// Obtener datos
$query = "SELECT * FROM $selectedTable";
$params = [];
$types = '';

if (!empty($searchTerm)) {
    $columns = [];
    $result = $mysql->ExecuteQuery("DESCRIBE $selectedTable");
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'] . " LIKE ?";
        $params[] = "%$searchTerm%";
        $types .= 's';
    }
    $query .= " WHERE " . implode(" OR ", $columns);
}

$stmt = $mysql->connection->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Obtener nombres de columnas
$columns = [];
if ($result->num_rows > 0) {
    $firstRow = $result->fetch_assoc();
    $columns = array_keys($firstRow);
    $result->data_seek(0);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultar Registros - Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .consultar-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }

        .table-selector {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .search-box {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: #FAF0E6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .data-table th {
            background: #DAA520;
            color: #000;
            padding: 12px;
            text-align: left;
        }

        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #EEE8AA;
        }

        .data-table tr:hover {
            background: #FFF5E1;
        }

        .btn-accion {
            padding: 5px 10px;
            border-radius: 3px;
            text-decoration: none;
            margin: 0 2px;
            font-size: 0.9em;
        }

        .btn-editar {
            background: #B8860B;
            color: white;
        }

        .btn-eliminar {
            background: #A0522D;
            color: white;
        }

        .no-results {
            padding: 20px;
            background: #FFF5E1;
            text-align: center;
            color: #A0522D;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="consultar-container">
        <h1>Consultar Registros</h1>
        
        <div class="table-selector">
            <form method="get">
                <select name="table" onchange="this.form.submit()">
                    <?php foreach ($allowedTables as $key => $value): ?>
                        <option value="<?= $key ?>" <?= $selectedTable === $key ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <form method="post" class="search-box">
                <input type="text" name="search" placeholder="Buscar..." value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="btn-submit">Buscar</button>
            </form>
        </div>

        <?php if ($result->num_rows > 0): ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php foreach ($columns as $column): ?>
                                <th><?= ucfirst(str_replace('_', ' ', $column)) ?></th>
                            <?php endforeach; ?>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <?php foreach ($row as $key => $value): ?>
                                    <td>
                                        <?php if ($key === 'imagen' && !empty($value)): ?>
                                            <img src="data:image/jpeg;base64,<?= base64_encode($value) ?>" width="50">
                                        <?php else: ?>
                                            <?= htmlspecialchars(substr($value, 0, 50)) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="actualizar.php?table=<?= $selectedTable ?>&id=<?= $row[$columns[0]] ?>" class="btn-accion btn-editar">Editar</a>
                                    
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="no-results">
                No se encontraron registros en esta tabla.
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>