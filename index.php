<?php
// 1. Spuštění session
session_start();

// 2. Kontrola přihlášení a role!
// Přístup pouze pro studenty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    // Pokud je uživatel neprihlášen nebo není student, presmerujeme ho na login
    header("Location: login.php?error=unauthorized_access");
    exit();
}

// ⭐ NASTAVENÍ ČASOVÉ ZÓNY pro správné formátování data
date_default_timezone_set('Europe/Prague'); 

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889); 

$student_id = $_SESSION['user_id'];
$jmeno_studenta = $_SESSION['jmeno'] . ' ' . $_SESSION['prijmeni'];
$kurzy = [];
$historie = []; // Inicializace pole pro historii

// 4. Připojení k DB a načtení dat
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Načtení všech aktivních kurzů pro formulář
    $sql_kurzy = "SELECT id, nazev FROM kurzy WHERE aktivni = 1 ORDER BY nazev ASC";
    $stmt_kurzy = $pdo->prepare($sql_kurzy);
    $stmt_kurzy->execute();
    $kurzy = $stmt_kurzy->fetchAll(PDO::FETCH_ASSOC);
    
    // Načtení historie docházky pro přihlášeného studenta
    $sql_historie = "
        SELECT 
            sr.datum_registrace, 
            k.nazev AS nazev_kurzu 
        FROM student_registrace sr
        JOIN kurzy k ON sr.kurz_id = k.id
        WHERE sr.student_id = :student_id
        ORDER BY sr.datum_registrace DESC
    ";
    $stmt_historie = $pdo->prepare($sql_historie);
    $stmt_historie->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt_historie->execute();
    $historie = $stmt_historie->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Chyba DB na index.php: " . $e->getMessage());
    // Chyba připojení se zobrazí jako prázdný seznam kurzů
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Studentský Docházkový Systém</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Systém docházky</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <span class="nav-link text-dark me-3">Přihlášen: <?php echo htmlspecialchars($jmeno_studenta); ?></span>
        </li>
        <li class="nav-item">
          <a class="btn btn-danger" href="logout.php">Odhlásit se</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    
    <?php
    if (isset($_GET['status']) && isset($_GET['message'])) {
        $status = $_GET['status'];
        $message = htmlspecialchars($_GET['message']);

        // Nastavení barvy alertu
        $alert_class = ($status == 'success') ? 'alert-success' : 'alert-danger';

        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo $message;
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    ?>

    <div class="alert alert-info" role="alert">
        <h4 class="alert-heading">Vítejte, <?php echo htmlspecialchars($jmeno_studenta); ?>!</h4>
        <p>Pro zápis docházky zadejte platný kód, který Vám sdělil přednášející, k odpovídajícímu předmětu.</p>
    </div>

    
    <div class="card shadow mt-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">✍️ Zápis docházky</h5>
        </div>
        <ul class="list-group list-group-flush">
            
            <?php if (empty($kurzy)): ?>
                <li class="list-group-item text-muted">Nebyly nalezeny žádné kurzy. Zkontrolujte připojení k databázi nebo existenci kurzů.</li>
            <?php endif; ?>

            <?php foreach ($kurzy as $kurz): ?>
                <li class="list-group-item">
                    <form action="zapis_kodem_process.php" method="POST" class="d-flex justify-content-between align-items-center">
                        <div class="fw-bold"><?php echo htmlspecialchars($kurz['nazev']); ?></div>
                        
                        <div class="d-flex align-items-center">
                            <input type="hidden" name="kurz_id" value="<?php echo $kurz['id']; ?>">
                            
                            <input type="text" 
                                   class="form-control me-2" 
                                   name="dochazkovy_kod" 
                                   placeholder="Zadejte kód" 
                                   required 
                                   style="width: 200px;">
                                   
                            <button type="submit" class="btn btn-success">Zapsat</button>
                        </div>
                    </form>
                </li>
            <?php endforeach; ?>
            
        </ul>
    </div>
    
    <div class="card shadow mt-5 mb-5">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📜 Historie mé docházky</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($historie)): ?>
                <p class="p-3 mb-0 text-muted">Zatím nemáte zapsanou žádnou docházku.</p>
            <?php else: ?>
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Předmět</th>
                            <th>Datum</th> 
                            <th>Čas</th> 
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historie as $zapis): 
                            
                            $datum_cas = new DateTime($zapis['datum_registrace']);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($zapis['nazev_kurzu']); ?></td>
                                <td><?php echo $datum_cas->format('d. m. Y'); ?></td>
                                <td><?php echo $datum_cas->format('H:i:s'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
</div> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>