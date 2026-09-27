<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
$e = ['nom'=>'','specialite'=>'','id_agence'=>'','telephone'=>'','email'=>''];
$erreur = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM experts WHERE id_expert = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $e = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $specialite = trim($_POST['specialite'] ?? '');
    $id_agence = $_POST['id_agence'] ?: null;
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($nom === '' || $specialite === '' || $telephone === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE experts SET nom=:nom, specialite=:sp, id_agence=:ag, telephone=:tel, email=:em WHERE id_expert=:id");
            $stmt->execute([':nom'=>$nom, ':sp'=>$specialite, ':ag'=>$id_agence, ':tel'=>$telephone, ':em'=>$email, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO experts (nom, specialite, id_agence, telephone, email) VALUES (:nom,:sp,:ag,:tel,:em)");
            $stmt->execute([':nom'=>$nom, ':sp'=>$specialite, ':ag'=>$id_agence, ':tel'=>$telephone, ':em'=>$email]);
        }
        header('Location: liste.php');
        exit;
    }
}

$agences = $pdo->query("SELECT id_agence, nom FROM agences ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $id ? 'Modifier' : 'Nouveau' ?> expert – Assurances BIAT</title>
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
  <a href="liste.php" class="nav-item active"><span class="icon">🔧</span> Experts</a>
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
    <div class="topbar-title"><?= $id ? 'Modifier' : 'Nouveau' ?> expert</div>
    <div class="topbar-right"><a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a></div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <a href="liste.php">Experts</a> › <span>Nouveau</span></div><h2>Ajouter 🔧 expert</h2></div>
    </div>
    <?php if ($erreur): ?><div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form method="post">
    <div class="card">
      <div class="card-header"><div class="card-title">🔧 Informations</div></div>
      <div class="form-section">
        <div class="form-grid">
          <div class="form-group"><label>Nom *</label><input class="form-inp" type="text" id="nom_e" name="nom" value="<?= htmlspecialchars($e['nom']) ?>" required/></div>
          <div class="form-group"><label>Spécialité *</label><input class="form-inp" type="text" name="specialite" value="<?= htmlspecialchars($e['specialite']) ?>" required/></div>
          <div class="form-group"><label>Agence</label>
            <select class="form-inp" name="id_agence">
              <option value="">-- Sélectionner --</option>
              <?php foreach ($agences as $ag): ?>
              <option value="<?= $ag['id_agence'] ?>" <?= (isset($e['id_agence']) && $e['id_agence']==$ag['id_agence']) ? 'selected' : '' ?>><?= htmlspecialchars($ag['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label>Téléphone *</label><input class="form-inp" type="tel" name="telephone" value="<?= htmlspecialchars($e['telephone']) ?>" required/></div>
          <div class="form-group full"><label>Email</label>
            <input class="form-inp" type="email" id="email_e" name="email" value="<?= htmlspecialchars($e['email'] ?? '') ?>" placeholder="prenom.nom@assurancesbiat.com.tn" pattern=".+@assurancesbiat\.com\.tn"/>
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
