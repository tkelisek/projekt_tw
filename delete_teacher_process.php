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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ucitel_id'])) {
    
    $ucitel_id = (int)$_POST['ucitel_id'];

    if ($ucitel_id === $_SESSION['user_id']) {
        redirectToAdmin('error', 'Nemůžete smazat svůj vlastní účet!');
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // --- 1. Smazání závislostí: Docházkové kódy vytvořené učitelem
        $sql_delete_codes = "DELETE FROM dochazkove_kody WHERE vytvoril_id = :ucitel_id";
        $stmt_codes = $pdo->prepare($sql_delete_codes);
        $stmt_codes->bindParam(':ucitel_id', $ucitel_id, PDO::PARAM_INT);
        $stmt_codes->execute();
        
        // --- 2. Smazání samotného účtu učitele
        $sql_delete_user = "DELETE FROM uzivatele WHERE id = :ucitel_id AND role = 'ucitel'";
        $stmt_user = $pdo->prepare($sql_delete_user);
        $stmt_user->bindParam(':ucitel_id', $ucitel_id, PDO::PARAM_INT);
        
        if ($stmt_user->execute() && $stmt_user->rowCount() > 0) {
            redirectToAdmin('success', 'Učitel a jeho kódy byly úspěšně smazány!');
        } else {
            redirectToAdmin('error', 'Učitel nebyl nalezen nebo smazán.');
        }

    } catch (PDOException $e) {
        error_log("Chyba při mazání učitele: " . $e->getMessage());
        redirectToAdmin('error', 'Chyba databáze při mazání. Zkontrolujte závislosti.');
    }
} else {
    redirectToAdmin('error', 'Neplatný požadavek.');
}
?>