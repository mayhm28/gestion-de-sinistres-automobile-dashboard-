<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

if ($_SESSION['user_role'] !== 'Administrateur') {
    header('Location: liste.php');
    exit;
}

$id = $_GET['id'] ?? null;
$u = ['nom'=>'','email'=>'','role'=>'Gestionnaire','id_agence'=>'','statut'=>'Actif'];
$erreur = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_user = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $u = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'Gestionnaire';
    $id_agence = $_POST['id_agence'] ?: null;
    $mdp = $_POST['mot_de_passe'] ?? '';

    if ($nom === '' || $email === '' || (!$id && $mdp === '')) {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } elseif (!preg_match('/^[a-z]+\.[a-z]+@assurancesbiat\.com\.tn$/i', $email)) {
        $erreur = "L'email doit être au format prenom.nom@assurancesbiat.com.tn";
    } else {
        if ($id) {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom=:n, email=:e, role=:r, id_agence=:ag WHERE id_user=:id");
            $stmt->execute([':n'=>$nom, ':e'=>$email, ':r'=>$role, ':ag'=>$id_agence, ':id'=>$id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, id_agence, statut) VALUES (:n,:e,'',:r,:ag,'Actif')");
            $stmt->execute([':n'=>$nom, ':e'=>$email, ':r'=>$role, ':ag'=>$id_agence]);
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
  <title><?= $id ? 'Modifier' : 'Nouvel' ?> utilisateur – Assurances BIAT</title>
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
  <a href="../agences/liste.php" class="nav-item"><span class="icon">🏢</span> Agences</a>
  <div class="sb-section">Administration</div>
  <a href="liste.php" class="nav-item active"><span class="icon">👥</span> Utilisateurs</a>
  <a href="../alertes/index.php" class="nav-item"><span class="icon">🔔</span> Alertes<span class="nav-badge">3</span></a>
  <a href="../rapports/index.php" class="nav-item"><span class="icon">📄</span> Rapports PDF</a>
  <div class="sb-section">Compte</div>
  <a href="../auth/logout.php" class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
  <div class="sb-bottom"><div class="user-card"><div class="user-avatar"><?= htmlspecialchars(mb_substr($_SESSION['user_nom'],0,1)) ?></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div></div></div>
</aside>
<div class="main">
  <header class="topbar">
    <div class="topbar-title"><?= $id ? 'Modifier' : 'Nouvel' ?> utilisateur</div>
    <div class="topbar-right"><a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a></div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <a href="liste.php">Utilisateurs</a> › <span>Nouveau</span></div><h2>Ajouter 👥 utilisateur</h2></div>
    </div>
    <?php if ($erreur): ?><div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form method="post">
    <div class="card">
      <div class="card-header"><div class="card-title">👥 Informations</div></div>
      <div class="form-section">
        <div class="form-grid">
          <div class="form-group"><label>Nom complet *</label><input class="form-inp" type="text" id="nom_u" name="nom" value="<?= htmlspecialchars($u['nom']) ?>" required/></div>
          <div class="form-group full"><label>Email *</label>
            <input class="form-inp" type="email" id="email_u" name="email" value="<?= htmlspecialchars($u['email']) ?>" placeholder="prenom.nom@assurancesbiat.com.tn" required/>
          </div>
          <div class="form-group"><label>Rôle *</label>
            <select class="form-inp" name="role">
              <option value="Gestionnaire" <?= $u['role']==='Gestionnaire'?'selected':'' ?>>Gestionnaire</option>
              <option value="Responsable agence" <?= $u['role']==='Responsable agence'?'selected':'' ?>>Responsable agence</option>
              <option value="Administrateur" <?= $u['role']==='Administrateur'?'selected':'' ?>>Administrateur</option>
            </select>
          </div>
          <div class="form-group"><label>Agence</label>
            <select class="form-inp" name="id_agence">
              <option value="">-- Sélectionner --</option>
              <?php foreach ($agences as $ag): ?>
              <option value="<?= $ag['id_agence'] ?>" <?= (isset($u['id_agence']) && $u['id_agence']==$ag['id_agence']) ? 'selected' : '' ?>><?= htmlspecialchars($ag['nom']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if (!$id): ?>
          <div class="form-group full"><label>Mot de passe temporaire *</label><input class="form-inp" type="password" name="mot_de_passe" placeholder="Non stocké — mot de passe commun utilisé pour la connexion"/></div>
          <?php endif; ?>
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
<script>
  function normaliser(s) {
    return s.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z -]/g, '');
  }
  document.getElementById('nom_u').addEventListener('input', function() {
    const parts = normaliser(this.value).split(' ').filter(Boolean);
    if (parts.length >= 2) {
      document.getElementById('email_u').value = `${parts[1]}.${parts[0]}@assurancesbiat.com.tn`;
    }
  });
</script>
</body>
</html>
