<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
$ag = ['code'=>'','nom'=>'','ville'=>'','responsable'=>'','adresse'=>''];
$erreur = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM agences WHERE id_agence = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $ag = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $responsable = trim($_POST['responsable'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    if ($code === '' || $nom === '' || $ville === '' || $responsable === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE agences SET code=:c, nom=:n, ville=:v, responsable=:r, adresse=:a WHERE id_agence=:id");
            $stmt->execute([':c'=>$code, ':n'=>$nom, ':v'=>$ville, ':r'=>$responsable, ':a'=>$adresse, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO agences (code, nom, ville, responsable, adresse) VALUES (:c,:n,:v,:r,:a)");
            $stmt->execute([':c'=>$code, ':n'=>$nom, ':v'=>$ville, ':r'=>$responsable, ':a'=>$adresse]);
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
  <title><?= $id ? 'Modifier' : 'Nouvelle' ?> agence – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-logo-icon">AB</div><div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div></div>
  <div class="sb-section">Navigation</div>
  <a href="../index.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
  <a href="../sinistres/liste.php" class="nav-item"><span class="icon">📁</span> Sinistres</a>
  <a href="../assures/liste.php" class="nav-item"><span class="icon">👤</span> Assurés</a>
  <a href="../vehicules/liste.php" class="nav-item"><span class="icon">🚗</span> Véhicules</a>
  <a href="../experts/liste.php" class="nav-item"><span class="icon">🔧</span> Experts</a>
  <a href="liste.php" class="nav-item active"><span class="icon">🏢</span> Agences</a>
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
    <div class="topbar-title"><?= $id ? 'Modifier' : 'Nouvelle' ?> agence</div>
    <div class="topbar-right"><a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a></div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <a href="liste.php">Agences</a> › <span>Nouveau</span></div><h2>Ajouter 🏢 agence</h2></div>
    </div>
    <?php if ($erreur): ?><div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form method="post">
    <div class="card">
      <div class="card-header"><div class="card-title">🏢 Informations</div></div>
      <div class="form-section">
        <div class="form-grid">
          <div class="form-group"><label>Code *</label><input class="form-inp" type="text" name="code" value="<?= htmlspecialchars($ag['code']) ?>" required/></div>
          <div class="form-group"><label>Nom *</label><input class="form-inp" type="text" name="nom" value="<?= htmlspecialchars($ag['nom']) ?>" required/></div>
          <div class="form-group"><label>Ville *</label><input class="form-inp" type="text" name="ville" value="<?= htmlspecialchars($ag['ville']) ?>" required/></div>
          <div class="form-group"><label>Responsable *</label><input class="form-inp" type="text" name="responsable" value="<?= htmlspecialchars($ag['responsable']) ?>" required/></div>
          <div class="form-group full"><label>Adresse</label><input class="form-inp" type="text" name="adresse" value="<?= htmlspecialchars($ag['adresse'] ?? '') ?>"/></div>
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
