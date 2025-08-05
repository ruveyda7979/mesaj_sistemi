<?php
$host = 'localhost';
$db   = 'mesaj_sistemi';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO (PHP Data Objects) ayarları
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Hata modunu exception yapar
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Verileri dizi şeklinde çeker
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Gerçek prepared statement kullanılır
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options); // PDO ile veritabanına bağlanılır
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode()); // Hata varsa detaylı şekilde göster
}
?>
