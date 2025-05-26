<?php
require_once 'check_role.php';
if ($_SESSION['rol'] != 'cliente') {
    header("Location: index.php");
    exit;
}

require_once 'MysqlConnector.php';
$mysql = new MysqlConnector();
$mysql->Connect();

// Obtener ID del cliente
$query = "SELECT idcliente FROM cliente WHERE correo = (SELECT correo FROM seguridad WHERE usuario = ?)";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("s", $_SESSION['user']);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$id_cliente = $cliente['idcliente'];

// Procesar agregar al carrito
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar'])) {
    if (!isset($_SESSION['carrito'])) {
        $_SESSION['carrito'] = [];
    }
    
    $id_articulo = $_POST['id_articulo'];
    $cantidad = $_POST['cantidad'] ?? 1;
    
    // Validar que la cantidad sea positiva
    if ($cantidad <= 0) {
        $_SESSION['error_carrito'] = "La cantidad debe ser al menos 1";
    } else {
        // Verificar si el producto ya está en el carrito
        $cantidad_existente = isset($_SESSION['carrito'][$id_articulo]) ? $_SESSION['carrito'][$id_articulo]['cantidad'] : 0;
        $nueva_cantidad = $cantidad_existente + $cantidad;
        
        // Validar límite de 3 unidades por producto
        if ($nueva_cantidad > 3) {
            $_SESSION['error_carrito'] = "No puedes agregar más de 3 unidades del mismo producto";
        } else {
            // Obtener detalles del artículo
            $query = "SELECT * FROM articulos WHERE id_articulo = ?";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("i", $id_articulo);
            $stmt->execute();
            $articulo = $stmt->get_result()->fetch_assoc();
            
            if ($articulo) {
                $_SESSION['carrito'][$id_articulo] = [
                    'id' => $id_articulo,
                    'descripcion' => $articulo['descripcion'],
                    'precio' => $articulo['precio'],
                    'cantidad' => $nueva_cantidad,
                    'imagen' => base64_encode($articulo['imagen'])
                ];
            }
        }
    }
}

// Procesar eliminar del carrito
if (isset($_GET['eliminar'])) {
    unset($_SESSION['carrito'][$_GET['eliminar']]);
}

// Procesar finalizar pedido 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar'])) {
    if (!empty($_SESSION['carrito'])) {
        try {
            $mysql->connection->begin_transaction();
            
            // 1. Calcular total
            $total = 0;
            foreach ($_SESSION['carrito'] as $item) {
                $total += $item['precio'] * $item['cantidad'];
            }
            
            // 2. Obtener ID del cliente
            $query = "SELECT idcliente FROM cliente WHERE correo = (SELECT correo FROM seguridad WHERE usuario = ?)";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("s", $_SESSION['user']);
            $stmt->execute();
            $cliente = $stmt->get_result()->fetch_assoc();
            $id_cliente = $cliente['idcliente'];
            
            // 3. Crear pedido
            $query = "INSERT INTO pedidos (id_cliente, total) VALUES (?, ?)";
            $stmt = $mysql->connection->prepare($query);
            $stmt->bind_param("id", $id_cliente, $total);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al crear pedido: " . $stmt->error);
            }
            
            $id_pedido = $mysql->connection->insert_id;
            
            // 4. Agregar detalles
            foreach ($_SESSION['carrito'] as $item) {
                $query = "INSERT INTO pedido_detalles (id_pedido, id_articulo, cantidad, precio_unitario) 
                         VALUES (?, ?, ?, ?)";
                $stmt = $mysql->connection->prepare($query);
                $stmt->bind_param("iiid", $id_pedido, $item['id'], $item['cantidad'], $item['precio']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al agregar detalles: " . $stmt->error);
                }
            }
            
            $mysql->connection->commit();
            unset($_SESSION['carrito']);
            
            // Redirigir al ticket
            header("Location: ticket_pedido.php?id=" . $id_pedido);
            exit;
            
        } catch (Exception $e) {
            $mysql->connection->rollback();
            $error = "Error al procesar pedido: " . $e->getMessage();
            error_log($error);
        }
    }
}

