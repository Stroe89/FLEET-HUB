<?php
session_start();

// Distruge toate datele din sesiune
$_SESSION = array();

// Șterge cookie-ul de sesiune dacă există
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distruge sesiunea
session_destroy();

// Setează un mesaj de confirmare
session_start();
$_SESSION['success_message'] = 'Te-ai deconectat cu succes!';

// Redirecționează la pagina de login
header('Location: login.php');
exit();
?>
