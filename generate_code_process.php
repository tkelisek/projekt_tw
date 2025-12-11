<?php
//  OPRAVA: Nastavení časové zóny, aby se NOW() shodovalo s expirací
date_default_timezone_set('Europe/Prague'); 

// 1. Spustíme session
session_start();

// 2. Kontrola přihlášení a role!
// Přístup pouze pro učitele
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ucitel') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889); 

$ucitel_id = $_SESSION['user_id'];
$kurz_id = null;
$platnost_minut = 15; // Výchozí hodnota
$pdo = null;

// Funkce pro generování náhodného kódu (např. 6 alfanumerických znaků)
function generateRandomCode($length = 6) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $code;
}

// Zpracování POST dat
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Získání a validace dat
    if (isset($_POST['kurz_id']) && is_numeric($_POST['kurz_id'])) {
        $kurz_id = (int)$_POST['kurz_id'];
    }
    
    // Vynucení celé hodnoty, aby se zajistil správný výpočet
    if (isset($_POST['platnost']) && is_numeric($_POST['platnost'])) {
        $platnost_minut = (int)$_POST['platnost'];
    } else {
        // Pokud by z nějakého důvodu chyběla hodnota, použijeme 15 minut
        $platnost_minut = 15;
    }

    if (empty($kurz_id) || $platnost_minut < 1 || $platnost_minut > 60) {
        header("Location: admin_dashboard.php?status=error&message=Neplatný výběr kurzu nebo platnosti (pouze 1-60 min).");
        exit();
    }

    // Generování unikátního kódu a času expirace
    $novy_kod = generateRandomCode(6);
    // Vytvoříme čas expirace (aktuální čas + správný počet minut)
    $expirace = date('Y-m-d H:i:s', strtotime("+" . $platnost_minut . " minutes"));
    
    // --- 4. Zápis do databáze ---
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL dotaz pro vložení nového kódu
        $sql_insert = "
            INSERT INTO dochazkove_kody 
            (predmet_id, kod, expirace, vytvoril_id, stav) 
            VALUES (:predmet_id, :kod, :expirace, :vytvoril_id, 'aktivni')
        ";
        
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindParam(":predmet_id", $kurz_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(":kod", $novy_kod, PDO::PARAM_STR);
        $stmt_insert->bindParam(":expirace", $expirace, PDO::PARAM_STR);
        $stmt_insert->bindParam(":vytvoril_id", $ucitel_id, PDO::PARAM_INT);

        if ($stmt_insert->execute()) {
            // Úspěch: Přesměrování zpět na dashboard s vygenerovaným kódem a expirací
            header("Location: admin_dashboard.php?status=success&code=" . $novy_kod . "&kurz_id=" . $kurz_id . "&exp=" . urlencode($expirace));
            exit();
        } else {
            // Chyba zápisu
            header("Location: admin_dashboard.php?status=error&message=Chyba při zápisu kódu do DB.");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Chyba generování kódu: " . $e->getMessage());
        header("Location: admin_dashboard.php?status=error&message=Chyba připojení nebo SQL dotazu. Detail: " . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Přímý přístup bez POST požadavku
    header("Location: admin_dashboard.php");
    exit();
}
?>