<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

// Recherche + filtres (facultatifs, via GET)
$recherche = $_GET['q'] ?? '';
$statut    = $_GET['statut'] ?? '';

$sql = "SELECT s.id_sinistre, s.numero_dossier, s.date_sinistre, s.type_sinistre, s.cout, s.statut,
               CONCAT(a.prenom, ' ', a.nom) AS assure_nom,
               e.nom AS expert_nom
        FROM sinistres s
        JOIN assures a ON s.id_assure = a.id_assure
        LEFT JOIN experts e ON s.id_expert = e.id_expert
        WHERE 1=1";
$params = [];

if ($recherche !== '') {
    $sql .= " AND (s.numero_dossier LIKE :q OR a.nom LIKE :q OR a.prenom LIKE :q)";
    $params[':q'] = "%$recherche%";
}
if ($statut !== '') {
    $sql .= " AND s.statut = :statut";
    $params[':statut'] = $statut;
}
$sql .= " ORDER BY s.date_sinistre DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sinistres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->query("SELECT COUNT(*) FROM sinistres")->fetchColumn();

function statutClass($s) {
    return match ($s) {
        'Alerte' => 'alerte',
        'En cours' => 'encours',
        'Clôturé' => 'cloture',
        default => 'ouvert',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Sinistres – Assurances BIAT</title>
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
    <a href="../auth/login.php"      class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
    <div class="sb-bottom"><div class="user-card"><div class="user-avatar">AK</div><div><div class="user-name">Ahmed Karim</div><div class="user-role">Gestionnaire</div></div></div></div>
  </aside>

  <div class="main">
    <header class="topbar">
      <div class="topbar-title">📁 Sinistres</div>
      <div class="topbar-right">
        <a href="../alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
        <a href="nouveau.php" class="btn btn-green">＋ Nouveau sinistre</a>
      </div>
    </header>

    <main class="content">
      <div class="card">
        <div class="card-header">
          <div class="card-title">📁 Tous les dossiers sinistres</div>
          <span style="font-size:12px;color:var(--gris-texte);"><?= $total ?> dossiers au total</span>
        </div>

        <form method="get" class="search-row">
          <input class="search-input" type="text" name="q" value="<?= htmlspecialchars($recherche) ?>" placeholder="🔍 Rechercher N° dossier, assuré..."/>
          <select class="select-filter" name="statut" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <option value="Ouvert"   <?= $statut === 'Ouvert' ? 'selected' : '' ?>>Ouvert</option>
            <option value="En cours" <?= $statut === 'En cours' ? 'selected' : '' ?>>En cours</option>
            <option value="Clôturé"  <?= $statut === 'Clôturé' ? 'selected' : '' ?>>Clôturé</option>
            <option value="Alerte"   <?= $statut === 'Alerte' ? 'selected' : '' ?>>Alerte</option>
          </select>
          <button type="submit" class="btn btn-outline btn-sm">Filtrer</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>N° Dossier</th>
              <th>Assuré</th>
              <th>Type</th>
              <th>Date sinistre</th>
              <th>Expert</th>
              <th>Coût (DT)</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($sinistres)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--gris-texte);">Aucun dossier trouvé.</td></tr>
            <?php else: foreach ($sinistres as $s): ?>
            <tr>
              <td><strong><?= htmlspecialchars($s['numero_dossier']) ?></strong></td>
              <td><?= htmlspecialchars($s['assure_nom']) ?></td>
              <td><?= htmlspecialchars($s['type_sinistre']) ?></td>
              <td><?= date('d/m/Y', strtotime($s['date_sinistre'])) ?></td>
              <td><?= htmlspecialchars($s['expert_nom'] ?? '—') ?></td>
              <td><?= number_format($s['cout'], 0, ',', ' ') ?></td>
              <td><span class="statut <?= statutClass($s['statut']) ?>"><?= htmlspecialchars($s['statut']) ?></span></td>
              <td><div class="action-btns">
                <a href="detail.php?id=<?= $s['id_sinistre'] ?>" class="btn btn-outline btn-sm">👁</a>
                <a href="modifier.php?id=<?= $s['id_sinistre'] ?>" class="btn btn-outline btn-sm">✏️</a>
                <?php if ($_SESSION['user_role'] === 'Administrateur'): ?>
                <a href="supprimer.php?id=<?= $s['id_sinistre'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce dossier ?')">🗑️</a>
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
