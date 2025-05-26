<?php
session_start();
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'MysqlConnector.php';
    $mysql = new MysqlConnector();
    $mysql->Connect();

    // Datos personales (tabla cliente)
    $datosPersonales = [
        'nombre' => trim($_POST['nombre']),
        'apellido' => trim($_POST['apellido']),
        'correo' => trim($_POST['correo']),
        'direccion' => trim($_POST['direccion']),
        'colonia' => trim($_POST['colonia']),
        'ciudad' => trim($_POST['ciudad']),
        'estado' => trim($_POST['estado']),
        'pais' => trim($_POST['pais']),
        'codigopostal' => trim($_POST['codigopostal'])
    ];

    // Credenciales (tabla seguridad)
    $credenciales = [
        'usuario' => trim($_POST['usuario']),
        'clave' => trim($_POST['clave']),
        'correo' => $datosPersonales['correo'],
        'rol' => 'cliente' // Todos los nuevos registros son clientes por defecto
    ];

    // Validaciones
    if (in_array('', $datosPersonales) || in_array('', $credenciales)) {
        $error = "Todos los campos son obligatorios";
    } elseif (!filter_var($datosPersonales['correo'], FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido";
    } elseif ($credenciales['clave'] !== trim($_POST['confirmar_clave'])) {
        $error = "Las contraseñas no coinciden";
    } else {
        try {
            // Iniciar transacción
            $mysql->connection->begin_transaction();

            // 1. Insertar en tabla cliente
            $queryCliente = "INSERT INTO cliente (nombre, apellido, correo, direccion, colonia, ciudad, estado, pais, codigopostal) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtCliente = $mysql->connection->prepare($queryCliente);
            $stmtCliente->bind_param("sssssssss", ...array_values($datosPersonales));
            $stmtCliente->execute();

            // 2. Insertar en tabla seguridad (con hash MD5 - en producción usar password_hash())
            $querySeguridad = "INSERT INTO seguridad (usuario, clave, correo, rol) VALUES (?, MD5(?), ?, ?)";
            $stmtSeguridad = $mysql->connection->prepare($querySeguridad);
            $stmtSeguridad->bind_param("ssss", $credenciales['usuario'], $credenciales['clave'], $credenciales['correo'], $credenciales['rol']);
            $stmtSeguridad->execute();

            // Confirmar transacción
            $mysql->connection->commit();
            $success = "Registro exitoso. Ahora puedes iniciar sesión.";
        } catch (Exception $e) {
            $mysql->connection->rollback();
            $error = "Error al registrar: " . (strpos($e->getMessage(), 'Duplicate entry') ? "El usuario o correo ya existe" : "Datos inválidos");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: #FAF0E6;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .registration-title {
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #DAA520;
            border-radius: 5px;
        }
        .btn-submit {
            background: #DAA520;
            color: #000;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            grid-column: span 2;
            margin-top: 10px;
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
        }
        .error {
            background: #f8d7da;
            color: #721c24;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <h1 class="registration-title">Registro de Nuevo Cliente</h1>
        
        <?php if ($error): ?>
            <div class="notification error"><?= $error ?></div>
        <?php elseif ($success): ?>
            <div class="notification success"><?= $success ?>
                <p><a href="login.php">Ir a inicio de sesión</a></p>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-grid">
                <!-- Datos Personales -->
                <div class="form-group">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" required>
                </div>
                <div class="form-group">
                    <label>Apellido *</label>
                    <input type="text" name="apellido" required>
                </div>
                <div class="form-group">
                    <label>Correo Electrónico *</label>
                    <input type="email" name="correo" required>
                </div>
                <div class="form-group">
                    <label>Dirección *</label>
                    <input type="text" name="direccion" required>
                </div>
                <div class="form-group">
                    <label>Colonia *</label>
                    <input type="text" name="colonia" required>
                </div>
                <div class="form-group">
                    <label>Ciudad *</label>
                    <input type="text" name="ciudad" required>
                </div>
                <div class="form-group">
                    <label>Estado *</label>
                    <input type="text" name="estado" required>
                </div>
                <div class="form-group">
                    <label>País *</label>
                    <input type="text" name="pais" required>
                </div>
                <div class="form-group">
                    <label>Código Postal *</label>
                    <input type="text" name="codigopostal" required>
                </div>
                
                <!-- Credenciales -->
                <div class="form-group">
                    <label>Usuario *</label>
                    <input type="text" name="usuario" required>
                </div>
                <div class="form-group">
                    <label>Contraseña *</label>
                    <input type="password" name="clave" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Contraseña *</label>
                    <input type="password" name="confirmar_clave" required>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">Registrarse</button>
        </form>
        
        <div class="login-link">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>