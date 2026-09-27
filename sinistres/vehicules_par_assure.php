<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

header('Content-Type: application/json');

$id_assure = $_GET['id_assure'] ?? null;
if (!$id_assure) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT id_vehicule, immatriculation, marque, modele
     FROM vehicules WHERE id_assure = ? ORDER BY marque"
);
$stmt->execute([$id_assure]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($vehicules);
