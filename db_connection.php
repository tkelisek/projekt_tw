<?php
$host = 'localhost'; 
$db   = 'projekt_tw'; 
$user = 'root'; 
$pass = 'root'; 
$charset = 'utf8mb4';
$port = '8889'; 

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset"; 
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);

     // echo "Připojení k databázi projekt_tw bylo úspěšné!"; 
} catch (\PDOException $e) {
     // Vypíše přesnou chybu
     die("Chyba připojení k databázi: " . $e->getMessage());
}
?>