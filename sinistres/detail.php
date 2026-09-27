<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: liste.php'); exit; }

$stmt = $pdo->prepare(
    "SELECT s.*, CONCAT(a.prenom,' ',a.nom) AS assure_nom, a.cin, a.telephone AS assure_tel, a.numero_contrat,
            v.marque, v.modele, v.annee AS vehicule_annee, v.immatriculation,
            e.nom AS expert_nom, e.specialite,
            ag.nom AS agence_nom
     FROM sinistres s
     JOIN assures a ON s.id_assure = a.id_assure
     LEFT JOIN vehicules v ON s.id_vehicule = v.id_vehicule
     LEFT JOIN experts e ON s.id_expert = e.id_expert
     LEFT JOIN agences ag ON s.id_agence = ag.id_agence
     WHERE s.id_sinistre = ?"
);
$stmt->execute([$id]);
$s = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$s) { header('Location: liste.php'); exit; }

// Changement rapide de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nouveau_statut'])) {
    $nouveau = $_POST['nouveau_statut'];
    if (in_array($nouveau, ['Ouvert','En cours','Clôturé','Alerte'])) {
        if ($nouveau === 'Clôturé') {
            $upd = $pdo->prepare("UPDATE sinistres SET statut = ?, date_cloture = CURDATE() WHERE id_sinistre = ?");
        } else {
            $upd = $pdo->prepare("UPDATE sinistres SET statut = ? WHERE id_sinistre = ?");
        }
        $upd->execute([$nouveau, $id]);
        header("Location: detail.php?id=$id");
        exit;
    }
}

