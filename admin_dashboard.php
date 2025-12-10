<?php
// 1. Spuštění session
session_start();

// 2. Kontrola přihlášení a role!
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ucitel') {
    header("Location: login.php?error=unauthorized_access");
    exit();
}

// NASTAVENÍ ČASOVÉ ZÓNY
date_default_timezone_set('Europe/Prague'); 

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889); 

$ucitel_id = $_SESSION['user_id'];
$jmeno_ucitele = $_SESSION['jmeno'] . ' ' . $_SESSION['prijmeni'];
$kurzy = [];
$success_message = '';
$error_message = '';

// Funkce pro přesměrování s kódem
function redirectToAdmin($status, $message) {
    header("Location: admin_dashboard.php?status=" . $status . "&message=" . urlencode($message));
    exit();
}

// 4. Připojení k DB
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Načtení všech aktivních kurzů pro formuláře
    $sql_kurzy = "SELECT id, nazev, kod FROM kurzy WHERE aktivni = 1 ORDER BY nazev ASC";
    $stmt_kurzy = $pdo->prepare($sql_kurzy);
    $stmt_kurzy->execute();
    $kurzy = $stmt_kurzy->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Chyba DB na admin_dashboard.php: " . $e->getMessage());
    $error_message = "Chyba při připojení k databázi: " . $e->getMessage();
}


// 5. LOGIKA PRO VYTVOŘENÍ NOVÉHO KURZU
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['vytvorit_kurz'])) {
    if (!isset($pdo)) { 
        redirectToAdmin('error', 'Chyba připojení k databázi.');
    }
    if (empty($_POST['nazev']) || empty($_POST['kod_kurzu'])) {
        redirectToAdmin('error', 'Vyplňte prosím název i kód kurzu.');
    }

    $nazev = trim($_POST['nazev']);
    $kod_kurzu = strtoupper(trim($_POST['kod_kurzu'])); 

    try {
        // Kontrola duplicity
        $sql_check = "SELECT id FROM kurzy WHERE kod = :kod_kurzu";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->bindParam(':kod_kurzu', $kod_kurzu);
        $stmt_check->execute();

        if ($stmt_check->rowCount() > 0) {
            redirectToAdmin('error', 'Kurz s kódem ' . $kod_kurzu . ' již existuje!');
        }
        
        // Vložení nového kurzu
        $sql_insert = "INSERT INTO kurzy (nazev, kod, aktivni) VALUES (:nazev, :kod, 1)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->bindParam(':nazev', $nazev);
        $stmt_insert->bindParam(':kod', $kod_kurzu);
        $stmt_insert->execute();

        redirectToAdmin('success', 'Kurz "' . $nazev . ' (' . $kod_kurzu . ')" byl úspěšně vytvořen!');

    } catch (PDOException $e) {
        error_log("Chyba při vytváření kurzu: " . $e->getMessage());
        redirectToAdmin('error', 'Chyba při ukládání kurzu do databáze.');
    }
}


// 6. LOGIKA PRO ZOBRAZENÍ PŘEHLEDU DOCHÁZKY
$dochazka_studentu = []; 
$zvoleny_kurz_nazev = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['zobrazit_dochazku'])) {
    
    $zvoleny_kurz_id = (int)$_POST['kurz_id'];

    if (empty($zvoleny_kurz_id)) {
        $error_message = 'Prosím, vyberte kurz pro zobrazení docházky.';
    } else {
        try {
            // Najdeme název kurzu pro zobrazení v nadpisu
            foreach ($kurzy as $k) {
                if ($k['id'] == $zvoleny_kurz_id) {
                    $zvoleny_kurz_nazev = $k['nazev'] . ' (' . $k['kod'] . ')';
                    break;
                }
            }

            // SQL DOTAZ: Načte všechny unikátní zápisy do daného kurzu
            $sql_dochazka = "
                SELECT 
                    u.jmeno, 
                    u.prijmeni, 
                    sr.datum_registrace
                FROM student_registrace sr
                JOIN uzivatele u ON sr.student_id = u.id
                WHERE sr.kurz_id = :kurz_id
                ORDER BY sr.datum_registrace DESC
            ";
            
            $stmt_dochazka = $pdo->prepare($sql_dochazka);
            $stmt_dochazka->bindParam(':kurz_id', $zvoleny_kurz_id, PDO::PARAM_INT);
            $stmt_dochazka->execute();
            $dochazka_studentu = $stmt_dochazka->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Chyba DB zobrazení docházky: " . $e->getMessage());
            $error_message = 'Chyba při načítání dat docházky.';
        }
    }
}


// 7. Zobrazení zpráv z URL (generátor, změna hesla)
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status = $_GET['status'];
    $message = htmlspecialchars($_GET['message']);
    if ($status == 'success') {
        $success_message = $message;
    } else {
        $error_message = $message;
    }
}

