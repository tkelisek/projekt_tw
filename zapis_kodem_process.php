<?php
// 1. Spuštění session
session_start();

// 2. Kontrola přihlášení a role!
// Přístup pouze pro studenty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // Přesměrování na login s chybou
    header("Location: login.php?error=unauthorized_student");
    exit();
}

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); // Pro MAMP
define('DB_NAME', 'projekt_tw');

$student_id = $_SESSION['user_id'];
$dochazkovy_kod = null;
$pdo = null;

// Funkce pro přesměrování s chybou zpět na index
function redirectToIndex($error_message) {
    header("Location: index.php?status=error&message=" . urlencode($error_message));
    exit();
}

// Zpracování POST dat
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['dochazkovy_kod'])) {
    
    $dochazkovy_kod = trim(strtoupper($_POST['dochazkovy_kod'])); // Kód na velká písmena
    
    if (empty($dochazkovy_kod)) {
        redirectToIndex("Kód nesmí být prázdný.");
    }
    
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=8889;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ===============================================
        // KROK 1: Ověření docházkového kódu
        // ===============================================
        $sql_kod = "
            SELECT id, predmet_id, expirace, stav
            FROM dochazkove_kody
            WHERE kod = :kod AND stav = 'aktivni' AND expirace > NOW()
        ";
        $stmt_kod = $pdo->prepare($sql_kod);
        $stmt_kod->bindParam(':kod', $dochazkovy_kod, PDO::PARAM_STR);
        $stmt_kod->execute();
        $kod_info = $stmt_kod->fetch(PDO::FETCH_ASSOC);

        if (!$kod_info) {
            redirectToIndex("Kód je neplatný, expirovaný nebo byl již použit.");
        }

        $kurz_id = $kod_info['predmet_id'];
        $dochazkovy_kod_id = $kod_info['id'];


        // KROK 2: Kontrola duplicity zápisu (jednou denně do jednoho kurzu)
    
        $sql_dupl = "
            SELECT COUNT(*) 
            FROM student_registrace 
            WHERE student_id = :student_id 
            AND kurz_id = :kurz_id 
            AND DATE(datum_registrace) = CURDATE()
        ";
        $stmt_dupl = $pdo->prepare($sql_dupl);
        $stmt_dupl->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt_dupl->bindParam(':kurz_id', $kurz_id, PDO::PARAM_INT);
        $stmt_dupl->execute();
        $count = $stmt_dupl->fetchColumn();

        if ($count > 0) {
            redirectToIndex("Docházka pro tento kurz byla již dnes zapsána.");
        }

        
        // KROK 3: Zápis do tabulky student_registrace
        
        $sql_zapis = "
            INSERT INTO student_registrace (student_id, kurz_id) 
            VALUES (:student_id, :kurz_id)
        ";
        $stmt_zapis = $pdo->prepare($sql_zapis);
        $stmt_zapis->bindParam(':student_id', $student_id, PDO::PARAM_INT);
        $stmt_zapis->bindParam(':kurz_id', $kurz_id, PDO::PARAM_INT);
        $stmt_zapis->execute();
        
        

        // Úspěch: Přesměrování zpět na index s úspěchem
        header("Location: index.php?status=success&message=" . urlencode("Docházka úspěšně zapsána pro kurz ID " . $kurz_id . "."));
        exit();

    } catch (PDOException $e) {
        error_log("Chyba zápisu docházky: " . $e->getMessage());
        redirectToIndex("Došlo k chybě při zápisu do databáze.");
    } finally {
        if (isset($pdo)) {
            unset($pdo);
        }
    }
} else {
    // Přímý přístup
    header("Location: index.php");
    exit();
}
?>