<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Registrace uživatele</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 400px; margin: 0 auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%; padding: 10px; margin: 8px 0; display: inline-block; border: 1px solid #ccc; box-sizing: border-box;
        }
        button {
            background-color: #4CAF50; color: white; padding: 14px 20px; margin: 8px 0; border: none; cursor: pointer; width: 100%;
        }
        .error { color: red; font-weight: bold; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Registrace nového uživatele</h2>

    <?php
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
        $message = '';
        if ($error == 'emptyfields') $message = 'Vyplňte prosím všechna pole.';
        elseif ($error == 'invalidemail') $message = 'Zadali jste neplatný formát e-mailu.';
        elseif ($error == 'passwordmismatch') $message = 'Hesla se neshodují.';
        elseif ($error == 'userexists') $message = 'Tento e-mail je již registrován.';
        elseif ($error == 'dberror') $message = 'Došlo k chybě při registraci. Zkuste to později.';
        echo '<p class="error">' . htmlspecialchars($message) . '</p>';
    }
    ?>

    <form action="register_process.php" method="POST">
        
        <label for="jmeno"><b>Jméno</b></label>
        <input type="text" placeholder="Zadejte jméno" name="jmeno" required>

        <label for="prijmeni"><b>Příjmení</b></label>
        <input type="text" placeholder="Zadejte příjmení" name="prijmeni" required>

        <label for="email"><b>E-mail</b></label>
        <input type="email" placeholder="Zadejte e-mail" name="email" required>

        <label for="heslo"><b>Heslo</b></label>
        <input type="password" placeholder="Zadejte heslo" name="heslo" required>
        
        <label for="heslo_znovu"><b>Heslo znovu</b></label>
        <input type="password" placeholder="Zadejte heslo znovu" name="heslo_znovu" required>

        <button type="submit" name="register-submit">Registrovat se</button>
    </form>
    <p>Již máte účet? <a href="login.php">Přihlaste se zde</a>.</p>
</div>

</body>
</html>