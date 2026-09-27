<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$recherche = $_GET['q'] ?? '';
$sql = "SELECT u.id_user, u.nom, u.email, u.role, u.statut, ag.nom AS agence_nom
        FROM utilisateurs u LEFT JOIN agences ag ON u.id_agence = ag.id_agence WHERE 1=1";
$params = [];
if ($recherche !== '') {
    $sql .= " AND (u.nom LIKE :q OR u.email LIKE :q)";
    $params[':q'] = "%$recherche%";
}
$sql .= " ORDER BY u.nom";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Utilisateurs – Assurances BIAT</title>
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
      <div class="topbar-title">👥 Utilisateurs</div>
      <div class="topbar-right">
        <a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
        <a href="nouveau.php" class="btn btn-green">＋ Nouvel utilisateur</a>
      </div>
    </header>
    <main class="content">
      <div class="card">
        <div class="card-header"><div class="card-title">👥 Utilisateurs</div></div>
        <form method="get" class="search-row">
          <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="🔍 Rechercher..."/>
          <button type="submit" class="btn btn-outline btn-sm">Rechercher</button>
        </form>
        <table>
          <thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Agence</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="6" style="text-align:center;color:var(--gris-texte);">Aucun utilisateur trouvé.</td></tr>
            <?php else: foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['nom']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td><?= htmlspecialchars($u['agence_nom'] ?? '—') ?></td>
              <td><span class="statut <?= $u['statut']==='Actif' ? 'ouvert' : 'cloture' ?>"><?= htmlspecialchars($u['statut']) ?></span></td>
              <td><div class="action-btns">
                <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
                <a href="nouveau.php?id=<?= $u['id_user'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <a href="supprimer.php?id=<?= $u['id_user'] ?>" onclick="return confirm('Supprimer cet utilisateur ?')" class="btn btn-danger btn-sm">🗑️</a>
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
