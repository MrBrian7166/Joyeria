<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

$allowedTables = [
    'tiendas' => 'id_tienda',
    'articulos' => 'id_articulo',
    'linea_de_articulos' => 'idlinea',
    'existencias' => 'id_existencia',
    'cliente' => 'idcliente',
    'ventas' => 'folio',
    'seguridad' => 'id_seguridad'
];

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? 0;

if (!array_key_exists($table, $allowedTables) || empty($id)) {
    die("Parámetros inválidos");
}

$primaryKey = $allowedTables[$table];
$query = "SELECT * FROM $table WHERE $primaryKey = ?";

// Preparamos la consulta con mejor manejo de errores
if (!$stmt = $mysql->connection->prepare($query)) {
    die("Error al preparar la consulta: " . $mysql->connection->error);
}

if (!$stmt->bind_param("i", $id)) {
    die("Error al vincular parámetros: " . $stmt->error);
}

if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
$currentData = $result->fetch_assoc();

if (!$currentData) {
    die("Registro no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateFields = [];
    $params = [];
    $types = '';
    
    // Procesar imagen si es un artículo
    if ($table === 'articulos' && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['imagen']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y GIF.";
        } else {
            $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $updateFields[] = "imagen = ?";
            $params[] = $imagen;
            $types .= 's';
        }
    }
    
    foreach ($_POST as $field => $value) {
        if ($field !== 'table' && $field !== 'id' && array_key_exists($field, $currentData)) {
            if ($field === 'clave' && $table === 'seguridad') {
                if (trim($value) === '') continue;
                $value = md5($value);
            }

            $updateFields[] = "$field = ?";
            $params[] = $value;
            $types .= is_numeric($value) ? 'i' : 's';
        }
    }
    
    if (!empty($updateFields)) {
        $query = "UPDATE $table SET " . implode(", ", $updateFields) . " WHERE $primaryKey = ?";
        $params[] = $id;
        $types .= 'i';
        
        if (!$stmt = $mysql->connection->prepare($query)) {
            die("Error al preparar la actualización: " . $mysql->connection->error);
        }
        
        if (!$stmt->bind_param($types, ...$params)) {
            die("Error al vincular parámetros: " . $stmt->error);
        }
        
        if ($stmt->execute()) {
            $success = "Registro actualizado correctamente";
            $currentData = array_merge($currentData, $_POST);
            
            if ($table === 'articulos' && isset($imagen)) {
                $currentData['imagen'] = $imagen;
            }
            
            // Actualizar los datos mostrados
            $query = "SELECT * FROM $table WHERE $primaryKey = ?";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $currentData = $result->fetch_assoc();
        } else {
            $error = "Error al actualizar: " . $stmt->error;
        }
    }
}

// Obtener datos para selects
$lineas = $mysql->ExecuteQuery("SELECT idlinea, descripcion FROM linea_de_articulos");
$articulos = $mysql->ExecuteQuery("SELECT id_articulo, descripcion FROM articulos");
$tiendas = $mysql->ExecuteQuery("SELECT id_tienda, descripcion FROM tiendas");
$clientes = $mysql->ExecuteQuery("SELECT idcliente, CONCAT(nombre, ' ', apellido) AS nombre FROM cliente");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Registro - Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .update-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #FAF0E6;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #A0522D;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #DAA520;
            border-radius: 4px;
        }

        .btn-submit {
            background: #DAA520;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            grid-column: span 2;
        }

        .notification {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .success {
            background: #d4edda;
            color: #155724;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="update-container">
        <h1>Actualizar <?= ucfirst(str_replace('_', ' ', $table)) ?></h1>
        
        <?php if (isset($success)): ?>
            <div class="notification success"><?= $success ?></div>
        <?php elseif (isset($error)): ?>
            <div class="notification error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="table" value="<?= $table ?>">
            <input type="hidden" name="id" value="<?= $id ?>">
            
            <div class="form-grid">
                <?php foreach ($currentData as $field => $value): ?>
                    <?php if ($field === $primaryKey) continue; ?>

                    <div class="form-group">
                        <label><?= ucfirst(str_replace('_', ' ', $field)) ?>:</label>

                        <?php if ($field === 'idlinea' && $table === 'articulos'): ?>
                            <select name="idlinea" required>
                                <?php while ($linea = mysqli_fetch_assoc($lineas)): ?>
                                    <option value="<?= $linea['idlinea'] ?>" <?= $value == $linea['idlinea'] ? 'selected' : '' ?>>
                                        <?= $linea['descripcion'] ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php mysqli_data_seek($lineas, 0); ?>
                            </select>

                        <?php elseif (($field === 'id_articulo' && $table === 'existencias') || ($field === 'id_tienda' && $table === 'existencias')): ?>
                            <select name="<?= $field ?>" required>
                                <?php 
                                $data = $field === 'id_articulo' ? $articulos : $tiendas;
                                mysqli_data_seek($data, 0);
                                ?>
                                <?php while ($item = mysqli_fetch_assoc($data)): ?>
                                    <option value="<?= $item[$field] ?>" <?= $value == $item[$field] ? 'selected' : '' ?>>
                                        <?= $item['descripcion'] ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php mysqli_data_seek($data, 0); ?>
                            </select>

                        <?php elseif ($field === 'idcliente' && $table === 'ventas'): ?>
                            <select name="idcliente" required>
                                <?php mysqli_data_seek($clientes, 0); ?>
                                <?php while ($cliente = mysqli_fetch_assoc($clientes)): ?>
                                    <option value="<?= $cliente['idcliente'] ?>" <?= $value == $cliente['idcliente'] ? 'selected' : '' ?>>
                                        <?= $cliente['nombre'] ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php mysqli_data_seek($clientes, 0); ?>
                            </select>

                        <?php elseif ($field === 'clave' && $table === 'seguridad'): ?>
                            <input type="password" name="clave" placeholder="Dejar vacío para no cambiar">

                        <?php elseif ($field === 'imagen' && $table === 'articulos'): ?>
                            <input type="file" name="imagen" accept="image/*">
                            <?php if (!empty($value)): ?>
                                <div>
                                    <p>Imagen actual:</p>
                                    <img src="data:image/jpeg;base64,<?= base64_encode($value) ?>" class="image-preview">
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <input type="<?= is_numeric($value) ? 'number' : 'text' ?>" 
                                   name="<?= $field ?>" 
                                   value="<?= htmlspecialchars($value) ?>"
                                   <?= $field === 'precio' ? 'step="0.01"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>