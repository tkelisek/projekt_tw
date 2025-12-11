<?php
// 1. Spuštění session
session_start();

// 2. Kontrola přihlášení a ROLE!
// Přístup pouze pro Admina
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized_admin");
    exit();
}

// 3. Konfigurace DB
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');
define('DB_PORT', 8889);

$admin_jmeno = $_SESSION['jmeno'] . ' ' . $_SESSION['prijmeni'];
$success_message = '';
$error_message = '';
$seznam_ucitelu = [];

// Zpracování zpráv z URL 
if (isset($_GET['status']) && isset($_GET['message'])) {
    $status = $_GET['status'];
    $message = htmlspecialchars($_GET['message']);
    if ($status == 'success') {
        $success_message = $message;
    } else {
        $error_message = $message;
    }
}

// 4. Připojení k DB a načtení dat
try {
    $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=" . DB_PORT . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Načtení seznamu všech učitelů pro tabulku mazání
    $sql_ucitele = "SELECT id, jmeno, prijmeni, email FROM uzivatele WHERE role = 'ucitel' ORDER BY prijmeni ASC";
    $stmt_ucitele = $pdo->prepare($sql_ucitele);
    $stmt_ucitele->execute();
    $seznam_ucitelu = $stmt_ucitele->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Chyba DB: " . $e->getMessage());
    $error_message = "Chyba při načítání seznamu učitelů z databáze.";
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Administrace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-danger border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold" href="#">SUPER ADMIN PANEL</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <span class="nav-link text-light me-3">Vítejte, <?php echo htmlspecialchars($admin_jmeno); ?></span>
        </li>
        <li class="nav-item">
          <a class="btn btn-warning" href="logout.php">Odhlásit se</a>
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

    <div class="alert alert-secondary" role="alert">
        Tato sekce umožňuje vytvářet účty pro nové učitele, přidávat kurzy a spravovat existující účty.
    </div>
    
    <div class="card shadow mb-5">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">👨‍🏫 Vytvořit účet Učitele</h5>
        </div>
        <div class="card-body">
            <p>Vytvořte nový účet s rolí "učitel". Heslo bude automaticky nastaveno na "welcome123".</p>
            <form action="create_teacher_process.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="jmeno" placeholder="Jméno" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="prijmeni" placeholder="Příjmení" required>
                    </div>
                    <div class="col-md-4">
                        <input type="email" class="form-control" name="email" placeholder="E-mail (např. ucitel@skola.cz)" required>
                    </div>
                    <div class="col-12 mt-4">
                        <button type="submit" class="btn btn-primary w-100">Vytvořit Účet Učitele</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card shadow mb-5">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">➕ Přidat nový kurz</h5>
        </div>
        <div class="card-body">
            <p>Přidejte nový předmět do nabídky kurzů. Použijte zkratku (např. WT, DBS).</p>
            <form action="create_course_process.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="nazev" placeholder="Celý název kurzu (např. Mat. analýza)" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="kod_kurzu" placeholder="Zkratka (např. MA)" maxlength="5" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success w-100">Přidat Kurz</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-5">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">🗑️ Spravovat a Smazat Učitele</h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($seznam_ucitelu)): ?>
                <p class="p-3 mb-0 text-muted">V systému nejsou zapsáni žádní učitelé.</p>
            <?php else: ?>
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Jméno</th>
                            <th>E-mail</th>
                            <th>Akce</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($seznam_ucitelu as $ucitel): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ucitel['jmeno']) . ' ' . htmlspecialchars($ucitel['prijmeni']); ?></td>
                                <td><?php echo htmlspecialchars($ucitel['email']); ?></td>
                                <td>
                                    <form action="delete_teacher_process.php" method="POST" onsubmit="return confirm('Opravdu chcete smazat učitele <?php echo htmlspecialchars($ucitel['jmeno']); ?>? Tím se smažou i jeho kódy!');" style="display:inline;">
                                        <input type="hidden" name="ucitel_id" value="<?php echo $ucitel['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Smazat</button>
                                    </form>
                                </td>
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