<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
$assure = ['nom'=>'','prenom'=>'','cin'=>'','date_naissance'=>'','telephone'=>'','email'=>'','numero_contrat'=>'','date_debut_contrat'=>'','adresse'=>''];
$erreur = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM assures WHERE id_assure = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $assure = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $cin = trim($_POST['cin'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $numero_contrat = trim($_POST['numero_contrat'] ?? '');
    $date_naissance = $_POST['date_naissance'] ?: null;
    $email = trim($_POST['email'] ?? '');
    $date_debut_contrat = $_POST['date_debut_contrat'] ?: null;
    $adresse = trim($_POST['adresse'] ?? '');

    if ($nom === '' || $prenom === '' || $cin === '' || $telephone === '' || $numero_contrat === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE assures SET nom=:nom, prenom=:prenom, cin=:cin, date_naissance=:dn,
                 telephone=:tel, email=:email, numero_contrat=:contrat, date_debut_contrat=:ddc, adresse=:adr
                 WHERE id_assure=:id"
            );
            $stmt->execute([':nom'=>$nom, ':prenom'=>$prenom, ':cin'=>$cin, ':dn'=>$date_naissance,
                ':tel'=>$telephone, ':email'=>$email, ':contrat'=>$numero_contrat, ':ddc'=>$date_debut_contrat,
                ':adr'=>$adresse, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO assures (nom, prenom, cin, date_naissance, telephone, email, numero_contrat, date_debut_contrat, adresse)
                 VALUES (:nom, :prenom, :cin, :dn, :tel, :email, :contrat, :ddc, :adr)"
            );
            $stmt->execute([':nom'=>$nom, ':prenom'=>$prenom, ':cin'=>$cin, ':dn'=>$date_naissance,
                ':tel'=>$telephone, ':email'=>$email, ':contrat'=>$numero_contrat, ':ddc'=>$date_debut_contrat,
                ':adr'=>$adresse]);
        }
        header('Location: liste.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $id ? 'Modifier' : 'Nouvel' ?> assuré – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-logo-icon">AB</div><div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div></div>
  <div class="sb-section">Navigation</div>
  <a href="../index.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
  <a href="../sinistres/liste.php" class="nav-item"><span class="icon">📁</span> Sinistres</a>
  <a href="liste.php" class="nav-item active"><span class="icon">👤</span> Assurés</a>
  <a href="../vehicules/liste.php" class="nav-item"><span class="icon">🚗</span> Véhicules</a>
  <a href="../experts/liste.php" class="nav-item"><span class="icon">🔧</span> Experts</a>
  <a href="../agences/liste.php" class="nav-item"><span class="icon">🏢</span> Agences</a>
  <div class="sb-section">Administration</div>
  <a href="../users/liste.php" class="nav-item"><span class="icon">👥</span> Utilisateurs</a>
  <a href="../alertes/index.php" class="nav-item"><span class="icon">🔔</span> Alertes<span class="nav-badge">3</span></a>
  <a href="../rapports/index.php" class="nav-item"><span class="icon">📄</span> Rapports PDF</a>
  <div class="sb-section">Compte</div>
  <a href="../auth/logout.php" class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
  <div class="sb-bottom"><div class="user-card"><div class="user-avatar"><?= htmlspecialchars(mb_substr($_SESSION['user_nom'],0,1)) ?></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div></div></div>
</aside>
<div class="main">
  <header class="topbar">
    <div class="topbar-title"><?= $id ? 'Modifier' : 'Nouvel' ?> assuré</div>
    <div class="topbar-right"><a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a></div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <a href="liste.php">Assurés</a> › <span><?= $id ? 'Modifier' : 'Nouveau' ?></span></div><h2><?= $id ? 'Modifier' : 'Ajouter' ?> un assuré</h2></div>
    </div>
    <?php if ($erreur): ?>
      <div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <form method="post">
    <div class="card">
      <div class="card-header"><div class="card-title">👤 Informations de l'assuré</div></div>
      <div class="form-section">
        <div class="form-grid">
          <div class="form-group"><label>Nom *</label><input class="form-inp" type="text" name="nom" value="<?= htmlspecialchars($assure['nom']) ?>" required/></div>
          <div class="form-group"><label>Prénom *</label><input class="form-inp" type="text" name="prenom" value="<?= htmlspecialchars($assure['prenom']) ?>" required/></div>
          <div class="form-group"><label>CIN *</label><input class="form-inp" type="text" name="cin" value="<?= htmlspecialchars($assure['cin']) ?>" required/></div>
          <div class="form-group"><label>Date de naissance</label><input class="form-inp" type="date" name="date_naissance" value="<?= htmlspecialchars($assure['date_naissance'] ?? '') ?>"/></div>
          <div class="form-group"><label>Téléphone *</label><input class="form-inp" type="tel" name="telephone" value="<?= htmlspecialchars($assure['telephone']) ?>" required/></div>
          <div class="form-group"><label>Email</label><input class="form-inp" type="email" name="email" value="<?= htmlspecialchars($assure['email'] ?? '') ?>"/></div>
          <div class="form-group"><label>N° Contrat *</label><input class="form-inp" type="text" name="numero_contrat" value="<?= htmlspecialchars($assure['numero_contrat']) ?>" required/></div>
          <div class="form-group"><label>Date début contrat</label><input class="form-inp" type="date" name="date_debut_contrat" value="<?= htmlspecialchars($assure['date_debut_contrat'] ?? '') ?>"/></div>
          <div class="form-group full"><label>Adresse complète</label><input class="form-inp" type="text" name="adresse" value="<?= htmlspecialchars($assure['adresse'] ?? '') ?>"/></div>
        </div>
      </div>
      <div class="form-actions">
        <a href="liste.php" class="btn btn-outline">Annuler</a>
        <button type="submit" class="btn btn-green">💾 Enregistrer</button>
      </div>
    </div>
    </form>
  </main>
</div>
</body>
</html>
