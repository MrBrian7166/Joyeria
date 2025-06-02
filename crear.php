<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

include_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

// Procesar envío de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'];
    
    try {
        switch ($table) {
            case 'tiendas':
                $query = "INSERT INTO tiendas (descripcion, ciudad, direccion, codigo_postal, horario) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("sssss", $_POST['descripcion'], $_POST['ciudad'], $_POST['direccion'], 
                                  $_POST['codigo_postal'], $_POST['horario']);
                break;
                
            case 'articulos':
                $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
                $query = "INSERT INTO articulos (idlinea, descripcion, caracteristicas, precio, imagen) 
                          VALUES (?, ?, ?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("issis", $_POST['idlinea'], $_POST['descripcion'], $_POST['caracteristicas'], 
                                  $_POST['precio'], $imagen);
                break;
                
            case 'linea_de_articulos':
                $query = "INSERT INTO linea_de_articulos (descripcion) VALUES (?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("s", $_POST['descripcion']);
                break;
                
            case 'existencias':
                $query = "INSERT INTO existencias (id_articulo, id_tienda, cantidad) VALUES (?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("iii", $_POST['id_articulo'], $_POST['id_tienda'], $_POST['cantidad']);
                break;
                
            case 'cliente':
                $query = "INSERT INTO cliente (nombre, apellido, correo, direccion, colonia, ciudad, estado, pais, codigopostal) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("ssssssssi", $_POST['nombre'], $_POST['apellido'], $_POST['correo'], 
                                 $_POST['direccion'], $_POST['colonia'], $_POST['ciudad'], $_POST['estado'], 
                                 $_POST['pais'], $_POST['codigopostal']);
                break;
                
            case 'ventas':
                $query = "INSERT INTO ventas (id_articulo, idcliente, fecha, id_tienda) VALUES (?, ?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("iisi", $_POST['id_articulo'], $_POST['idcliente'], $_POST['fecha'], $_POST['id_tienda']);
                break;
                
            case 'seguridad':
                $clave = md5($_POST['clave']); // En producción usa password_hash()
                $query = "INSERT INTO seguridad (usuario, clave) VALUES (?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("ss", $_POST['usuario'], $clave);
                break;
        }
        
        $stmt->execute();
        $success = "Registro creado exitosamente en la tabla $table";
    } catch (Exception $e) {
        $error = "Error al crear registro: " . $e->getMessage();
    }
}

