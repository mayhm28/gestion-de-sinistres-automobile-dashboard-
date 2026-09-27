<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$recherche = $_GET['q'] ?? '';
$sql = "SELECT v.id_vehicule, v.immatriculation, v.marque, v.modele, v.annee,
               CONCAT(a.prenom,' ',a.nom) AS assure_nom
        FROM vehicules v JOIN assures a ON v.id_assure = a.id_assure WHERE 1=1";
$params = [];
if ($recherche !== '') {
    $sql .= " AND (v.immatriculation LIKE :q OR v.marque LIKE :q OR a.nom LIKE :q)";
    $params[':q'] = "%$recherche%";
}
$sql .= " ORDER BY v.marque";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Véhicules – Assurances BIAT</title>
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
      <div class="topbar-title">🚗 Véhicules</div>
      <div class="topbar-right">
        <a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
        <a href="nouveau.php" class="btn btn-green">＋ Nouveau véhicule</a>
      </div>
    </header>
    <main class="content">
      <div class="card">
        <div class="card-header"><div class="card-title">🚗 Véhicules</div></div>
        <form method="get" class="search-row">
          <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="🔍 Rechercher..."/>
          <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
        </form>
        <table>
          <thead><tr><th>Immatriculation</th><th>Marque / Modèle</th><th>Assuré</th><th>Année</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($vehicules)): ?>
            <tr><td colspan="5" style="text-align:center;color:var(--gris-texte);">Aucun véhicule trouvé.</td></tr>
            <?php else: foreach ($vehicules as $v): ?>
            <tr>
              <td><?= htmlspecialchars($v['immatriculation']) ?></td>
              <td><?= htmlspecialchars($v['marque'].' '.$v['modele']) ?></td>
              <td><?= htmlspecialchars($v['assure_nom']) ?></td>
              <td><?= htmlspecialchars($v['annee']) ?></td>
              <td><div class="action-btns">
                <a href="nouveau.php?id=<?= $v['id_vehicule'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
                <a href="supprimer.php?id=<?= $v['id_vehicule'] ?>" onclick="return confirm('Supprimer ce véhicule ?')" class="btn btn-danger btn-sm">🗑️</a>
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