// Zobrazení kódu z URL, pokud byl vygenerován
$vygenerovany_kod = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';
$kod_expirace = isset($_GET['exp']) ? htmlspecialchars(urldecode($_GET['exp'])) : '';


?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrace Učitele</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">Administrace Učitele</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <span class="nav-link text-light me-3">Vítejte, <?php echo htmlspecialchars($jmeno_ucitele); ?></span>
        </li>
        <li class="nav-item">
          <a class="btn btn-danger" href="logout.php">Odhlásit se</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>


    <div class="card shadow mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">🔑 Generátor Docházkových Kódů</h5>
        </div>
        <div class="card-body">
            <p>Zde můžete vygenerovat jednorázový kód pro aktuálně probíhající hodinu, který studenti použijí pro zápis docházky.</p>
            
            <?php if ($vygenerovany_kod): ?>
                <div class="alert alert-success mt-3" role="alert">
                    ✅ Kód pro kurz s ID <?php echo htmlspecialchars($_GET['kurz_id'] ?? ''); ?> byl úspěšně vygenerován!
                    <br>
                    Zapište studentům tento kód:<?php echo $vygenerovany_kod; ?>
                    <br>
                    Platí do: <?php echo date('Y-m-d H:i:s', strtotime($kod_expirace)); ?>
                </div>
            <?php endif; ?>

            <form action="generate_code_process.php" method="POST" class="mt-4">
                <div class="card p-3 bg-light">
                    <h6>Vybrat kurz a čas platnosti</h6>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="vyber_kurz" class="form-label">Vybrat kurz:</label>
                            <select name="kurz_id" id="vyber_kurz" class="form-select" required>
                                <option value="">-- Vyberte kurz --</option>
                                <?php foreach ($kurzy as $kurz): ?>
                                    <option value="<?php echo $kurz['id']; ?>">
                                        <?php echo htmlspecialchars($kurz['nazev']); ?> (<?php echo htmlspecialchars($kurz['kod']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="platnost" class="form-label">Platnost kódu (v minutách):</label>
                            <input type="number" name="platnost" id="platnost" class="form-control" value="15" min="5" max="60" required>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-warning w-100">Generovat a Zobrazit Kód</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <div class="card shadow mb-5">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">➕ Vytvořit nový kurz</h5>
        </div>
        <div class="card-body">
            <p>Zde můžete rychle přidat nový předmět do systému.</p>
            <form action="admin_dashboard.php" method="POST">
                <input type="hidden" name="vytvorit_kurz" value="1">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="nazev" placeholder="Celý název kurzu (např. Databázové systémy)" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="kod_kurzu" placeholder="Zkratka (např. DBS)" maxlength="5" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100">Vytvořit Kurz</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-5">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">🔒 Změna hesla</h5>
        </div>
        <div class="card-body">
            <form action="change_password_process.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label for="current_password" class="form-label">Staré heslo</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="new_password" class="form-label">Nové heslo</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Potvrzení nového hesla</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-secondary w-100">Změnit Heslo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-5">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">📊 Přehled Docházky Studentů</h5>
        </div>
        <div class="card-body">
            
            <form action="admin_dashboard.php" method="POST" class="mb-4">
                <input type="hidden" name="zobrazit_dochazku" value="1">
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="vyber_kurz_dochazka" class="form-label">Vybrat kurz pro přehled:</label>
                        <select name="kurz_id" id="vyber_kurz_dochazka" class="form-select" required>
                            <option value="">-- Vyberte kurz --</option>
                            <?php foreach ($kurzy as $kurz): ?>
                                <option value="<?php echo $kurz['id']; ?>">
                                    <?php echo htmlspecialchars($kurz['nazev']); ?> (<?php echo htmlspecialchars($kurz['kod']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <button type="submit" class="btn btn-danger w-100">Zobrazit Docházku</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($dochazka_studentu)): ?>
                <h6 class="mt-4">Docházka pro kurz: <?php echo htmlspecialchars($zvoleny_kurz_nazev); ?></h6>
                <table class="table table-striped table-bordered mt-3">
                    <thead>
                        <tr>
                            <th>Jméno a Příjmení</th>
                            <th>Datum</th>
                            <th>Čas Zápisu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dochazka_studentu as $zapis): 
                            // Konverze data a času pro české formátování
                            $datum_cas = new DateTime($zapis['datum_registrace']);
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($zapis['jmeno']) . ' ' . htmlspecialchars($zapis['prijmeni']); ?></td>
                                <td><?php echo $datum_cas->format('d. m. Y'); ?></td>
                                <td><?php echo $datum_cas->format('H:i:s'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p class="text-muted">Celkem <?php echo count($dochazka_studentu); ?>zapsaných docházek.</p>

            <?php elseif (isset($_POST['zobrazit_dochazku'])): ?>
                 <div class="alert alert-warning mt-3">
                     Pro zvolený kurz nebyly nalezeny žádné záznamy docházky.
                 </div>
            <?php endif; ?>
        </div>
    </div>
    
</div> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>