<?php
session_start();
require_once 'MysqlConnector.php';

$mysql = new MysqlConnector();
$mysql->Connect();

$username = $_POST['username'];
$password = $_POST['password'];

$query = "SELECT * FROM seguridad WHERE usuario = ? AND clave = MD5(?)";
$stmt = $mysql->connection->prepare($query);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
    $_SESSION['user'] = $username;
    $_SESSION['rol'] = $userData['rol'];
    
    // Redirigir según rol
    $redirect = ($userData['rol'] == 'admin') ? 'index.php' : 'cliente.php';
    header("Location: $redirect");
} else {
    header("Location: login.php?error=1");
}

$mysql->CloseConnection();
?>