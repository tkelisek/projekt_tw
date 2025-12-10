<?php
// Zde definujte Vaše databázové údaje
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', 'root'); 
define('DB_NAME', 'projekt_tw');

// Funkce pro jednoduché přesměrování s chybou
function redirectToRegister($error_code) {
    header("Location: register.php?error=" . $error_code);
    exit();
}

// Inicializace proměnných pro PDO
$pdo = null;
$stmt_check = null;
$stmt_insert = null;

// Kontrola, zda byl formulář odeslán
if (isset($_POST['register-submit'])) {
    
    // Získání a čištění dat - PŘIDÁNO Jméno a Příjmení
    $jmeno = trim($_POST['jmeno']);
    $prijmeni = trim($_POST['prijmeni']);
    $email = trim($_POST['email']);
    $heslo = $_POST['heslo'];
    $heslo_znovu = $_POST['heslo_znovu']; 

    // --- 1. Validace dat ---
    
    // 1.1 Kontrola prázdných polí (Nyní kontroluje i Jméno a Příjmení)
    if (empty($jmeno) || empty($prijmeni) || empty($email) || empty($heslo) || empty($heslo_znovu)) {
        redirectToRegister('emptyfields');
    }
    
    // 1.2 Kontrola formátu emailu
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectToRegister('invalidemail');
    }
    
    // 1.3 Kontrola shody hesel
    if ($heslo !== $heslo_znovu) {
        redirectToRegister('passwordmismatch');
    }

    // --- 2. Kontrola existence uživatele a připojení k DB ---
    
    try {
        // Připojení k databázi (S portem 8889 pro MAMP - pokud používáte jiný, upravte port)
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";port=8889;dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL dotaz pro kontrolu, zda email již existuje
        $sql_check = "SELECT id FROM uzivatele WHERE email = :email";
        
        if ($stmt_check = $pdo->prepare($sql_check)) {
            $stmt_check->bindParam(":email", $param_email, PDO::PARAM_STR);
            $param_email = $email;

            if ($stmt_check->execute()) {
                
                // 2.1 Uživatel již existuje
                if ($stmt_check->rowCount() > 0) {
                    redirectToRegister('userexists');
                } else {
                    
                    // --- 3. Uložení nového uživatele ---
                    
                    // 3.1 Hašování hesla
                    $hashed_password = password_hash($heslo, PASSWORD_DEFAULT);

                    // SQL dotaz pro vložení nového uživatele - OPRAVA: Přidáno jmeno a prijmeni
                    $sql_insert = "INSERT INTO uzivatele (jmeno, prijmeni, email, heslo) 
                                   VALUES (:jmeno, :prijmeni, :email, :heslo)";
                    
                    if ($stmt_insert = $pdo->prepare($sql_insert)) {
                        
                        
                        $stmt_insert->bindParam(":jmeno", $param_jmeno, PDO::PARAM_STR);
                        $stmt_insert->bindParam(":prijmeni", $param_prijmeni, PDO::PARAM_STR);
                        $stmt_insert->bindParam(":email", $param_email, PDO::PARAM_STR);
                        $stmt_insert->bindParam(":heslo", $param_heslo, PDO::PARAM_STR);
                        
                        // Nastavení hodnot pro vazbu
                        $param_jmeno = $jmeno;
                        $param_prijmeni = $prijmeni;
                        $param_email = $email; // Důležité: Přidáno nastavení $param_email pro insert!
                        $param_heslo = $hashed_password;

                        // 3.2 Spuštění dotazu
                        if ($stmt_insert->execute()) {
                            // Registrace úspěšná, přesměrování na přihlašovací stránku
                            header("Location: login.php?registration=success");
                            exit();
                        } else {
                            redirectToRegister('dberror');
                        }
                    } else {
                        redirectToRegister('dberror');
                    }
                }
            } else {
                redirectToRegister('dberror');
            }
        } else {
            redirectToRegister('dberror');
        }
        
    } catch (PDOException $e) {
        // Tato chyba se již neobjeví, protože jmeno bude odesláno
        die("Chyba připojení k databázi: " . $e->getMessage()); 
        
    } finally {
        // Úklidové operace
        if (isset($stmt_check)) {
            unset($stmt_check);
        }
        if (isset($stmt_insert)) {
            unset($stmt_insert);
        }
        
        // Uzavření připojení k DB
        if (isset($pdo)) {
            unset($pdo);
        }
    }
    
} else {
    // Přímý přístup bez odeslání formuláře
    header("Location: register.php");
    exit();
}
?>