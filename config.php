<?php
// config.php
$host = 'localhost';
$db   = 'vcs_dbb';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Set the default timezone to Manila (Asia/Manila)
date_default_timezone_set('Asia/Manila');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
     throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>