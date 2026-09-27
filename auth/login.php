<?php
require '../config/db.php';
require '../config/auth.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    // Seuls les emails au format prenom.nom@assurancesbiat.com.tn sont acceptés
    if (!preg_match('/^[a-z]+\.[a-z]+@assurancesbiat\.com\.tn$/i', $email)) {
        $erreur = "Adresse email invalide. Format attendu : prenom.nom@assurancesbiat.com.tn";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email AND statut = 'Actif'");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $erreur = "Aucun compte actif ne correspond à cet email.";
        } elseif ($mdp !== MDP_COMMUN_TEMPORAIRE) {
            $erreur = "Mot de passe incorrect.";
        } else {
            $_SESSION['user_id']   = $user['id_user'];
            $_SESSION['user_nom']  = $user['nom'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: ../index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Connexion – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="login-page">
  <div class="login-card">

    <div class="login-logo">
      <div class="login-logo-icon">AB</div>
      <div class="login-logo-text">
        <div class="brand">Assurances BIAT</div>
        <div class="sub">Gestion des sinistres automobiles</div>
      </div>
    </div>

    <div class="login-title">Connectez-vous à votre espace</div>

    <?php if ($erreur): ?>
      <div class="alert-bar" style="margin-bottom:14px;">⚠️ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="form-group" style="margin-bottom:14px;">
        <label>Adresse email</label>
        <input class="form-inp" type="email" name="email" placeholder="prenom.nom@assurancesbiat.com.tn"
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required/>
      </div>
      <div class="form-group" style="margin-bottom:22px;">
        <label>Mot de passe</label>
        <input class="form-inp" type="password" name="mot_de_passe" placeholder="••••••••" required/>
      </div>
      <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;padding:12px;">
        🔐 Se connecter
      </button>
    </form>

    <p style="text-align:center;margin-top:18px;font-size:12px;color:var(--gris-texte);">
      Mot de passe oublié ? Contactez votre administrateur.
    </p>
  </div>
</div>
</body>
</html>
