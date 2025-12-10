<?php
// 1. Spuštění session
session_start();

// 2. Kontrola přihlášení a role!
// Přístup pouze pro učitele (Adam Novák)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ucitel') {
    header("Location: login.php?error=unauthorized_access");
    exit();
}

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889); 

$user_id = $_SESSION['user_id'];

function redirectToAdmin($status, $message) {
    header("Location: admin_dashboard.php?status=" . $status . "&message=" . urlencode($message));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validace vstupu
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        redirectToAdmin('error', 'Vyplňte prosím všechna pole.');
    }
    if ($new_password !== $confirm_password) {
        redirectToAdmin('error', 'Nové heslo a potvrzení se neshodují.');
    }
    if (strlen($new_password) < 6) { // Příklad jednoduché kontroly délky
        redirectToAdmin('error', 'Nové heslo musí mít alespoň 6 znaků.');
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // KROK 1: Načtení aktuálního haše z DB
        $sql_fetch = "SELECT heslo FROM uzivatele WHERE id = :user_id";
        $stmt_fetch = $pdo->prepare($sql_fetch);
        $stmt_fetch->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_fetch->execute();
        $user = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            redirectToAdmin('error', 'Uživatel nebyl nalezen.');
        }

        // KROK 2: Ověření starého hesla
        if (!password_verify($current_password, $user['heslo'])) {
            redirectToAdmin('error', 'Zadané staré heslo je nesprávné.');
        }

        // KROK 3: Vytvoření nového haše a aktualizace hesla
        $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql_update = "UPDATE uzivatele SET heslo = :new_hash WHERE id = :user_id";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->bindParam(':new_hash', $new_password_hash);
        $stmt_update->bindParam(':user_id', $user_id, PDO::PARAM_INT);

        if ($stmt_update->execute()) {
            redirectToAdmin('success', 'Heslo bylo úspěšně změněno!');
        } else {
            redirectToAdmin('error', 'Chyba při aktualizaci hesla v databázi.');
        }

    } catch (PDOException $e) {
        error_log("Chyba změny hesla: " . $e->getMessage());
        redirectToAdmin('error', 'Chyba databáze. Zkuste to znovu.');
    }
} else {
    redirectToAdmin('error', 'Neplatný požadavek.');
}
?>