<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: liste.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM sinistres WHERE id_sinistre = ?");
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) { header('Location: liste.php'); exit; }

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type_sinistre'] ?? '';
    $date_sinistre = $_POST['date_sinistre'] ?? '';
    $cout = $_POST['cout'] !== '' ? $_POST['cout'] : 0;
    $statut = $_POST['statut'] ?? 'Ouvert';
    $description = $_POST['description'] ?? '';
    $id_vehicule = $_POST['id_vehicule'] ?: null;
    $id_expert = $_POST['id_expert'] ?: null;
    $id_agence = $_POST['id_agence'] ?: null;

    if ($type === '' || $date_sinistre === '' || $description === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        $date_cloture = $s['date_cloture'];
        if ($statut === 'Clôturé' && !$date_cloture) {
            $date_cloture = date('Y-m-d');
        } elseif ($statut !== 'Clôturé') {
            $date_cloture = null;
        }

        $upd = $pdo->prepare(
            "UPDATE sinistres SET type_sinistre=:type, date_sinistre=:date, date_cloture=:cloture,
             cout=:cout, statut=:statut, description=:desc, id_vehicule=:vehicule, id_expert=:expert, id_agence=:agence
             WHERE id_sinistre=:id"
        );
        $upd->execute([
            ':type'=>$type, ':date'=>$date_sinistre, ':cloture'=>$date_cloture, ':cout'=>$cout,
            ':statut'=>$statut, ':desc'=>$description, ':vehicule'=>$id_vehicule,
            ':expert'=>$id_expert, ':agence'=>$id_agence, ':id'=>$id,
        ]);
        header("Location: detail.php?id=$id");
        exit;
    }
}

$vehicules = $pdo->query("SELECT id_vehicule, immatriculation, marque, modele FROM vehicules ORDER BY marque")->fetchAll(PDO::FETCH_ASSOC);
$experts   = $pdo->query("SELECT id_expert, nom, specialite FROM experts ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$agences   = $pdo->query("SELECT id_agence, nom FROM agences ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Modifier <?= htmlspecialchars($s['numero_dossier']) ?> – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
  <aside class="sidebar">
    <div class="sb-logo"><div class="sb-logo-icon">AB</div><div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div></div>
    <div class="sb-section">Navigation</div>
    <a href="../index.php"       class="nav-item"><span class="icon">📊</span> Dashboard</a>
    <a href="liste.php"              class="nav-item active"><span class="icon">📁</span> Sinistres</a>
    <a href="../assures/liste.php"   class="nav-item"><span class="icon">👤</span> Assurés</a>
    <a href="../vehicules/liste.php" class="nav-item"><span class="icon">🚗</span> Véhicules</a>
    <a href="../experts/liste.php"   class="nav-item"><span class="icon">🔧</span> Experts</a>
    <a href="../agences/liste.php"   class="nav-item"><span class="icon">🏢</span> Agences</a>
    <div class="sb-section">Administration</div>
    <a href="../users/liste.php"     class="nav-item"><span class="icon">👥</span> Utilisateurs</a>
    <a href="../alertes/index.php"   class="nav-item"><span class="icon">🔔</span> Alertes <span class="nav-badge">3</span></a>
    <a href="../rapports/index.php"  class="nav-item"><span class="icon">📄</span> Rapports PDF</a>
    <div class="sb-section">Compte</div>
    <a href="../auth/logout.php"     class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
    <div class="sb-bottom"><div class="user-card"><div class="user-avatar"><?= htmlspecialchars(mb_substr($_SESSION['user_nom'],0,1)) ?></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div></div></div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">✏️ Modifier <?= htmlspecialchars($s['numero_dossier']) ?></div>
      <div class="topbar-right">
        <a href="detail.php?id=<?= $id ?>" class="btn btn-outline">← Retour au dossier</a>
      </div>
    </header>

    <main class="content">
      <div style="font-size:12px;color:var(--gris-texte);margin-bottom:18px;">
        <a href="../index.php" style="color:var(--vert-clair);">Dashboard</a> /
        <a href="liste.php" style="color:var(--vert-clair);">Sinistres</a> /
        <a href="detail.php?id=<?= $id ?>" style="color:var(--vert-clair);"><?= htmlspecialchars($s['numero_dossier']) ?></a> / Modifier
      </div>

      <?php if ($erreur): ?>
        <div class="alert-bar" style="margin-bottom:16px;">⚠️ <?= htmlspecialchars($erreur) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><div class="card-title">📋 Informations du sinistre</div></div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label>N° Dossier</label>
                <input class="form-inp" type="text" value="<?= htmlspecialchars($s['numero_dossier']) ?>" readonly style="background:var(--gris-fond);"/>
              </div>
              <div class="form-group">
                <label>Date du sinistre *</label>
                <input class="form-inp" type="date" name="date_sinistre" value="<?= htmlspecialchars($s['date_sinistre']) ?>" required/>
              </div>
              <div class="form-group">
                <label>Type de sinistre *</label>
                <select class="form-inp" name="type_sinistre" required>
                  <?php foreach (['Collision','Vol','Incendie','Bris de glace','Catastrophe naturelle'] as $t): ?>
                  <option <?= $s['type_sinistre']===$t?'selected':'' ?>><?= $t ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Statut</label>
                <select class="form-inp" name="statut">
                  <?php foreach (['Ouvert','En cours','Alerte','Clôturé'] as $st): ?>
                  <option value="<?= $st ?>" <?= $s['statut']===$st?'selected':'' ?>><?= $st ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Coût (DT)</label>
                <input class="form-inp" type="number" step="0.01" name="cout" value="<?= htmlspecialchars($s['cout']) ?>" min="0"/>
              </div>
              <div class="form-group full">
                <label>Description *</label>
                <textarea class="form-inp" name="description" rows="3" required><?= htmlspecialchars($s['description'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><div class="card-title">🚗 Véhicule, Expert &amp; Agence</div></div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label>Véhicule</label>
                <select class="form-inp" name="id_vehicule">
                  <option value="">-- Aucun --</option>
                  <?php foreach ($vehicules as $v): ?>
                  <option value="<?= $v['id_vehicule'] ?>" <?= $s['id_vehicule']==$v['id_vehicule']?'selected':'' ?>><?= htmlspecialchars($v['immatriculation'].' – '.$v['marque'].' '.$v['modele']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Expert</label>
                <select class="form-inp" name="id_expert">
                  <option value="">-- Aucun --</option>
                  <?php foreach ($experts as $e): ?>
                  <option value="<?= $e['id_expert'] ?>" <?= $s['id_expert']==$e['id_expert']?'selected':'' ?>><?= htmlspecialchars($e['nom'].' – '.$e['specialite']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Agence</label>
                <select class="form-inp" name="id_agence">
                  <option value="">-- Aucune --</option>
                  <?php foreach ($agences as $ag): ?>
                  <option value="<?= $ag['id_agence'] ?>" <?= $s['id_agence']==$ag['id_agence']?'selected':'' ?>><?= htmlspecialchars($ag['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-green">💾 Enregistrer les modifications</button>
          <a href="detail.php?id=<?= $id ?>" class="btn btn-outline">Annuler</a>
        </div>
      </form>
    </main>
  </div>
</div>
</body>
</html>
