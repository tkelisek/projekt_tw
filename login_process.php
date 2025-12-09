<?php
// 1. Spuštění session
session_start();

// Zde definujte Vaše databázové údaje
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');

// Funkce pro jednoduché přesměrování s chybou
function redirectToLogin($error_code) {
    header("Location: login.php?error=" . $error_code);
    exit();
}

// Kontrola, zda data přišla metodou POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Získání a čištění dat z formuláře
    $email = trim($_POST['email']);
    // Upozornění: Heslo NEčistíme pomocí htmlspecialchars, protože ho potřebujeme pro ověření.
    $heslo = $_POST['heslo']; 

    // Kontrola, zda jsou pole prázdná
    if (empty($email) || empty($heslo)) {
        redirectToLogin('emptyfields');
    }

    try {
        // 2. Připojení k databázi pomocí PDO
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 3. Příprava SQL dotazu pro načtení uživatele podle emailu
        // Předpokládáme tabulku 'uzivatele' s sloupci 'id', 'email' a 'heslo' (kde je uloženo hašované heslo)
        $sql = "SELECT id, email, heslo FROM uzivatele WHERE email = :email";
        
        if ($stmt = $pdo->prepare($sql)) {
            
            // Vazba parametrů
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = $email;

            // Spuštění dotazu
            if ($stmt->execute()) {
                
                // Kontrola, zda byl nalezen uživatel
                if ($stmt->rowCount() == 1) {
                    
                    if ($row = $stmt->fetch()) {
                        $id = $row['id'];
                        $email_db = $row['email'];
                        $hashed_password = $row['heslo'];

                        // 4. Ověření hesla
                        if (password_verify($heslo, $hashed_password)) {
                            // Heslo je správné, přihlášení úspěšné
                            
                            // 5. Uložení dat do session
                            $_SESSION['loggedin'] = true;
                            $_SESSION['user_id'] = $id;
                            $_SESSION['user_email'] = $email_db;
                            
                            // Přesměrování na chráněný dashboard
                            header("Location: index.php");
                            exit();
                        
                        } else {
                            // Heslo je nesprávné
                            redirectToLogin('wrongpassword');
                        }
                    }
                } else {
                    // Nebyl nalezen uživatel s tímto emailem
                    redirectToLogin('nouser');
                }
            } else {
                // Chyba při spuštění dotazu
                redirectToLogin('dberror');
            }
        }
        
        unset($stmt);
        
    } catch (PDOException $e) {
        // Zde můžete logovat skutečnou chybu $e->getMessage() pro účely ladění
        // Ale uživateli zobrazíme obecnou zprávu
        redirectToLogin('connectionerror');
    }
    
    // Uzavření připojení
    unset($pdo);
    
} else {
    // Pokud někdo přistupuje na login_process.php přímo bez POST dat
    header("Location: login.php");
    exit();
}
?>