<?php
require '../config/db.php';
require '../config/auth.php';
exiger_connexion('../auth/login.php');

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_assure   = $_POST['id_assure'] ?? '';
    $id_vehicule = $_POST['id_vehicule'] ?: null;
    $id_expert   = $_POST['id_expert'] ?: null;
    $id_agence   = $_POST['id_agence'] ?: null;
    $type        = $_POST['type_sinistre'] ?? '';
    $date        = $_POST['date_sinistre'] ?? '';
    $cout        = $_POST['cout'] !== '' ? $_POST['cout'] : 0;
    $statut      = $_POST['statut'] ?? 'Ouvert';
    $description = $_POST['description'] ?? '';

    if ($id_assure === '' || $type === '' || $date === '' || $description === '') {
        $erreur = "Merci de remplir tous les champs obligatoires (*).";
    } else {
        $annee = date('Y');
        $count = $pdo->query("SELECT COUNT(*) FROM sinistres WHERE numero_dossier LIKE 'SIN-$annee-%'")->fetchColumn();
        $numero_dossier = sprintf("SIN-%s-%04d", $annee, $count + 1);

        $stmt = $pdo->prepare(
            "INSERT INTO sinistres (numero_dossier, id_assure, id_vehicule, id_expert, id_agence, type_sinistre, date_sinistre, cout, statut, description)
             VALUES (:numero, :assure, :vehicule, :expert, :agence, :type, :date, :cout, :statut, :description)"
        );
        $stmt->execute([
            ':numero'      => $numero_dossier,
            ':assure'      => $id_assure,
            ':vehicule'    => $id_vehicule,
            ':expert'      => $id_expert,
            ':agence'      => $id_agence,
            ':type'        => $type,
            ':date'        => $date,
            ':cout'        => $cout,
            ':statut'      => $statut,
            ':description' => $description,
        ]);

        header("Location: liste.php");
        exit;
    }
}

$assures = $pdo->query("SELECT id_assure, nom, prenom, cin FROM assures ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$experts = $pdo->query("SELECT id_expert, nom, specialite FROM experts ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
$agences = $pdo->query("SELECT id_agence, nom FROM agences ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <title>Nouveau sinistre – Assurances BIAT</title>
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
      <div class="topbar-title">＋ Nouveau sinistre</div>
      <div class="topbar-right">
        <a href="liste.php" class="btn btn-outline">← Retour à la liste</a>
      </div>
    </header>

    <main class="content">

      <div style="font-size:12px;color:var(--gris-texte);margin-bottom:18px;">
        <a href="../index.php" style="color:var(--vert-clair);">Dashboard</a> /
        <a href="liste.php" style="color:var(--vert-clair);">Sinistres</a> /
        <span>Nouveau dossier</span>
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
                <input class="form-inp" type="text" value="Généré automatiquement" readonly style="background:var(--gris-fond);"/>
              </div>
              <div class="form-group">
                <label>Date du sinistre *</label>
                <input class="form-inp" type="date" name="date_sinistre" required/>
              </div>
              <div class="form-group">
                <label>Type de sinistre *</label>
                <select class="form-inp" name="type_sinistre" required>
                  <option value="">-- Sélectionner --</option>
                  <option>Collision</option>
                  <option>Vol</option>
                  <option>Incendie</option>
                  <option>Bris de glace</option>
                  <option>Catastrophe naturelle</option>
                </select>
              </div>
              <div class="form-group">
                <label>Statut initial</label>
                <select class="form-inp" name="statut">
                  <option value="Ouvert" selected>Ouvert</option>
                  <option value="En cours">En cours</option>
                </select>
              </div>
              <div class="form-group">
                <label>Coût estimé (DT)</label>
                <input class="form-inp" type="number" step="0.01" name="cout" placeholder="0.00" min="0"/>
              </div>
              <div class="form-group full">
                <label>Description du sinistre *</label>
                <textarea class="form-inp" name="description" rows="3" placeholder="Décrire les circonstances du sinistre..." required></textarea>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><div class="card-title">👤 Assuré & Véhicule</div></div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label>Assuré *</label>
                <select class="form-inp" name="id_assure" id="select-assure" required>
                  <option value="">-- Sélectionner --</option>
                  <?php foreach ($assures as $a): ?>
                  <option value="<?= $a['id_assure'] ?>"><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?> (CIN <?= htmlspecialchars($a['cin']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Véhicule</label>
                <select class="form-inp" name="id_vehicule" id="select-vehicule">
                  <option value="">-- Sélectionnez d'abord un assuré --</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="card" style="margin-bottom:16px;">
          <div class="card-header"><div class="card-title">🔧 Expert & Agence</div></div>
          <div class="card-body">
            <div class="form-grid">
              <div class="form-group">
                <label>Expert assigné</label>
                <select class="form-inp" name="id_expert">
                  <option value="">-- Sélectionner --</option>
                  <?php foreach ($experts as $e): ?>
                  <option value="<?= $e['id_expert'] ?>"><?= htmlspecialchars($e['nom'] . ' – ' . $e['specialite']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label>Agence responsable *</label>
                <select class="form-inp" name="id_agence" required>
                  <option value="">-- Sélectionner --</option>
                  <?php foreach ($agences as $ag): ?>
                  <option value="<?= $ag['id_agence'] ?>"><?= htmlspecialchars($ag['nom']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-green">💾 Enregistrer le dossier</button>
          <a href="liste.php" class="btn btn-outline">Annuler</a>
        </div>
      </form>

    </main>
  </div>
</div>

<script>
document.getElementById('select-assure').addEventListener('change', function() {
  const idAssure = this.value;
  const selectVehicule = document.getElementById('select-vehicule');

  if (!idAssure) {
    selectVehicule.innerHTML = '<option value="">-- Sélectionnez d\'abord un assuré --</option>';
    return;
  }

  selectVehicule.innerHTML = '<option value="">Chargement...</option>';

  fetch('vehicules_par_assure.php?id_assure=' + idAssure)
    .then(res => res.json())
    .then(vehicules => {
      if (vehicules.length === 0) {
        selectVehicule.innerHTML = '<option value="">Aucun véhicule pour cet assuré</option>';
        return;
      }
      let options = '<option value="">-- Sélectionner --</option>';
      vehicules.forEach(v => {
        options += `<option value="${v.id_vehicule}">${v.immatriculation} – ${v.marque} ${v.modele}</option>`;
      });
      selectVehicule.innerHTML = options;
    });
});
</script>
</body>
</html>