// Obtener datos para selects
$lineas = $mysql->ExecuteQuery("SELECT idlinea, descripcion FROM linea_de_articulos");
$articulos = $mysql->ExecuteQuery("SELECT id_articulo, descripcion FROM articulos");
$tiendas = $mysql->ExecuteQuery("SELECT id_tienda, descripcion FROM tiendas");
$clientes = $mysql->ExecuteQuery("SELECT idcliente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM cliente");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Registros - Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <style>
        .tab-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
        }
        .tabs {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 20px;
            gap: 5px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: #FAF0E6;
            border: 1px solid #DAA520;
            cursor: pointer;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .tab-btn:hover {
            background: #DAA520;
        }
        .tab-btn.active {
            background: #DAA520;
            color: #000;
            font-weight: bold;
        }
        .tab-content {
            display: none;
            padding: 20px;
            background: #FAF0E6;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #DAA520;
        }
        .tab-content.active {
            display: block;
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
            padding: 10px;
            border: 1px solid #DAA520;
            border-radius: 5px;
            background: #FFF;
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
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: #FFD700;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="tab-container">
        <h1>Crear Nuevos Registros</h1>
        
        <?php if (isset($success)): ?>
            <div class="notification success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="tabs">
            <button class="tab-btn active" onclick="openTab(event, 'tiendas')">Tiendas</button>
            <button class="tab-btn" onclick="openTab(event, 'articulos')">Artículos</button>
            <button class="tab-btn" onclick="openTab(event, 'lineas')">Líneas de Artículos</button>
            <button class="tab-btn" onclick="openTab(event, 'existencias')">Existencias</button>
            <button class="tab-btn" onclick="openTab(event, 'clientes')">Clientes</button>
            <button class="tab-btn" onclick="openTab(event, 'ventas')">Ventas</button>
            <button class="tab-btn" onclick="openTab(event, 'usuarios')">Usuarios</button>
        </div>

        <!-- Formulario Tiendas -->
        <div id="tiendas" class="tab-content active">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="table" value="tiendas">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Descripción:</label>
                        <input type="text" name="descripcion" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ciudad:</label>
                        <input type="text" name="ciudad" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección:</label>
                        <input type="text" name="direccion" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Código Postal:</label>
                        <input type="number" name="codigo_postal" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Horario:</label>
                        <input type="text" name="horario" placeholder="Ej: Lunes a Viernes 9:00-18:00" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Crear Tienda</button>
            </form>
        </div>

        <!-- Formulario Artículos -->
        <div id="articulos" class="tab-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="table" value="articulos">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Línea de Artículo:</label>
                        <select name="idlinea" required>
                            <?php while ($linea = mysqli_fetch_assoc($lineas)): ?>
                                <option value="<?php echo $linea['idlinea']; ?>">
                                    <?php echo $linea['descripcion']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Descripción:</label>
                        <input type="text" name="descripcion" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Características:</label>
                        <textarea name="caracteristicas" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Precio (€):</label>
                        <input type="number" name="precio" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Imagen:</label>
                        <input type="file" name="imagen" accept="image/*" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Crear Artículo</button>
            </form>
        </div>

        <!-- Formulario Líneas de Artículos -->
        <div id="lineas" class="tab-content">
            <form method="POST">
                <input type="hidden" name="table" value="linea_de_articulos">
                
                <div class="form-group">
                    <label>Descripción de la Línea:</label>
                    <input type="text" name="descripcion" required>
                </div>
                
                <button type="submit" class="btn-submit">Crear Línea</button>
            </form>
        </div>

        <!-- Formulario Existencias -->
        <div id="existencias" class="tab-content">
            <form method="POST">
                <input type="hidden" name="table" value="existencias">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Artículo:</label>
                        <select name="id_articulo" required>
                            <?php mysqli_data_seek($articulos, 0); ?>
                            <?php while ($articulo = mysqli_fetch_assoc($articulos)): ?>
                                <option value="<?php echo $articulo['id_articulo']; ?>">
                                    <?php echo $articulo['descripcion']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Tienda:</label>
                        <select name="id_tienda" required>
                            <?php mysqli_data_seek($tiendas, 0); ?>
                            <?php while ($tienda = mysqli_fetch_assoc($tiendas)): ?>
                                <option value="<?php echo $tienda['id_tienda']; ?>">
                                    <?php echo $tienda['descripcion']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cantidad:</label>
                        <input type="number" name="cantidad" min="0" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Registrar Existencia</button>
            </form>
        </div>

        <!-- Formulario Clientes -->
        <div id="clientes" class="tab-content">
            <form method="POST">
                <input type="hidden" name="table" value="cliente">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nombre:</label>
                        <input type="text" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Apellido:</label>
                        <input type="text" name="apellido" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Correo Electrónico:</label>
                        <input type="email" name="correo" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Dirección:</label>
                        <input type="text" name="direccion" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Colonia:</label>
                        <input type="text" name="colonia" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ciudad:</label>
                        <input type="text" name="ciudad" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Estado:</label>
                        <input type="text" name="estado" required>
                    </div>
                    
                    <div class="form-group">
                        <label>País:</label>
                        <input type="text" name="pais" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Código Postal:</label>
                        <input type="number" name="codigopostal" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Registrar Cliente</button>
            </form>
        </div>

        <!-- Formulario Ventas -->
        <div id="ventas" class="tab-content">
            <form method="POST">
                <input type="hidden" name="table" value="ventas">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Artículo:</label>
                        <select name="id_articulo" required>
                            <?php mysqli_data_seek($articulos, 0); ?>
                            <?php while ($articulo = mysqli_fetch_assoc($articulos)): ?>
                                <option value="<?php echo $articulo['id_articulo']; ?>">
                                    <?php echo $articulo['descripcion']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Cliente:</label>
                        <select name="idcliente" required>
                            <?php mysqli_data_seek($clientes, 0); ?>
                            <?php while ($cliente = mysqli_fetch_assoc($clientes)): ?>
                                <option value="<?php echo $cliente['idcliente']; ?>">
                                    <?php echo $cliente['nombre_completo']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha:</label>
                        <input type="date" name="fecha" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Tienda:</label>
                        <select name="id_tienda" required>
                            <?php mysqli_data_seek($tiendas, 0); ?>
                            <?php while ($tienda = mysqli_fetch_assoc($tiendas)): ?>
                                <option value="<?php echo $tienda['id_tienda']; ?>">
                                    <?php echo $tienda['descripcion']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Registrar Venta</button>
            </form>
        </div>

        <!-- Formulario Usuarios -->
        <div id="usuarios" class="tab-content">
            <form method="POST">
                <input type="hidden" name="table" value="seguridad">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Usuario:</label>
                        <input type="text" name="usuario" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña:</label>
                        <input type="password" name="clave" required>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Crear Usuario</button>
            </form>
        </div>
    </div>

    <script>
        function openTab(evt, tabName) {
            var i, tabcontent, tabbuttons;
            
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            tabbuttons = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].classList.remove("active");
            }
            
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>