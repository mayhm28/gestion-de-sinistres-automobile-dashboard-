<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
$v = ['immatriculation'=>'','marque'=>'','modele'=>'','annee'=>'','id_assure'=>''];
$erreur = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM vehicules WHERE id_vehicule = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $v = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $immatriculation = trim($_POST['immatriculation'] ?? '');
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $annee = $_POST['annee'] ?: null;
    $id_assure = $_POST['id_assure'] ?? '';

    if ($immatriculation === '' || $marque === '' || $modele === '' || $id_assure === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        $id_marque = $pdo->prepare("SELECT id_marque FROM marques WHERE nom = ?");
        $id_marque->execute([$marque]);
        $idm = $id_marque->fetchColumn() ?: null;

        if ($id) {
            $stmt = $pdo->prepare("UPDATE vehicules SET immatriculation=:im, marque=:ma, modele=:mo, annee=:an, id_assure=:ida, id_marque=:idm WHERE id_vehicule=:id");
            $stmt->execute([':im'=>$immatriculation, ':ma'=>$marque, ':mo'=>$modele, ':an'=>$annee, ':ida'=>$id_assure, ':idm'=>$idm, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO vehicules (immatriculation, marque, modele, annee, id_assure, id_marque) VALUES (:im,:ma,:mo,:an,:ida,:idm)");
            $stmt->execute([':im'=>$immatriculation, ':ma'=>$marque, ':mo'=>$modele, ':an'=>$annee, ':ida'=>$id_assure, ':idm'=>$idm]);
        }
        header('Location: liste.php');
        exit;
    }
}

$assures = $pdo->query("SELECT id_assure, nom, prenom, cin FROM assures ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $id ? 'Modifier' : 'Nouveau' ?> véhicule – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-logo-icon">AB</div><div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div></div>
  <div class="sb-section">Navigation</div>
  <a href="../index.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
  <a href="../sinistres/liste.php" class="nav-item"><span class="icon">📁</span> Sinistres</a>
  <a href="../assures/liste.php" class="nav-item"><span class="icon">👤</span> Assurés</a>
  <a href="liste.php" class="nav-item active"><span class="icon">🚗</span> Véhicules</a>
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
    <div class="topbar-title"><?= $id ? 'Modifier' : 'Nouveau' ?> véhicule</div>
    <div class="topbar-right"><a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a></div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <a href="liste.php">Véhicules</a> › <span>Nouveau</span></div><h2>Ajouter 🚗 véhicule</h2></div>
    </div>
    <?php if ($erreur): ?><div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form method="post">
    <div class="card">
      <div class="card-header"><div class="card-title">🚗 Informations</div></div>
      <div class="form-section">
        <div class="form-grid">
          <div class="form-group"><label>Immatriculation *</label><input class="form-inp" type="text" name="immatriculation" value="<?= htmlspecialchars($v['immatriculation']) ?>" required/></div>
          <div class="form-group"><label>Marque *</label><input class="form-inp" type="text" name="marque" value="<?= htmlspecialchars($v['marque']) ?>" required/></div>
          <div class="form-group"><label>Modèle *</label><input class="form-inp" type="text" name="modele" value="<?= htmlspecialchars($v['modele']) ?>" required/></div>
          <div class="form-group"><label>Année</label><input class="form-inp" type="number" name="annee" value="<?= htmlspecialchars($v['annee'] ?? '') ?>"/></div>
          <div class="form-group full"><label>Assuré *</label>
            <select class="form-inp" name="id_assure" required>
              <option value="">-- Sélectionner --</option>
              <?php foreach ($assures as $a): ?>
              <option value="<?= $a['id_assure'] ?>" <?= (isset($v['id_assure']) && $v['id_assure']==$a['id_assure']) ? 'selected' : '' ?>><?= htmlspecialchars($a['prenom'].' '.$a['nom'].' (CIN '.$a['cin'].')') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
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
