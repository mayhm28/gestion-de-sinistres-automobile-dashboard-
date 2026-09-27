<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$recherche = $_GET['q'] ?? '';
$sql = "SELECT e.id_expert, e.nom, e.specialite, e.telephone, e.email, ag.nom AS agence_nom,
               (SELECT COUNT(*) FROM sinistres s WHERE s.id_expert = e.id_expert AND s.statut != 'Clôturé') AS dossiers_en_cours
        FROM experts e LEFT JOIN agences ag ON e.id_agence = ag.id_agence WHERE 1=1";
$params = [];
if ($recherche !== '') {
    $sql .= " AND (e.nom LIKE :q OR e.specialite LIKE :q)";
    $params[':q'] = "%$recherche%";
}
$sql .= " ORDER BY e.nom";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$experts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Experts – Assurances BIAT</title>
  <link rel="stylesheet" href="../assets/css/style.css"/>
</head>
<body>
<div class="app-wrapper">
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
      <div class="topbar-title">🔧 Experts</div>
      <div class="topbar-right">
        <a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
        <a href="nouveau.php" class="btn btn-green">＋ Nouvel expert</a>
      </div>
    </header>
    <main class="content">
      <div class="card">
        <div class="card-header"><div class="card-title">🔧 Experts</div></div>
        <form method="get" class="search-row">
          <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="🔍 Rechercher..."/>
          <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
        </form>
        <table>
          <thead><tr><th>Nom</th><th>Spécialité</th><th>Agence</th><th>Dossiers en cours</th><th>Téléphone</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($experts)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--gris-texte);">Aucun expert trouvé.</td></tr>
            <?php else: foreach ($experts as $e): ?>
            <tr>
              <td><?= htmlspecialchars($e['nom']) ?></td>
              <td><?= htmlspecialchars($e['specialite']) ?></td>
              <td><?= htmlspecialchars($e['agence_nom'] ?? '—') ?></td>
              <td><?= $e['dossiers_en_cours'] ?></td>
              <td><?= htmlspecialchars($e['telephone']) ?></td>
              <td><div class="action-btns">
                <a href="nouveau.php?id=<?= $e['id_expert'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
                <a href="supprimer.php?id=<?= $e['id_expert'] ?>" onclick="return confirm('Supprimer cet expert ?')" class="btn btn-danger btn-sm">🗑️</a>
                <?php endif; ?>
              </div></td>
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