$delaiJours = (new DateTime($s['date_sinistre']))->diff(new DateTime())->days;
$statutClass = match($s['statut']) { 'Alerte'=>'alerte', 'En cours'=>'encours', 'Clôturé'=>'cloture', default=>'ouvert' };
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Détail sinistre <?= htmlspecialchars($s['numero_dossier']) ?> – Assurances BIAT</title>
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
      <div class="topbar-title">📄 <?= htmlspecialchars($s['numero_dossier']) ?></div>
      <div class="topbar-right">
        <a href="modifier.php?id=<?= $id ?>" class="btn btn-outline">✏️ Modifier</a>
        <a href="../rapports/index.php" class="btn btn-outline">📄 Rapport PDF</a>
        <a href="liste.php" class="btn btn-outline">← Retour</a>
      </div>
    </header>

    <main class="content">
      <div style="font-size:12px;color:var(--gris-texte);margin-bottom:18px;">
        <a href="../index.php" style="color:var(--vert-clair);">Dashboard</a> /
        <a href="liste.php" style="color:var(--vert-clair);">Sinistres</a> /
        <?= htmlspecialchars($s['numero_dossier']) ?>
      </div>

      <?php if ($s['statut'] === 'Alerte'): ?>
      <div class="alert-bar" style="margin-bottom:18px;">
        ⚠️ Ce dossier est en <strong>alerte délai</strong> — ouvert depuis <?= $delaiJours ?> jours sans clôture.
      </div>
      <?php endif; ?>

      <div class="detail-grid">

        <div>
          <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
              <div class="card-title">📋 Informations générales</div>
              <span class="statut <?= $statutClass ?>"><?= htmlspecialchars($s['statut']) ?></span>
            </div>
            <div class="card-body">
              <div class="info-row"><span class="info-label">N° Dossier</span><span class="info-value"><strong><?= htmlspecialchars($s['numero_dossier']) ?></strong></span></div>
              <div class="info-row"><span class="info-label">Type de sinistre</span><span class="info-value"><?= htmlspecialchars($s['type_sinistre']) ?></span></div>
              <div class="info-row"><span class="info-label">Date du sinistre</span><span class="info-value"><?= date('d/m/Y', strtotime($s['date_sinistre'])) ?></span></div>
              <?php if ($s['date_cloture']): ?>
              <div class="info-row"><span class="info-label">Date de clôture</span><span class="info-value"><?= date('d/m/Y', strtotime($s['date_cloture'])) ?></span></div>
              <?php endif; ?>
              <div class="info-row"><span class="info-label">Description</span><span class="info-value"><?= htmlspecialchars($s['description'] ?? '—') ?></span></div>
              <div class="info-row"><span class="info-label">Coût</span><span class="info-value"><strong><?= number_format($s['cout'],0,',',' ') ?> DT</strong></span></div>
              <div class="info-row"><span class="info-label">Jours écoulés</span><span class="info-value" style="<?= $delaiJours > 30 ? 'color:var(--danger);font-weight:700;' : '' ?>"><?= $delaiJours ?> jours<?= $delaiJours > 30 ? ' ⚠️' : '' ?></span></div>
            </div>
          </div>

          <div class="card" style="margin-bottom:16px;">
            <div class="card-header"><div class="card-title">👤 Assuré &amp; Véhicule</div></div>
            <div class="card-body">
              <div class="info-row"><span class="info-label">Assuré</span><span class="info-value"><a href="../assures/liste.php" style="color:var(--vert-clair);font-weight:600;"><?= htmlspecialchars($s['assure_nom']) ?></a></span></div>
              <div class="info-row"><span class="info-label">CIN</span><span class="info-value"><?= htmlspecialchars($s['cin']) ?></span></div>
              <div class="info-row"><span class="info-label">Téléphone</span><span class="info-value"><?= htmlspecialchars($s['assure_tel']) ?></span></div>
              <div class="info-row"><span class="info-label">N° Contrat</span><span class="info-value"><?= htmlspecialchars($s['numero_contrat']) ?></span></div>
              <div class="info-row"><span class="info-label">Véhicule</span><span class="info-value"><?= $s['marque'] ? htmlspecialchars($s['marque'].' '.$s['modele'].' '.$s['vehicule_annee'].' – '.$s['immatriculation']) : '—' ?></span></div>
            </div>
          </div>

          <div class="card">
            <div class="card-header"><div class="card-title">🔧 Expert &amp; Agence</div></div>
            <div class="card-body">
              <div class="info-row"><span class="info-label">Expert assigné</span><span class="info-value"><a href="../experts/liste.php" style="color:var(--vert-clair);font-weight:600;"><?= htmlspecialchars($s['expert_nom'] ?? '—') ?></a></span></div>
              <div class="info-row"><span class="info-label">Spécialité</span><span class="info-value"><?= htmlspecialchars($s['specialite'] ?? '—') ?></span></div>
              <div class="info-row"><span class="info-label">Agence</span><span class="info-value"><?= htmlspecialchars($s['agence_nom'] ?? '—') ?></span></div>
              <div class="info-row"><span class="info-label">Gestionnaire</span><span class="info-value"><?= htmlspecialchars($_SESSION['user_nom']) ?></span></div>
            </div>
          </div>
        </div>

        <div>
          <div class="card">
            <div class="card-header"><div class="card-title">⚙️ Actions rapides</div></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:10px;">
              <a href="modifier.php?id=<?= $id ?>" class="btn btn-green" style="justify-content:center;">✏️ Modifier le dossier</a>

              <form method="post" style="display:flex;gap:8px;align-items:center;">
                <select name="nouveau_statut" class="form-inp" style="flex:1;">
                  <?php foreach (['Ouvert','En cours','Alerte','Clôturé'] as $opt): ?>
                  <option value="<?= $opt ?>" <?= $s['statut']===$opt?'selected':'' ?>><?= $opt ?></option>
                  <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-outline">🔄 Changer</button>
              </form>

              <a href="../rapports/index.php" class="btn btn-outline" style="justify-content:center;">📄 Générer rapport PDF</a>
              <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
              <a href="supprimer.php?id=<?= $id ?>" class="btn btn-danger" style="justify-content:center;" onclick="return confirm('Supprimer définitivement ce dossier ?')">🗑️ Supprimer le dossier</a>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>
</body>
</html>
