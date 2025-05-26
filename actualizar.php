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
    'tiendas', 'articulos', 'linea_de_articulos', 
    'existencias', 'cliente', 'ventas', 'seguridad'
];

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? 0;

if (!in_array($table, $allowedTables) || empty($id)) {
    die("Parámetros inválidos");
}

$primaryKey = $table === 'seguridad' ? 'id_seguridad' : 'id_' . substr($table, 0, -1);
$query = "SELECT * FROM $table WHERE $primaryKey = ?";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$currentData = $stmt->get_result()->fetch_assoc();

if (!$currentData) {
    die("Registro no encontrado");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateFields = [];
    $params = [];
    $types = '';
    
    // Procesar imagen si es un artículo
    if ($table === 'articulos' && isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        // Validar tipo de imagen
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['imagen']['type'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $error = "Tipo de archivo no permitido. Solo se aceptan JPEG, PNG y GIF.";
        } else {
            // Leer el contenido de la imagen
            $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $updateFields[] = "imagen = ?";
            $params[] = $imagen;
            $types .= 's';
        }
    }
    
    foreach ($_POST as $field => $value) {
        if ($field !== 'table' && $field !== 'id' && array_key_exists($field, $currentData)) {
            // Si estamos actualizando el campo 'clave' de la tabla seguridad
            if ($field === 'clave' && $table === 'seguridad') {
                if (trim($value) === '') {
                    continue; // Si está vacío, no lo actualizamos
                }
                $value = md5($value); // Hashear la nueva clave
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
        
        $stmt = $mysql->connection->prepare($query);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success = "Registro actualizado correctamente";
            // Actualizar $currentData para mostrar los cambios
            $currentData = array_merge($currentData, $_POST);
            
            // Si se actualizó la imagen, actualizar el dato local
            if ($table === 'articulos' && isset($imagen)) {
                $currentData['imagen'] = $imagen;
            }
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
                            </select>

                        <?php elseif (($field === 'id_articulo' || $field === 'id_tienda' || $field === 'idcliente') && in_array($table, ['existencias', 'ventas'])): ?>
                            <select name="<?= $field ?>" required>
                                <?php 
                                $data = ${str_replace('id_', '', $field) . 's'};
                                mysqli_data_seek($data, 0);
                                ?>
                                <?php while ($item = mysqli_fetch_assoc($data)): ?>
                                    <option value="<?= $item[$field] ?>" <?= $value == $item[$field] ? 'selected' : '' ?>>
                                        <?= $item['descripcion'] ?? $item['nombre'] ?>
                                    </option>
                                <?php endwhile; ?>
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