<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlášení uživatele</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" 
          rel="stylesheet" 
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" 
          crossorigin="anonymous">
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Přihlášení uživatele</h4>
                </div>
                <div class="card-body">
                    <?php 
                    // Příklad, pokud byste chtěli zobrazit zprávu po přesměrování
                    if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                        <div class="alert alert-success" role="alert">
                            Registrace úspěšná! Nyní se můžete přihlásit.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php 
                                if ($_GET['error'] == 'nouser') {
                                    echo "Uživatel s tímto e-mailem nebyl nalezen.";
                                } elseif ($_GET['error'] == 'wrongpassword') {
                                    echo "Nesprávné heslo. Zkuste to znovu.";
                                } else {
                                    echo "Došlo k chybě při přihlašování.";
                                }
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="login_process.php" method="POST">
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Emailová adresa</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="heslo" class="form-label">Heslo</label>
                            <input type="password" class="form-control" id="heslo" name="heslo" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mt-3">Přihlásit se</button>
                    </form>
                    
                    <p class="text-center mt-3">
                        Ještě nemáte účet? <a href="registrace_form.php">Zaregistrujte se zde.</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" 
        crossorigin="anonymous">
</script>
</body>
</html>