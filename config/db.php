<?php
// config/db.php

$host    = 'localhost';
$db      = 'escuela_db';
$user    = 'jhonamarista';
$pass    = 'Amarista*3103#'; // Pon tu contraseña de MySQL si tienes una
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Error en la conexión a la base de datos: " . $e->getMessage());
}
?>