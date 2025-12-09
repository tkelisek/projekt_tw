<?php
// 1. Spustíme session
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
$user_id = $_SESSION['user_id'];
$jmeno_uzivatele = 'Student'; // Záložní jméno
$pdo = null; // Inicializace PDO

// 4. Připojení k databázi a načtení jména a kurzů
$kurzy = [];
try {
    // Připojení s portem 8889 pro MAMP
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=8889;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Získání jména a příjmení studenta
    $sql_user = "SELECT jmeno, prijmeni FROM uzivatele WHERE id = :id"; 
    $stmt_user = $pdo->prepare($sql_user);
    $stmt_user->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt_user->execute();
    
    if ($row = $stmt_user->fetch(PDO::FETCH_ASSOC)) {
        $jmeno_uzivatele = htmlspecialchars($row['jmeno']) . ' ' . htmlspecialchars($row['prijmeni']);
    }

    // ⭐ 5. Načtení seznamu aktivních kurzů z tabulky KURZY
    $sql_kurzy = "SELECT id, nazev FROM kurzy WHERE aktivni = 1 ORDER BY nazev ASC";
    $stmt_kurzy = $pdo->prepare($sql_kurzy);
    $stmt_kurzy->execute();
    $kurzy = $stmt_kurzy->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Chyba databáze na index.php: " . $e->getMessage());
    $jmeno_uzivatele = 'CHYBA DATABÁZE';
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Školní docházkový systém</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Systém docházky</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <span class="nav-link text-white me-3">Přihlášen: **<?php echo $jmeno_uzivatele; ?>**</span>
        </li>
        <li class="nav-item">
          <a class="btn btn-danger" href="logout.php">Odhlásit se</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    
    <div class="alert alert-info shadow-sm" role="alert">
        <h4 class="alert-heading">Vítejte, **<?php echo $jmeno_uzivatele; ?>**! 📘</h4>
        <p class="mb-0">Pro zápis docházky zadejte platný kód, který Vám sdělil přednášející, k odpovídajícímu předmětu.</p>
    </div>

    <div class="card shadow mt-5">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">🔑 Zápis docházky</h5>
        </div>
        <div class="card-body">
            
            <?php if (empty($kurzy)): ?>
                <div class="alert alert-warning">
                    Nebyly nalezeny žádné aktivní kurzy nebo došlo k chybě připojení k databázi.
                    Zkontrolujte tabulku `kurzy` a připojovací údaje v PHP.
                </div>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach ($kurzy as $kurz): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            
                            <span class="fw-bold me-3 text-nowrap"><?php echo htmlspecialchars($kurz['nazev']); ?></span>

                            <form action="zapis_kodem_process.php" method="POST" class="d-flex flex-grow-1 justify-content-end">
                                
                                <input type="hidden" name="kurz_id" value="<?php echo $kurz['id']; ?>">
                                
                                <input type="text" class="form-control me-2" name="dochazkovy_kod" 
                                       placeholder="Zadejte kód pro <?php echo htmlspecialchars($kurz['nazev']); ?>" 
                                       required maxlength="10" style="max-width: 250px;">
                                
                                <button type="submit" class="btn btn-success text-nowrap">Zapsat</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>
    </div>
    
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>