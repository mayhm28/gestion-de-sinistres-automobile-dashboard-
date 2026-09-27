<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion PDO à la base assurancesbiat (config par défaut de XAMPP)

$host = "localhost";
$dbname = "assurancesbiat";
$user = "root";
$password = ""; // XAMPP : root sans mot de passe par défaut

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
