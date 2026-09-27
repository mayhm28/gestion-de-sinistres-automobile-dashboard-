<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$type = $_GET['type'] ?? '';

$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM sinistres")->fetchColumn(),
    'charge' => $pdo->query("SELECT SUM(cout) FROM sinistres")->fetchColumn(),
    'clotures' => $pdo->query("SELECT COUNT(*) FROM sinistres WHERE statut='Clôturé'")->fetchColumn(),
    'alertes' => $pdo->query("SELECT COUNT(*) FROM sinistres WHERE statut='Alerte'")->fetchColumn(),
];

$parAgence = [];
$parAnomalie = [];
if ($type === 'agence') {
    $parAgence = $pdo->query(
        "SELECT ag.nom, COUNT(*) AS nb, SUM(s.cout) AS charge
         FROM sinistres s JOIN agences ag ON s.id_agence = ag.id_agence
         GROUP BY ag.nom ORDER BY charge DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} elseif ($type === 'anomalies') {
    $parAnomalie = $pdo->query(
        "SELECT numero_dossier, type_sinistre, cout, statut, date_sinistre
         FROM sinistres WHERE statut = 'Alerte' OR cout > (SELECT AVG(cout)*2 FROM sinistres)
         ORDER BY cout DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Rapports PDF – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
  <style>
    @media print {
      .sidebar, .topbar, .no-print { display: none !important; }
      .main { margin: 0 !important; }
    }
  </style>
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
  <a href="../alertes/index.php"   class="nav-item"><span class="icon">🔔</span> Alertes</a>
  <a href="index.php"              class="nav-item active"><span class="icon">📄</span> Rapports PDF</a>
  <div class="sb-section">Compte</div>
  <a href="../auth/logout.php"     class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
  <div class="sb-bottom"><div class="user-card"><div class="user-avatar"><?= htmlspecialchars(mb_substr($_SESSION['user_nom'],0,1)) ?></div><div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div></div></div>
</aside>

  <div class="main">
    <header class="topbar no-print">
      <div class="topbar-title">📄 Rapports PDF</div>
    </header>
    <main class="content">

      <div class="card no-print" style="margin-bottom:18px;">
        <div class="card-header"><div class="card-title">📄 Génération de rapports</div></div>
        <div style="padding:18px;display:flex;gap:12px;flex-wrap:wrap;">
          <a href="?type=mensuel" class="btn btn-green">📄 Rapport mensuel sinistres</a>
          <a href="?type=agence" class="btn btn-outline">📄 Rapport par agence</a>
          <a href="?type=anomalies" class="btn btn-outline">📄 Rapport fraude / anomalies</a>
          <?php if ($type): ?>
          <button onclick="window.print()" class="btn btn-outline">🖨️ Imprimer / Exporter en PDF</button>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($type === 'mensuel'): ?>
      <div class="card">
        <div class="card-header"><div class="card-title">Rapport mensuel — synthèse globale</div></div>
        <div style="padding:18px;">
          <p>Généré le <?= date('d/m/Y à H:i') ?></p>
          <table>
            <tbody>
              <tr><td><strong>Total dossiers sinistres</strong></td><td><?= $stats['total'] ?></td></tr>
              <tr><td><strong>Charge totale</strong></td><td><?= number_format($stats['charge'],0,',',' ') ?> DT</td></tr>
              <tr><td><strong>Dossiers clôturés</strong></td><td><?= $stats['clotures'] ?></td></tr>
              <tr><td><strong>Dossiers en alerte</strong></td><td><?= $stats['alertes'] ?></td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <?php elseif ($type === 'agence'): ?>
      <div class="card">
        <div class="card-header"><div class="card-title">Rapport par agence</div></div>
        <table>
          <thead><tr><th>Agence</th><th>Nb dossiers</th><th>Charge totale</th></tr></thead>
          <tbody>
            <?php foreach ($parAgence as $ag): ?>
            <tr><td><?= htmlspecialchars($ag['nom']) ?></td><td><?= $ag['nb'] ?></td><td><?= number_format($ag['charge'],0,',',' ') ?> DT</td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php elseif ($type === 'anomalies'): ?>
      <div class="card">
        <div class="card-header"><div class="card-title">Rapport fraude / anomalies</div></div>
        <table>
          <thead><tr><th>N° Dossier</th><th>Type</th><th>Coût</th><th>Statut</th><th>Date</th></tr></thead>
          <tbody>
            <?php if (empty($parAnomalie)): ?>
            <tr><td colspan="5" style="text-align:center;">Aucune anomalie détectée.</td></tr>
            <?php else: foreach ($parAnomalie as $an): ?>
            <tr>
              <td><?= htmlspecialchars($an['numero_dossier']) ?></td>
              <td><?= htmlspecialchars($an['type_sinistre']) ?></td>
              <td><?= number_format($an['cout'],0,',',' ') ?> DT</td>
              <td><?= htmlspecialchars($an['statut']) ?></td>
              <td><?= date('d/m/Y', strtotime($an['date_sinistre'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>
</body>
</html>
