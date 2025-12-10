<?php
session_start();
// Kontrola přístupu (Pouze Admin)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized_access");
    exit();
}

// Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889);

function redirectToAdmin($status, $message) {
    header("Location: super_admin_dashboard.php?status=" . $status . "&message=" . urlencode($message));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nazev = trim($_POST['nazev']);
    $kod_kurzu = strtoupper(trim($_POST['kod_kurzu'])); 

    if (empty($nazev) || empty($kod_kurzu)) {
        redirectToAdmin('error', 'Vyplňte prosím název i kód kurzu.');
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Kontrola, zda kurz s daným kódem už neexistuje
        $sql_check = "SELECT id FROM kurzy WHERE kod = :kod_kurzu";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':kod_kurzu', $kod_kurzu);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            redirectToAdmin('error', 'Kurz s kódem ' . $kod_kurzu . ' již existuje!');
        }
        
        // Vložení nového kurzu
        $sql_insert = "INSERT INTO kurzy (nazev, kod, aktivni) VALUES (:nazev, :kod, 1)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindParam(':nazev', $nazev);
        $stmt_insert->bindParam(':kod', $kod_kurzu);
        $stmt_insert->execute();

        redirectToAdmin('success', 'Kurz "' . $nazev . ' (' . $kod_kurzu . ')" byl úspěšně vytvořen!');

    } catch (PDOException $e) {
        error_log("Chyba při vytváření kurzu: " . $e->getMessage());
        redirectToAdmin('error', 'Chyba při ukládání kurzu do databáze.');
    }
} else {
    redirectToAdmin('error', 'Neplatný požadavek.');
}
?>