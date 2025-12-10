<?php
// 1. Spuštění session
session_start();


define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889); 

// Funkce pro přesměrování s chybovým kódem zpět na login formulář
function redirectToLogin($error_code) {
    header("Location: login.php?error=" . $error_code);
    exit();
}

// Kontrola, zda byl formulář odeslán přes POST
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
    
    // Získání a čištění dat
    $email = trim($_POST['email']);
    $heslo = $_POST['heslo'];

    // --- 1. Základní Validace ---
    if (empty($email) || empty($heslo)) {
        redirectToLogin('emptyfields');
    }

    // --- 2. Připojení k DB a ověření uživatele ---
    try {
        // Připojení k databázi
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL KONTROLA: Načítáme VŠECHNY potřebné sloupce
        $sql = "SELECT id, heslo, role, jmeno, prijmeni FROM uzivatele WHERE email = :email";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);

        if ($stmt->execute()) {
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                
                // KONTROLA HESLA
                if (password_verify($heslo, $row['heslo'])) {
                    
                    // --- 3. PŘIHLÁŠENÍ ÚSPĚŠNÉ: Zápis do session ---
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['role'] = $row['role']; 
                    $_SESSION['jmeno'] = $row['jmeno']; 
                    $_SESSION['prijmeni'] = $row['prijmeni']; 
                    
                    // --- 4. Přesměrování dle role ---
                    if ($_SESSION['role'] === 'student') {
                        // Přesměrování na dashboard studenta
                        header("Location: index.php"); 
                    } elseif ($_SESSION['role'] === 'ucitel') {
                        // Přesměrování na dashboard učitele
                        header("Location: admin_dashboard.php");
                    } elseif ($_SESSION['role'] === 'admin') {
                        // PŘESMĚROVÁNÍ PRO SUPER ADMINA
                        header("Location: super_admin_dashboard.php");
                    } else {
                        // Neznámá nebo nezpracovaná role
                        header("Location: login.php?error=unknown_role"); 
                    }
                    exit();
                    
                } else {
                    // CHYBA HESLA
                    redirectToLogin('incorrectpassword');
                }
            } else {
                // CHYBA E-MAILU (Uživatel nenalezen)
                redirectToLogin('nouser');
            }
        } else {
            // Chyba v provedení dotazu
            redirectToLogin('dberror');
        }
        
    } catch (PDOException $e) {
        // Chyba připojení k databázi
        error_log("Chyba DB: " . $e->getMessage());
        redirectToLogin('dbconnectionerror');
        
    } finally {
        // Uzavření spojení
        if (isset($pdo)) {
            unset($pdo);
        }
    }
    
} else {
    // Přímý přístup nebo chybějící data formuláře
    redirectToLogin('formerror');
}
?>