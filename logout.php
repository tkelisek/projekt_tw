<?php
// 1. Spustíme session, abychom měli přístup k datům
session_start();

// 2. Odstranění všech proměnných v session
$_SESSION = array();

// Můžeme také zničit session cookie, pokud existuje
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Zničení session
session_destroy();

// 4. Přesměrování na přihlašovací stránku
// Doporučuje se přesměrovat na login.php nebo na úvodní stránku
header("Location: login.php"); 
exit();
?>