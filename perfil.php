<?php
require_once 'check_role.php';
require_once 'MysqlConnector.php';

if ($_SESSION['rol'] != 'cliente') {
    header("Location: index.php");
    exit;
}

$mysql = new MysqlConnector();
$mysql->Connect();

// Obtener datos actuales del cliente
$query = "SELECT c.*, s.usuario, s.correo as correo_seguridad 
          FROM cliente c
          JOIN seguridad s ON c.correo = s.correo
          WHERE s.usuario = ?";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$currentData = $stmt->get_result()->fetch_assoc();

$error = '';
$success = '';

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mysql->connection->begin_transaction();
        
        // 1. Actualizar datos en tabla cliente
        $query = "UPDATE cliente SET 
                 nombre = ?, apellido = ?, correo = ?, 
                 direccion = ?, colonia = ?, ciudad = ?, 
                 estado = ?, pais = ?, codigopostal = ?
                 WHERE idcliente = ?";
        
        $stmt = $mysql->connection->prepare($query);
        $stmt->bind_param(
            "sssssssssi",
            $_POST['nombre'],
            $_POST['apellido'],
            $_POST['correo'],
            $_POST['direccion'],
            $_POST['colonia'],
            $_POST['ciudad'],
            $_POST['estado'],
            $_POST['pais'],
            $_POST['codigopostal'],
            $currentData['idcliente']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar datos personales: " . $stmt->error);
        }
        
        // 2. Actualizar contraseña si se proporcionó
        if (!empty($_POST['password'])) {
            if ($_POST['password'] !== $_POST['confirmar_password']) {
                throw new Exception("Las contraseñas no coinciden");
            }
            
            $hashedPassword = md5($_POST['password']); // En producción usa password_hash()
            $query = "UPDATE seguridad SET clave = ? WHERE usuario = ?";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("ss", $hashedPassword, $_SESSION['user']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar contraseña: " . $stmt->error);
            }
        }
        
        // 3. Actualizar correo en seguridad si cambió
        if ($currentData['correo'] !== $_POST['correo']) {
            $query = "UPDATE seguridad SET correo = ? WHERE usuario = ?";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("ss", $_POST['correo'], $_SESSION['user']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar correo: " . $stmt->error);
            }
        }
        
        $mysql->connection->commit();
        $success = "Perfil actualizado correctamente";
        
        // Actualizar datos locales
        $currentData = array_merge($currentData, $_POST);
        $currentData['correo_seguridad'] = $_POST['correo'];
        
    } catch (Exception $e) {
        $mysql->connection->rollback();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Perfil | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .profile-container {
            max-width: 900px;
            margin: 30px auto;
            padding: 30px;
            background: #FAF0E6;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .profile-title {
            color: #A0522D;
            text-align: center;
            margin-bottom: 30px;
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #DAA520;
            border-radius: 5px;
            background: #FFF;
        }
        .password-section {
            grid-column: span 2;
            background: #FFF5E1;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border: 1px dashed #DAA520;
        }
        .btn-submit {
            background: #DAA520;
            color: #000;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
            grid-column: span 2;
            margin-top: 20px;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .password-section {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="profile-container">
        <h1 class="profile-title">Mi Perfil</h1>
        
        <?php if (!empty($success)): ?>
            <div class="notification success"><?= $success ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="notification error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <!-- Datos Personales -->
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" value="<?= htmlspecialchars($currentData['nombre']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" value="<?= htmlspecialchars($currentData['apellido']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Correo Electrónico *</label>
                    <input type="email" name="correo" value="<?= htmlspecialchars($currentData['correo']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" value="<?= htmlspecialchars($currentData['direccion']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Colonia *</label>
                    <input type="text" name="colonia" value="<?= htmlspecialchars($currentData['colonia']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Ciudad *</label>
                    <input type="text" name="ciudad" value="<?= htmlspecialchars($currentData['ciudad']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Estado *</label>
                    <input type="text" name="estado" value="<?= htmlspecialchars($currentData['estado']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>País *</label>
                    <input type="text" name="pais" value="<?= htmlspecialchars($currentData['pais']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Código Postal *</label>
                    <input type="text" name="codigopostal" value="<?= htmlspecialchars($currentData['codigopostal']) ?>" required>
                </div>
                
                <!-- Sección de Contraseña -->
                <div class="password-section">
                    <h3>Cambiar Contraseña</h3>
                    <p>Dejar en blanco si no deseas cambiar</p>
                    
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="password" placeholder="••••••••">
                        </div>
                        
                        <div class="form-group">
                            <label>Confirmar Contraseña</label>
                            <input type="password" name="confirmar_password" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Guardar Cambios</button>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>