// Obtener artículos disponibles
$articulos = $mysql->ExecuteQuery("SELECT * FROM articulos WHERE activo = 1");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Pedido | Joyeras Suárez</title>
    <link rel="stylesheet" href="estilos.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pedido-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 30px auto;
        }
        .articulos-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .articulo-card {
            border: 1px solid #DAA520;
            border-radius: 8px;
            padding: 15px;
            background: #FAF0E6;
        }
        .articulo-img {
            width: 100%;
            height: 200px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .carrito {
            background: #FAF0E6;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #DAA520;
            position: sticky;
            top: 20px;
        }
        .carrito-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #DAA520;
        }
        .btn-eliminar {
            color: #A0522D;
            background: none;
            border: none;
            cursor: pointer;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
            margin: 15px 0;
            text-align: right;
        }
        .btn-confirmar {
            background: #A0522D;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
        }
        .confirmacion-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }
        .error-message {
            color: red;
            padding: 10px;
            background: #FFEEEE;
            border: 1px solid red;
            margin-bottom: 20px;
            max-width: 1200px;
            margin: 20px auto 0;
        }
        .btn-disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .cantidad-info {
            font-size: 0.9em;
            color: #A0522D;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <?php if (!empty($_SESSION['error_carrito'])): ?>
        <div class="error-message">
            <?= $_SESSION['error_carrito'] ?>
            <?php unset($_SESSION['error_carrito']); ?>
        </div>
    <?php endif; ?>

    <div class="pedido-container">
        <!-- Lista de Artículos -->
        <div class="articulos-section">
            <h2>Selecciona tus artículos</h2>
            <p><strong>Límite:</strong> Máximo 3 unidades por producto</p>
            <div class="articulos-list">
                <?php while ($articulo = mysqli_fetch_assoc($articulos)): ?>
                    <div class="articulo-card">
                        <?php if (!empty($articulo['imagen'])): ?>
                            <img src="data:image/jpeg;base64,<?= base64_encode($articulo['imagen']) ?>" class="articulo-img">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($articulo['descripcion']) ?></h3>
                        <p>Precio: €<?= number_format($articulo['precio'], 2) ?></p>
                        <form method="POST">
                            <input type="hidden" name="id_articulo" value="<?= $articulo['id_articulo'] ?>">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="number" name="cantidad" value="1" min="1" max="3" style="width: 60px;">
                                <button type="submit" name="agregar" class="btn-submit" style="padding: 5px 10px;"
                                    <?php 
                                    // Deshabilitar botón si ya hay 3 unidades de este producto
                                    if (isset($_SESSION['carrito'][$articulo['id_articulo']]) && $_SESSION['carrito'][$articulo['id_articulo']]['cantidad'] >= 3) {
                                        echo 'disabled class="btn-submit btn-disabled" title="Límite de 3 unidades alcanzado"';
                                    } else {
                                        echo 'class="btn-submit"';
                                    }
                                    ?>>
                                    <i class="fas fa-cart-plus"></i> Agregar
                                </button>
                            </div>
                            <?php if (isset($_SESSION['carrito'][$articulo['id_articulo']])): ?>
                                <div class="cantidad-info">
                                    En carrito: <?= $_SESSION['carrito'][$articulo['id_articulo']]['cantidad'] ?>/3
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Carrito de Compras -->
        <div class="carrito">
            <h2>Tu Pedido</h2>
            
            <?php if (empty($_SESSION['carrito'])): ?>
                <p>Tu carrito está vacío</p>
            <?php else: ?>
                <?php $total = 0; ?>
                <?php foreach ($_SESSION['carrito'] as $item): ?>
                    <div class="carrito-item">
                        <div>
                            <strong><?= htmlspecialchars($item['descripcion']) ?></strong><br>
                            <?= $item['cantidad'] ?> x €<?= number_format($item['precio'], 2) ?>
                        </div>
                        <div>
                            €<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                            <a href="?eliminar=<?= $item['id'] ?>" class="btn-eliminar">
                                <i class="fas fa-trash-alt"></i>
                            </a>
                        </div>
                    </div>
                    <?php $total += $item['precio'] * $item['cantidad']; ?>
                <?php endforeach; ?>
                
                <div class="total">
                    Total: €<?= number_format($total, 2) ?>
                </div>
                
                <form method="POST" id="pedidoForm">
                    <input type="hidden" name="finalizar" value="1">
                    <button type="button" onclick="confirmarPedido()" class="btn-confirmar">
                        Finalizar Pedido
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de Confirmación -->
    <div id="confirmModal" class="confirmacion-modal">
        <div class="modal-content">
            <h2>Confirmar Pedido</h2>
            <p>¿Estás seguro de que deseas realizar este pedido por €<?= isset($total) ? number_format($total, 2) : '0.00' ?>?</p>
            <p>Esta acción no se puede deshacer.</p>
            <div style="display: flex; justify-content: center; gap: 15px; margin-top: 20px;">
                <button onclick="document.getElementById('confirmModal').style.display='none'" 
                        style="background: #DAA520; padding: 10px 20px; border: none; border-radius: 5px;">
                    Cancelar
                </button>
                <button onclick="document.getElementById('pedidoForm').submit()" 
                        style="background: #A0522D; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                    Confirmar
                </button>
            </div>
        </div>
    </div>

    <script>
        function confirmarPedido() {
            if (confirm("¿Estás seguro de finalizar el pedido?\nEsta acción no se puede deshacer.")) {
                if (confirm("¿Confirmas por última vez que deseas realizar este pedido?")) {
                    document.getElementById('pedidoForm').submit();
                }
            }
        }

        // Actualizar estado de los botones al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.articulo-card form').forEach(form => {
                const idArticulo = form.querySelector('input[name="id_articulo"]').value;
                const cantidadActual = <?= isset($_SESSION['carrito']) ? json_encode($_SESSION['carrito']) : '{}' ?>[idArticulo]?.cantidad || 0;
                
                if (cantidadActual >= 3) {
                    const button = form.querySelector('button[name="agregar"]');
                    button.disabled = true;
                    button.classList.add('btn-disabled');
                    button.title = 'Límite de 3 unidades alcanzado';
                    
                    const input = form.querySelector('input[name="cantidad"]');
                    input.disabled = true;
                }
            });
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>