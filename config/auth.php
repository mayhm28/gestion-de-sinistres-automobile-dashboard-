<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Mot de passe commun temporaire pour tous les comptes.
// À remplacer plus tard par un mot de passe individuel (hashé) stocké dans utilisateurs.mot_de_passe.
define('MDP_COMMUN_TEMPORAIRE', 'Biat@2026');

function est_connecte() {
    return isset($_SESSION['user_id']);
}

// $chemin_login : chemin relatif vers auth/login.php depuis la page appelante (ex: "auth/login.php" ou "../auth/login.php")
function exiger_connexion($chemin_login) {
    if (!est_connecte()) {
        header('Location: ' . $chemin_login);
        exit;
    }
}
