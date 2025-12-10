<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení uživatele</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin-top: 50px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="login-container mx-auto">
        <h2 class="text-center text-white bg-success p-3 rounded-top mb-0">Přihlášení uživatele</h2>
        
        <?php
        if (isset($_GET['error'])) {
            $error = $_GET['error'];
            $message = 'Došlo k chybě při přihlašování.'; // Obecná chyba
            
            // Specifické rozlišení chyb
            if ($error == 'incorrectpassword') {
                $message = '❌ Nesprávné heslo. Zkuste to prosím znovu.';
            } elseif ($error == 'nouser') {
                $message = '❌ Uživatel s tímto e-mailem nebyl nalezen.';
            } elseif ($error == 'emptyfields') {
                $message = '❌ Vyplňte prosím obě pole.';
            } elseif ($error == 'dbconnectionerror') {
                $message = '❌ Chyba připojení k databázi. Zkontrolujte MAMP/Port.';
            } elseif ($error == 'formerror') {
                 $message = '❌ Chyba formuláře. Zkuste to znovu.';
            }
            
            if (!empty($message)) {
                echo '<div class="alert alert-danger mt-3 text-center">' . $message . '</div>';
            }
        }
        ?>
        
        <form action="login_process.php" method="POST" class="mt-3">
            <div class="mb-3">
                <label for="email" class="form-label">Emailová adresa</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="heslo" class="form-label">Heslo</label>
                <input type="password" class="form-control" id="heslo" name="heslo" required>
            </div>
            <button type="submit" name="login-submit" class="btn btn-primary w-100">Přihlásit se</button>
        </form>

        <p class="mt-3 text-center">Ještě nemáte účet? <a href="register.php">Zaregistrujte se zde.</a></p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>