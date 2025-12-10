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

// Heslo pro "welcome123" je:
$DEFAULT_PASSWORD = 'welcome123';
$PASSWORD_HASH = '$2y$10$7vN5h3ZgX9L6Y.R7gK8L2uV4jN0c0l4k4D0f1e0G4/5Q/A.l4sR2vR/L.l1.QxQ/A.l4sR2vR/L.l1.Q'; 

function redirectToAdmin($status, $message) {
    header("Location: super_admin_dashboard.php?status=" . $status . "&message=" . urlencode($message));
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $jmeno = trim($_POST['jmeno']);
    $prijmeni = trim($_POST['prijmeni']);
    $email = trim($_POST['email']);
    $role = 'ucitel'; 

    if (empty($jmeno) || empty($prijmeni) || empty($email)) {
        redirectToAdmin('error', 'Vyplňte prosím všechna pole.');
    }

    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Kontrola, zda uživatel s tímto e-mailem již neexistuje
        $sql_check = "SELECT id FROM uzivatele WHERE email = :email";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':email', $email);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            redirectToAdmin('error', 'Účet s e-mailem ' . $email . ' již existuje!');
        }

        // Vložení nového učitele
        $sql_insert = "
            INSERT INTO uzivatele (jmeno, prijmeni, email, heslo, role) 
            VALUES (:jmeno, :prijmeni, :email, :heslo, :role)
        ";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindParam(':jmeno', $jmeno);
        $stmt_insert->bindParam(':prijmeni', $prijmeni);
        $stmt_insert->bindParam(':email', $email);
        $stmt_insert->bindParam(':heslo', $PASSWORD_HASH);
        $stmt_insert->bindParam(':role', $role);
        $stmt_insert->execute();

        redirectToAdmin('success', 'Účet učitele "' . $jmeno . ' ' . $prijmeni . '" byl úspěšně vytvořen s heslem: ' . $DEFAULT_PASSWORD);

    } catch (PDOException $e) {
        error_log("Chyba při vytváření učitele: " . $e->getMessage());
        redirectToAdmin('error', 'Chyba při ukládání do databáze.');
    }
} else {
    redirectToAdmin('error', 'Neplatný požadavek.');
}
?>