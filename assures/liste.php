<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$recherche = $_GET['q'] ?? '';
$sql = "SELECT a.id_assure, a.nom, a.prenom, a.cin, a.telephone, a.numero_contrat,
               (SELECT COUNT(*) FROM sinistres s WHERE s.id_assure = a.id_assure) AS nb_sinistres
        FROM assures a WHERE 1=1";
$params = [];
if ($recherche !== '') {
    $sql .= " AND (a.nom LIKE :q OR a.prenom LIKE :q OR a.cin LIKE :q OR a.numero_contrat LIKE :q)";
    $params[':q'] = "%$recherche%";
}
$sql .= " ORDER BY a.nom";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$assures = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = $pdo->query("SELECT COUNT(*) FROM assures")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Assurés – Assurances BIAT</title>
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
    <div class="topbar-title">Assurés</div>
    <div class="topbar-right">
      <a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
      <a href="nouveau.php" class="btn btn-green">＋ Ajouter assuré</a>
    </div>
  </header>
  <main class="content">
    <div class="page-header">
      <div><div class="breadcrumb"><a href="../index.php">Dashboard</a> › <span>Assurés</span></div><h2>Gestion des assurés</h2></div>
    </div>
    <div class="card">
      <div class="card-header"><div class="card-title">👤 Liste des assurés</div><span style="font-size:12px;color:var(--gris-texte);"><?= $total ?> assurés enregistrés</span></div>
      <form method="get" class="search-row">
        <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="🔍 Rechercher par nom, CIN, contrat..."/>
        <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
      </form>
      <table>
        <thead><tr><th>Nom complet</th><th>CIN</th><th>Téléphone</th><th>N° Contrat</th><th>Sinistres</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if (empty($assures)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--gris-texte);">Aucun assuré trouvé.</td></tr>
          <?php else: foreach ($assures as $a): ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['prenom'].' '.$a['nom']) ?></strong></td>
            <td><?= htmlspecialchars($a['cin']) ?></td>
            <td><?= htmlspecialchars($a['telephone']) ?></td>
            <td><?= htmlspecialchars($a['numero_contrat']) ?></td>
            <td><?= $a['nb_sinistres'] ?></td>
            <td><div class="action-btns">
              <a href="../sinistres/liste.php?q=<?= urlencode($a['nom']) ?>" class="btn btn-outline btn-sm">📁</a>
              <a href="nouveau.php?id=<?= $a['id_assure'] ?>" class="btn btn-outline btn-sm">✏️</a>
              <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
              <a href="supprimer.php?id=<?= $a['id_assure'] ?>" onclick="return confirm('Supprimer cet assuré ?')" class="btn btn-danger btn-sm">🗑️</a>
              <?php endif; ?>
            </div></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
