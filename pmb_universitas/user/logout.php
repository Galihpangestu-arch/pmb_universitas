<?php
require_once '../config/config.php';

// Hapus semua session
$_SESSION = [];

// Hancurkan session
session_destroy();

// Optional: hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect ke halaman login
header("Location: login.php");
exit;
?>