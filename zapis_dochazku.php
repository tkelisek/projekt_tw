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
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');

$student_id = $_SESSION['user_id'];
$status = 'ceka'; 

$result_message = '';
$result_class = '';

// --- Logika zpracování POST požadavku ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. Získání a validace ID kurzu (přijímáme ho z pole 'predmet_id' z index.php)
    if (isset($_POST['predmet_id']) && is_numeric($_POST['predmet_id'])) {
        $kurz_id = (int)$_POST['predmet_id']; // Důležité: Tuto hodnotu vložíme do sloupce kurz_id
    } else {
        $status = 'chyba';
        $result_message = '❌ Chyba formuláře: Nebyl zadán platný identifikátor kurzu.';
        $result_class = 'alert-danger';
    }

    // 5. Zápis do databáze (pouze pokud je ID platné)
    if ($status != 'chyba') {
        try {
            // Připojení s portem 8889 pro MAMP
            $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=8889;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            
            // Používáme tabulku student_registrace a sloupce student_id, kurz_id a datum_registrace
            $sql_insert = "INSERT INTO student_registrace (student_id, kurz_id, datum_registrace) 
                           VALUES (:student_id, :kurz_id, NOW())"; 

            // Poznámka: Sloupec 'datum_registrace' nyní slouží pro záznam docházky.
            
            $stmt_insert = $pdo->prepare($sql_insert);
            
          
            $stmt_insert->bindParam(":student_id", $student_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(":kurz_id", $kurz_id, PDO::PARAM_INT);

            if ($stmt_insert->execute()) {
                $status = 'uspech';
                $result_message = '✅ Docházka zaznamenána! Zápis proběhl k datu ' . date('d.m.Y H:i:s') . '.';
                $result_class = 'alert-success';
            } else {
                $status = 'chyba';
                $result_message = '❌ Chyba DB: Nepodařilo se provést vložení záznamu.';
                $result_class = 'alert-danger';
            }

        } catch (PDOException $e) {
            $status = 'chyba';
            error_log("Chyba při zápisu docházky: " . $e->getMessage());
            // Zobrazíme přesnou chybu pro ladění
            $result_message = '❌ Chyba DB/Připojení:' . htmlspecialchars($e->getMessage());
            $result_class = 'alert-danger';
        }
    }
} else {
    $status = 'chyba';
    $result_message = '❌ Chyba přístupu: Tato stránka musí být volána z formuláře.';
    $result_class = 'alert-danger';
}
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
            
            <?php if ($status != 'ceka'): ?>
                <div class="alert <?php echo $result_class; ?> fs-5" role="alert">
                    <?php echo $result_message; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info fs-5" role="alert">
                    Čekání na data. Vraťte se na hlavní stránku.
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary btn-lg">Zpět na přehled</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>