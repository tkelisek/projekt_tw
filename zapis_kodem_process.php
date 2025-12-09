<?php
// 1. Spuštění session
session_start();

// 2. Kontrola, zda je uživatel přihlášen
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 3. Konfigurace databáze
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); // Pro MAMP
define('DB_NAME', 'projekt_tw');

$student_id = $_SESSION['user_id'];
$dochazkovy_kod = '';
$kurz_id = null;
$status = 'chyba'; 

$result_message = '';
$result_class = '';

// --- Logika zpracování POST požadavku ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. Získání a validace dat z formuláře
    if (isset($_POST['dochazkovy_kod'])) {
        $dochazkovy_kod = trim($_POST['dochazkovy_kod']);
    }
    if (isset($_POST['kurz_id']) && is_numeric($_POST['kurz_id'])) {
        $kurz_id = (int)$_POST['kurz_id'];
    }

    if (empty($dochazkovy_kod) || empty($kurz_id)) {
        $result_message = '❌ **Chyba:** Musíte zadat kód a vybrat platný kurz.';
        $result_class = 'alert-danger';
        goto display_result; // Přejdeme rovnou na zobrazení výsledku
    }

    // 5. Zápis do databáze (pouze pokud je ID platné)
    try {
        // Připojení s portem 8889 pro MAMP
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=8889;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 6. KONTROLA KÓDU a PLATNOSTI
        // Hledáme kód, který se shoduje, odpovídá kurzu, a jehož expirace NEVYPRŠELA.
        $sql_check = "SELECT id, predmet_id FROM dochazkove_kody 
                      WHERE kod = :kod 
                      AND predmet_id = :kurz_id 
                      AND expirace > NOW() 
                      AND stav = 'aktivni'"; 
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':kod', $dochazkovy_kod, PDO::PARAM_STR);
        $stmt_check->bindParam(':kurz_id', $kurz_id, PDO::PARAM_INT);
        $stmt_check->execute();
        $kod_row = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($kod_row) {
            $kod_id = $kod_row['id']; // ID záznamu v dochazkove_kody

            // 7. ZÁPIS DOCHÁZKY do tabulky student_registrace
            // Používáme sloupce, které máte v DB (student_id, kurz_id, datum_registrace)
            $sql_insert = "INSERT INTO student_registrace (student_id, kurz_id, datum_registrace) 
                           VALUES (:student_id, :kurz_id, NOW())"; 
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->bindParam(":student_id", $student_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(":kurz_id", $kurz_id, PDO::PARAM_INT);

            if ($stmt_insert->execute()) {
                $status = 'uspech';
                $result_message = '✅ **Zápis úspěšný!** Docházka byla zaznamenána.';
                
                // 8. (Volitelné) OZNAČENÍ KÓDU JAKO POUŽITÉHO nebo Expirace
                // Nastavení expirace na aktuální čas, aby se už nedal použít.
                $sql_update_kod = "UPDATE dochazkove_kody SET stav = 'použito', expirace = NOW() WHERE id = :kod_id";
                $stmt_update = $pdo->prepare($sql_update_kod);
                $stmt_update->bindParam(':kod_id', $kod_id, PDO::PARAM_INT);
                $stmt_update->execute();

            } else {
                $result_message = '❌ **Chyba DB:** Nepodařilo se provést vložení docházky.';
            }
        } else {
            // Kód není platný, vypršela mu platnost nebo neodpovídá kurzu
            $result_message = '❌ **Neplatný kód:** Zadaný kód neexistuje, neodpovídá kurzu, nebo mu vypršela platnost.';
        }

    } catch (PDOException $e) {
        // Chyba připojení nebo DB chyba (např. FOREIGN KEY violation)
        error_log("Chyba při zápisu kódem: " . $e->getMessage());
        $result_message = '❌ **Chyba DB/Připojení:** Detailní hláška: ' . htmlspecialchars($e->getMessage());
    }
} else {
    $result_message = '❌ **Chyba přístupu:** Tato stránka musí být volána z formuláře.';
}

display_result: // Návěští pro goto

// Určení CSS třídy pro zobrazení výsledku
$result_class = ($status == 'uspech' ? 'alert-success' : 'alert-danger');

?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Výsledek zápisu docházky</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">ℹ️ Stav zaznamenání docházky</h4>
        </div>
        <div class="card-body">
            
            <div class="alert <?php echo $result_class; ?> fs-5" role="alert">
                <?php echo $result_message; ?>
            </div>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary btn-lg">Zpět na přehled</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>