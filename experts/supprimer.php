<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');
if ($_SESSION['user_role'] !== 'Administrateur') { header('Location: liste.php'); exit; }
$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $pdo->prepare("DELETE FROM experts WHERE id_expert = ?");
    $stmt->execute([$id]);
}
header('Location: liste.php');
exit;
