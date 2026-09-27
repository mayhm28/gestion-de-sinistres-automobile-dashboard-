<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

// Seuil de délai (en jours) au-dela duquel un dossier non clôturé est considéré en alerte
$SEUIL_JOURS = 30;

$stmt = $pdo->query(
    "SELECT s.id_sinistre, s.numero_dossier, s.date_sinistre, s.statut,
            CONCAT(a.prenom,' ',a.nom) AS assure_nom, e.nom AS expert_nom,
            DATEDIFF(CURDATE(), s.date_sinistre) AS jours_ecoules
     FROM sinistres s
     JOIN assures a ON s.id_assure = a.id_assure
     LEFT JOIN experts e ON s.id_expert = e.id_expert
     WHERE s.statut != 'Clôturé'
     HAVING jours_ecoules > $SEUIL_JOURS
     ORDER BY jours_ecoules DESC"
);
$alertes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nbAlertes = count($alertes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Alertes – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
<aside class="sidebar">
  <div class="sb-logo"><div class="sb-logo-icon">AB</div><div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div></div>
  <div class="sb-section">Navigation</div>
  <a href="../index.php"           class="nav-item"><span class="icon">📊</span> Dashboard</a>
  <a href="../sinistres/liste.php" class="nav-item"><span class="icon">📁</span> Sinistres</a>
  <a href="../assures/liste.php"   class="nav-item"><span class="icon">👤</span> Assurés</a>
  <a href="../vehicules/liste.php" class="nav-item"><span class="icon">🚗</span> Véhicules</a>
  <a href="../experts/liste.php"   class="nav-item"><span class="icon">🔧</span> Experts</a>
  <a href="../agences/liste.php"   class="nav-item"><span class="icon">🏢</span> Agences</a>
  <div class="sb-section">Administration</div>
  <a href="../users/liste.php"     class="nav-item"><span class="icon">👥</span> Utilisateurs</a>
  <a href="index.php"              class="nav-item active"><span class="icon">🔔</span> Alertes <span class="nav-badge"><?= $nbAlertes ?></span></a>
  <a href="../rapports/index.php"  class="nav-item"><span class="icon">📄</span> Rapports PDF</a>
  <div class="sb-section">Compte</div>
  <a href="../auth/logout.php"     class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
  <div class="sb-bottom"><div class="user-card"><div class="user-avatar"><?= htmlspecialchars(mb_substr($_SESSION['user_nom'],0,1)) ?></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div></div></div>
</aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">🔔 Alertes</div>
    </header>
    <main class="content">
      <div class="card">
        <div class="card-header"><div class="card-title">🔔 Dossiers en alerte (délai &gt; <?= $SEUIL_JOURS ?> jours)</div></div>
        <table>
          <thead><tr><th>N° Dossier</th><th>Assuré</th><th>Jours écoulés</th><th>Expert</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($alertes)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--gris-texte);">Aucun dossier en alerte actuellement. ✅</td></tr>
            <?php else: foreach ($alertes as $a): ?>
            <tr>
              <td><strong><?= htmlspecialchars($a['numero_dossier']) ?></strong></td>
              <td><?= htmlspecialchars($a['assure_nom']) ?></td>
              <td style="color:var(--danger);font-weight:700;"><?= $a['jours_ecoules'] ?></td>
              <td><?= htmlspecialchars($a['expert_nom'] ?? '—') ?></td>
              <td><?= htmlspecialchars($a['statut']) ?></td>
              <td><a href="../sinistres/detail.php?id=<?= $a['id_sinistre'] ?>" class="btn btn-outline btn-sm">👁</a></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>
</body>
</html>
