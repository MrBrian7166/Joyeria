<?php
// logout.php - Cierre de sesión seguro

// Iniciamos la sesión
session_start();

// Destruimos todos los datos de la sesión
$_SESSION = array();

// Si se desea destruir la cookie de sesión también
// Nota: ¡Esto destruirá la sesión, no solo los datos de la sesión!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruimos la sesión
session_destroy();

// Redirigimos al usuario a la página de login
header("Location: login.php");
exit;
?>