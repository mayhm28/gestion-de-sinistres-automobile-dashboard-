<?php
require 'config/db.php';
require 'config/auth.php';
exiger_connexion('auth/login.php');

$initiales = '';
foreach (explode(' ', $_SESSION['user_nom']) as $mot) {
    $initiales .= mb_strtoupper(mb_substr($mot, 0, 1));
}

// ==========================================================
// Recuperation des donnees brutes
// ==========================================================
$sinistres = $pdo->query(
    "SELECT id_sinistre, numero_dossier, date_sinistre, date_cloture, type_sinistre, cout, statut
     FROM sinistres ORDER BY date_sinistre"
)->fetchAll(PDO::FETCH_ASSOC);

$totalSinistres = count($sinistres);
$maxCout = 1;
$tousLesCouts = [];
foreach ($sinistres as $s) { $maxCout = max($maxCout, (float)$s['cout']); $tousLesCouts[] = (float)$s['cout']; }

sort($tousLesCouts);
function quantile($arr, $q) {
    $n = count($arr);
    if ($n === 0) return 0;
    $pos = $q * ($n - 1);
    $base = (int)floor($pos);
    $rest = $pos - $base;
    if (isset($arr[$base + 1])) {
        return $arr[$base] + $rest * ($arr[$base + 1] - $arr[$base]);
    }
    return $arr[$base];
}
$q25 = quantile($tousLesCouts, 0.25);
$q50 = quantile($tousLesCouts, 0.50);
$q75 = quantile($tousLesCouts, 0.75);
$q90 = quantile($tousLesCouts, 0.90);

function processus($cout, $q50, $q75) {
    $cout = (float)$cout;
    if ($cout <= $q50) return 'P1 - Standard';
    if ($cout <= $q75) return 'P2 - Accéléré';
    return 'P3 - Complexe';
}
$slaJours = ['P1 - Standard' => 10, 'P2 - Accéléré' => 20, 'P3 - Complexe' => 40];

function severite($cout, $q25, $q50, $q75) {
    $cout = (float)$cout;
    if ($cout <= $q25) return 'Faible';
    if ($cout <= $q50) return 'Modérée';
    if ($cout <= $q75) return 'Élevée';
    return 'Extrême';
}
$bandesSeverite = ['Faible', 'Modérée', 'Élevée', 'Extrême'];

$today = new DateTime();
$delais = []; $charges = 0; $nbCloture = 0; $nbAlerte = 0;
$parMois = [];
$parType = [];
$parProcessus = [];
$matriceFreqSeverite = [];
$matriceCriticite = [];
$scores = [];

foreach ($sinistres as $s) {
    $dDeclaration = new DateTime($s['date_sinistre']);

    if ($s['statut'] === 'Clôturé' && !empty($s['date_cloture'])) {
        $dCloture = new DateTime($s['date_cloture']);
        $delaiJours = $dDeclaration->diff($dCloture)->days;
    } else {
        $delaiJours = $today->diff($dDeclaration)->days;
    }
    $delais[] = $delaiJours;
    $charges += (float)$s['cout'];
    if ($s['statut'] === 'Clôturé') $nbCloture++;
    if ($s['statut'] === 'Alerte') $nbAlerte++;

    $mois = $dDeclaration->format('Y-m');
    if (!isset($parMois[$mois])) $parMois[$mois] = ['ouverts'=>0,'clotures'=>0,'charge'=>0];
    $parMois[$mois]['ouverts']++;
    if ($s['statut'] === 'Clôturé') $parMois[$mois]['clotures']++;
    $parMois[$mois]['charge'] += (float)$s['cout'];

    if (!isset($parType[$s['type_sinistre']])) $parType[$s['type_sinistre']] = 0;
    $parType[$s['type_sinistre']] += (float)$s['cout'];

    $proc = processus($s['cout'], $q50, $q75);
    if (!isset($parProcessus[$proc])) $parProcessus[$proc] = ['delais'=>[], 'charge'=>0, 'n'=>0];
    $parProcessus[$proc]['delais'][] = $delaiJours;
    $parProcessus[$proc]['charge'] += (float)$s['cout'];
    $parProcessus[$proc]['n']++;

    $sev = severite($s['cout'], $q25, $q50, $q75);
    $matriceFreqSeverite[$s['type_sinistre']][$sev] = ($matriceFreqSeverite[$s['type_sinistre']][$sev] ?? 0) + 1;
    $matriceCriticite[$s['statut']][$sev] = ($matriceCriticite[$s['statut']][$sev] ?? 0) + 1;

    $normCout  = min(1, (float)$s['cout'] / $maxCout);
    $normDelai = min(1, $delaiJours / 60);
    $bonusAlerte = $s['statut'] === 'Alerte' ? 15 : 0;
    $score = min(100, round($normCout * 55 + $normDelai * 30 + $bonusAlerte));
    $scores[] = [
        'numero' => $s['numero_dossier'],
        'type'   => $s['type_sinistre'],
        'delai'  => $delaiJours,
        'cout'   => (float)$s['cout'],
        'statut' => $s['statut'],
        'score'  => $score,
    ];
}

ksort($parMois);
$delaiMoyen = $delais ? round(array_sum($delais) / count($delais), 1) : 0;
$tauxCloture = $totalSinistres ? round($nbCloture / $totalSinistres * 100, 1) : 0;
$coutUnitaire = $totalSinistres ? round($charges / $totalSinistres) : 0;

usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
$topAnomalies = array_slice($scores, 0, 8);
$nbAnomaliesScore = count(array_filter($scores, fn($s) => $s['score'] >= 65));

$perfProcessus = [];
foreach (['P1 - Standard', 'P2 - Accéléré', 'P3 - Complexe'] as $p) {
    $info = $parProcessus[$p] ?? ['delais'=>[], 'charge'=>0, 'n'=>0];
    $moy = $info['delais'] ? array_sum($info['delais']) / count($info['delais']) : 0;
    $cible = $slaJours[$p];
    $pct = $moy > 0 ? min(100, round($cible / max($moy, 1) * 100)) : 100;
    $perfProcessus[] = [
        'processus' => $p, 'delai_moyen' => round($moy, 1), 'cible' => $cible,
        'pct' => $pct, 'n' => $info['n'], 'charge' => round($info['charge']),
        'cout_moyen' => $info['n'] ? round($info['charge'] / $info['n']) : 0,
    ];
}

$moisParProcessus = [];
foreach ($sinistres as $s) {
    $mois = (new DateTime($s['date_sinistre']))->format('Y-m');
    $proc = processus($s['cout'], $q50, $q75);
    $moisParProcessus[$mois][$proc] = ($moisParProcessus[$mois][$proc] ?? 0) + (float)$s['cout'];
}
ksort($moisParProcessus);

$risqueSegments = [];
foreach ($sinistres as $s) {
    $proc = processus($s['cout'], $q50, $q75);
    $key = $proc . ' · ' . $s['type_sinistre'];
    if (!isset($risqueSegments[$key])) $risqueSegments[$key] = ['sum'=>0, 'n'=>0];
    $risqueSegments[$key]['sum'] += (float)$s['cout'];
    $risqueSegments[$key]['n']++;
}
$risqueScores = [];
foreach ($risqueSegments as $key => $v) {
    $moy = $v['sum'] / $v['n'];
    $score = min(100, round($moy / $maxCout * 100 + $v['n']));
    $risqueScores[$key] = $score;
}
arsort($risqueScores);
$risqueScores = array_slice($risqueScores, 0, 6, true);

$primesParAnnee = [];
try {
    $stmtPrimes = $pdo->query("SELECT annee, SUM(montant_prime) AS total FROM primes GROUP BY annee ORDER BY annee");
    foreach ($stmtPrimes->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $primesParAnnee[$row['annee']] = (float)$row['total'];
    }
} catch (PDOException $e) {
    $primesParAnnee = [];
}

$sinistresParAnnee = [];
foreach ($sinistres as $s) {
    $an = (new DateTime($s['date_sinistre']))->format('Y');
    $sinistresParAnnee[$an] = ($sinistresParAnnee[$an] ?? 0) + (float)$s['cout'];
}

$ratioSP = [];
foreach ($primesParAnnee as $an => $totalPrimes) {
    if ($totalPrimes > 0) {
        $ratioSP[$an] = round((($sinistresParAnnee[$an] ?? 0) / $totalPrimes) * 100, 1);
    }
}
$anneeCourante = array_key_last($ratioSP);
$ratioSPActuel = $anneeCourante ? $ratioSP[$anneeCourante] : null;
$seuilCritiqueSP = 70;

$chargeParRegion = [];
$sinistraliteMarque = [];
try {
    $chargeParRegion = $pdo->query("SELECT * FROM v_charge_par_region")->fetchAll(PDO::FETCH_ASSOC);
    $sinistraliteMarque = $pdo->query("SELECT * FROM v_sinistralite_marque WHERE nb_vehicules > 0")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Dashboard – Assurances BIAT</title>
  <link rel="stylesheet" href="assets/css/style.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
  <style>
    .tabs { display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap; }
    .tab-btn {
      padding:9px 16px; border-radius:8px; border:1px solid var(--border); background:var(--blanc);
      color:var(--vert-fonce); font-size:13px; font-weight:600; cursor:pointer;
    }
    .tab-btn.active { background:var(--vert-vif); color:var(--vert-fonce); border-color:var(--vert-vif); }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .kpi-grid-6 { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:18px; }
    @media (max-width:1100px) { .kpi-grid-6 { grid-template-columns:repeat(3,1fr); } }
    .sp-ok { color:var(--vert-clair); } .sp-bad { color:var(--danger); }
    .kpi-mini { background:var(--blanc); border:1px solid var(--border); border-radius:var(--radius); padding:14px; }
    .kpi-mini .lbl { font-size:11px; color:var(--gris-texte); text-transform:uppercase; letter-spacing:.4px; }
    .kpi-mini .val { font-size:22px; font-weight:800; color:var(--vert-fonce); margin-top:4px; }
    .charts-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
    @media (max-width:1000px) { .charts-grid-2 { grid-template-columns:1fr; } }
    .chart-card { background:var(--blanc); border:1px solid var(--border); border-radius:var(--radius); padding:16px; box-shadow:var(--shadow); }
    .chart-card h3 { font-size:13px; margin-bottom:12px; color:var(--vert-fonce); }
    .chart-wrap { position:relative; height:260px; }
    .matrice-table { width:100%; border-collapse:collapse; font-size:12px; }
    .matrice-table th, .matrice-table td { border:1px solid var(--border); padding:8px; text-align:center; }
    .matrice-table th { background:var(--gris-fond); }
    .cell-0 { background:#eef5e6; color:#7a8a70; }
    .cell-low { background:#e3f3d0; }
    .cell-mid { background:#fde7b0; }
    .cell-high { background:#f6c2c1; }
    .cell-crit { background:#e88c8a; color:#fff; font-weight:700; }
    .sla-row { display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:12px; }
    .sla-bar-bg { flex:1; height:8px; border-radius:6px; background:var(--gris-fond); overflow:hidden; }
    .sla-bar-fg { height:100%; background:var(--vert-vif); }
    .anomalie-row { display:flex; justify-content:space-between; align-items:center; padding:9px 0; border-bottom:1px solid var(--border); font-size:12px; }
    .score-badge { padding:3px 9px; border-radius:20px; font-weight:700; font-size:11px; color:#fff; }
  </style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-logo">
    <div class="sb-logo-icon">AB</div>
    <div class="sb-logo-text"><div class="brand">Assurances BIAT</div><div class="sub">Gestion des sinistres</div></div>
  </div>
  <div class="sb-section">Navigation</div>
  <a href="index.php"              class="nav-item active"><span class="icon">📊</span> Dashboard</a>
  <a href="sinistres/liste.php"    class="nav-item"><span class="icon">📁</span> Sinistres</a>
  <a href="assures/liste.php"      class="nav-item"><span class="icon">👤</span> Assurés</a>
  <a href="vehicules/liste.php"    class="nav-item"><span class="icon">🚗</span> Véhicules</a>
  <a href="experts/liste.php"      class="nav-item"><span class="icon">🔧</span> Experts</a>
  <a href="agences/liste.php"      class="nav-item"><span class="icon">🏢</span> Agences</a>
  <div class="sb-section">Admin</div>
  <a href="users/liste.php"        class="nav-item"><span class="icon">👥</span> Utilisateurs</a>
  <a href="alertes/index.php"      class="nav-item"><span class="icon">🔔</span> Alertes<span class="nav-badge">3</span></a>
  <a href="rapports/index.php"  class="nav-item"><span class="icon">📄</span> Rapports PDF</a>
  <div class="sb-section">Compte</div>
  <a href="auth/logout.php"         class="nav-item"><span class="icon">🚪</span> Déconnexion</a>
  <div class="sb-bottom">
    <div class="user-card">
      <div class="user-avatar"><?= htmlspecialchars($initiales) ?></div>
      <div><div class="user-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></div><div class="user-role"><?= htmlspecialchars($_SESSION['user_role']) ?></div></div>
    </div>
  </div>
</aside>

<div class="main">
  <header class="topbar">
    <div class="topbar-title">Dashboard analytique</div>
    <div class="topbar-right">
      <a href="alertes/index.php" class="btn-notif">🔔<span class="badge">3</span></a>
      <a href="sinistres/nouveau.php" class="btn btn-green">＋ Nouveau sinistre</a>
    </div>
  </header>

  <main class="content">

    <div class="tabs">
      <button class="tab-btn active" data-tab="global">📊 Vue globale</button>
      <button class="tab-btn" data-tab="risque">⚠ Analyse de risque</button>
      <button class="tab-btn" data-tab="processus">⚙ Performance processus</button>
      <button class="tab-btn" data-tab="anomalies">🔍 Anomalies &amp; drill-down</button>
      <button class="tab-btn" data-tab="geo">🗺 Région &amp; Marques</button>
    </div>

    <div class="kpi-grid-6" style="grid-template-columns:repeat(7,1fr);">
      <div class="kpi-mini"><div class="lbl">Total sinistres</div><div class="val"><?= $totalSinistres ?></div></div>
      <div class="kpi-mini"><div class="lbl">Taux clôture</div><div class="val"><?= $tauxCloture ?>%</div></div>
      <div class="kpi-mini"><div class="lbl">Délai moyen</div><div class="val"><?= $delaiMoyen ?> j</div></div>
      <div class="kpi-mini"><div class="lbl">Charge totale</div><div class="val"><?= number_format($charges,0,',',' ') ?> DT</div></div>
      <div class="kpi-mini"><div class="lbl">Coût unitaire</div><div class="val"><?= number_format($coutUnitaire,0,',',' ') ?> DT</div></div>
      <div class="kpi-mini"><div class="lbl">Anomalies</div><div class="val" style="color:var(--danger);"><?= $nbAnomaliesScore ?></div></div>
      <div class="kpi-mini">
        <div class="lbl">Ratio S/P (<?= htmlspecialchars($anneeCourante ?? '—') ?>)</div>
        <div class="val" style="color:<?= ($ratioSPActuel !== null && $ratioSPActuel > $seuilCritiqueSP) ? 'var(--danger)' : 'var(--vert-clair)' ?>;">
          <?= $ratioSPActuel !== null ? $ratioSPActuel . '%' : 'N/A' ?>
        </div>
      </div>
    </div>

    <div class="tab-panel active" id="tab-global">
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>📈 Flux mensuel — sinistres ouverts vs clôturés (+ charge)</h3>
          <div class="chart-wrap"><canvas id="chartFlux"></canvas></div>
        </div>
        <div class="chart-card">
          <h3>🍩 Charge réglée par type de sinistre</h3>
          <div class="chart-wrap"><canvas id="chartDonut"></canvas></div>
        </div>
      </div>
      <div class="chart-card">
        <h3>💰 Répartition de la charge par type (barres)</h3>
        <div class="chart-wrap"><canvas id="chartBarType"></canvas></div>
      </div>
      <?php if ($ratioSP): ?>
      <div class="chart-card">
        <h3>📐 Ratio S/P (Sinistres / Primes) par année — seuil critique : <?= $seuilCritiqueSP ?>%</h3>
        <div class="chart-wrap"><canvas id="chartRatioSP"></canvas></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="tab-panel" id="tab-risque">
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>🎯 Score de risque actuariel par segment</h3>
          <div class="chart-wrap"><canvas id="chartRisque"></canvas></div>
        </div>
        <div class="chart-card">
          <h3>🔥 Matrice fréquence (type) × sévérité (coût)</h3>
          <table class="matrice-table">
            <thead><tr><th>Type \ Sévérité</th><?php foreach ($bandesSeverite as $b): ?><th><?= $b ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php foreach ($parType as $type => $_): ?>
              <tr>
                <td style="text-align:left;font-weight:600;"><?= htmlspecialchars($type) ?></td>
                <?php foreach ($bandesSeverite as $b):
                  $c = $matriceFreqSeverite[$type][$b] ?? 0;
                  $cls = $c === 0 ? 'cell-0' : ($c <= 2 ? 'cell-low' : ($c <= 5 ? 'cell-mid' : 'cell-high'));
                ?><td class="<?= $cls ?>"><?= $c ?></td><?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <div class="chart-card">
        <h3>☁️ Délai (jours écoulés) vs montant réglé — bulle = coût</h3>
        <div class="chart-wrap"><canvas id="chartScatter"></canvas></div>
      </div>
    </div>

    <div class="tab-panel" id="tab-processus">
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>⏱ Performance par processus — délai vs SLA cible</h3>
          <?php foreach ($perfProcessus as $p): ?>
          <div class="sla-row">
            <span style="width:130px;"><?= $p['processus'] ?></span>
            <div class="sla-bar-bg"><div class="sla-bar-fg" style="width:<?= $p['pct'] ?>%;"></div></div>
            <span><?= $p['pct'] ?>% · <?= $p['delai_moyen'] ?>j / <?= $p['cible'] ?>j cible</span>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="chart-card">
          <h3>📊 Charge mensuelle par processus</h3>
          <div class="chart-wrap"><canvas id="chartChargeProcessus"></canvas></div>
        </div>
      </div>
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>💵 Coût moyen par processus</h3>
          <div class="chart-wrap"><canvas id="chartCoutProcessus"></canvas></div>
        </div>
        <div class="chart-card">
          <h3>✅ Indicateurs par processus</h3>
          <table class="matrice-table">
            <thead><tr><th>Processus</th><th>Dossiers</th><th>Charge totale</th><th>Coût moyen</th><th>Délai moyen</th></tr></thead>
            <tbody>
              <?php foreach ($perfProcessus as $p): ?>
              <tr>
                <td style="text-align:left;"><?= $p['processus'] ?></td>
                <td><?= $p['n'] ?></td>
                <td><?= number_format($p['charge'],0,',',' ') ?> DT</td>
                <td><?= number_format($p['cout_moyen'],0,',',' ') ?> DT</td>
                <td><?= $p['delai_moyen'] ?> j</td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="tab-anomalies">
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>🧯 Matrice de criticité — statut × sévérité (nb dossiers)</h3>
          <table class="matrice-table">
            <thead><tr><th>Statut \ Sévérité</th><?php foreach ($bandesSeverite as $b): ?><th><?= $b ?></th><?php endforeach; ?></tr></thead>
            <tbody>
              <?php foreach (['Ouvert','En cours','Alerte','Clôturé'] as $st): ?>
              <tr>
                <td style="text-align:left;font-weight:600;"><?= $st ?></td>
                <?php foreach ($bandesSeverite as $b):
                  $c = $matriceCriticite[$st][$b] ?? 0;
                  $crit = ($st === 'Alerte' && $c > 0);
                  $cls = $c === 0 ? 'cell-0' : ($crit ? 'cell-crit' : ($c <= 2 ? 'cell-low' : ($c <= 5 ? 'cell-mid' : 'cell-high')));
                ?><td class="<?= $cls ?>"><?= $c ?></td><?php endforeach; ?>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="chart-card">
          <h3>⚠ Dossiers anomalies — scoring dynamique</h3>
          <?php foreach ($topAnomalies as $a):
            $couleur = $a['score'] >= 80 ? 'var(--danger)' : ($a['score'] >= 60 ? 'var(--warning)' : 'var(--vert-clair)');
          ?>
          <div class="anomalie-row">
            <div><strong><?= htmlspecialchars($a['numero']) ?></strong> — <?= htmlspecialchars($a['type']) ?><br>
              <span style="color:var(--gris-texte);"><?= $a['delai'] ?>j · <?= number_format($a['cout'],0,',',' ') ?> DT · <?= htmlspecialchars($a['statut']) ?></span>
            </div>
            <span class="score-badge" style="background:<?= $couleur ?>;"><?= $a['score'] ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="tab-geo">
      <div class="charts-grid-2">
        <div class="chart-card">
          <h3>🗺 Charge des sinistres par région</h3>
          <div class="chart-wrap"><canvas id="chartRegion"></canvas></div>
        </div>
        <div class="chart-card">
          <h3>🚗 Taux de sinistralité par marque (nb sinistres ÷ nb véhicules)</h3>
          <div class="chart-wrap"><canvas id="chartMarque"></canvas></div>
        </div>
      </div>
      <div class="chart-card">
        <h3>📋 Détail par région</h3>
        <table class="matrice-table">
          <thead><tr><th>Région</th><th>Nb sinistres</th><th>Charge totale</th><th>Coût moyen</th></tr></thead>
          <tbody>
            <?php foreach ($chargeParRegion as $r): ?>
            <tr>
              <td style="text-align:left;font-weight:600;"><?= htmlspecialchars($r['region']) ?></td>
              <td><?= $r['nb_sinistres'] ?></td>
              <td><?= number_format($r['charge_totale'],0,',',' ') ?> DT</td>
              <td><?= number_format($r['cout_moyen'],0,',',' ') ?> DT</td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$chargeParRegion): ?>
            <tr><td colspan="4" style="color:var(--gris-texte);">Vue v_charge_par_region introuvable — exécute la migration correspondante.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>
</div>

<script>
const donneesFlux = <?= json_encode(array_values($parMois)) ?>;
const labelsFlux   = <?= json_encode(array_keys($parMois)) ?>;
const donneesType  = <?= json_encode($parType) ?>;
const risqueScores = <?= json_encode($risqueScores) ?>;
const scatterData  = <?= json_encode(array_map(fn($s) => ['x'=>$s['delai'],'y'=>$s['cout'],'r'=>max(4,min(20,$s['cout']/1500))], $scores)) ?>;
const moisProcessus = <?= json_encode($moisParProcessus) ?>;
const perfProcessus  = <?= json_encode(array_column($perfProcessus, 'cout_moyen', 'processus')) ?>;
const ratioSP = <?= json_encode($ratioSP) ?>;
const seuilCritiqueSP = <?= json_encode($seuilCritiqueSP) ?>;
const chargeParRegion = <?= json_encode($chargeParRegion) ?>;
const sinistraliteMarque = <?= json_encode($sinistraliteMarque) ?>;

const VERT_VIF = '#7DC429', VERT_CLAIR = '#4A8A1A', WARNING = '#F0A500', DANGER = '#E24B4A', BLEU = '#3B82C4', VIOLET = '#8B5CF6', TURQUOISE = '#14B8A6';
Chart.defaults.font.size = 11;

let chartsInit = { global: false, risque: false, processus: false, anomalies: false, geo: false };

function initGlobal() {
  new Chart(document.getElementById('chartFlux'), {
    type: 'bar',
    data: {
      labels: labelsFlux,
      datasets: [
        { type:'bar',  label:'Ouverts',   data: donneesFlux.map(d=>d.ouverts),  backgroundColor: BLEU },
        { type:'bar',  label:'Clôturés',  data: donneesFlux.map(d=>d.clotures), backgroundColor: VERT_VIF },
        { type:'line', label:'Charge (DT)', data: donneesFlux.map(d=>d.charge), borderColor: DANGER, yAxisID:'y1', tension:.3 },
      ]
    },
    options: { responsive:true, maintainAspectRatio:false,
      scales: { y:{ beginAtZero:true }, y1:{ position:'right', grid:{drawOnChartArea:false} } } }
  });

  new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: { labels: Object.keys(donneesType), datasets:[{ data: Object.values(donneesType),
      backgroundColor:[VERT_VIF,BLEU,WARNING,DANGER,VERT_CLAIR] }] },
    options: { responsive:true, maintainAspectRatio:false }
  });

  new Chart(document.getElementById('chartBarType'), {
    type: 'bar',
    data: { labels: Object.keys(donneesType), datasets:[{ label:'Charge (DT)', data: Object.values(donneesType), backgroundColor: [VERT_VIF, BLEU, WARNING, DANGER, VIOLET, TURQUOISE] }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  if (document.getElementById('chartRatioSP')) {
    const anneesSP = Object.keys(ratioSP);
    new Chart(document.getElementById('chartRatioSP'), {
      data: {
        labels: anneesSP,
        datasets: [
          { type:'bar', label:'Ratio S/P (%)', data: Object.values(ratioSP),
            backgroundColor: Object.values(ratioSP).map(v => v > seuilCritiqueSP ? DANGER : VERT_VIF) },
          { type:'line', label:'Seuil critique', data: anneesSP.map(() => seuilCritiqueSP),
            borderColor: WARNING, borderDash:[6,4], pointRadius:0 },
        ]
      },
      options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:true } } }
    });
  }
}

function initRisque() {
  new Chart(document.getElementById('chartRisque'), {
    type: 'bar',
    data: { labels: Object.keys(risqueScores), datasets:[{ label:'Score /100', data: Object.values(risqueScores), backgroundColor: [VIOLET, TURQUOISE, BLEU, WARNING, VERT_CLAIR, DANGER] }] },
    options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });

  new Chart(document.getElementById('chartScatter'), {
    type: 'bubble',
    data: { datasets:[{ label:'Dossiers', data: scatterData, backgroundColor:'rgba(139,92,246,0.55)' }] },
    options: { responsive:true, maintainAspectRatio:false,
      scales: { x:{ title:{display:true,text:'Délai (jours)'} }, y:{ title:{display:true,text:'Montant (DT)'} } },
      plugins:{legend:{display:false}} }
  });
}

function initProcessus() {
  const moisLabels = Object.keys(moisProcessus);
  const procs = ['P1 - Standard','P2 - Accéléré','P3 - Complexe'];
  const couleurs = { 'P1 - Standard':VERT_VIF, 'P2 - Accéléré':WARNING, 'P3 - Complexe':DANGER };
  new Chart(document.getElementById('chartChargeProcessus'), {
    type: 'bar',
    data: { labels: moisLabels, datasets: procs.map(p => ({
      label: p, data: moisLabels.map(m => moisProcessus[m][p] || 0), backgroundColor: couleurs[p]
    })) },
    options: { responsive:true, maintainAspectRatio:false, scales:{ x:{stacked:true}, y:{stacked:true} } }
  });

  new Chart(document.getElementById('chartCoutProcessus'), {
    type: 'line',
    data: { labels: Object.keys(perfProcessus), datasets:[{ label:'Coût moyen (DT)', data: Object.values(perfProcessus),
      borderColor: BLEU, backgroundColor:'rgba(59,130,196,0.15)', fill:true, tension:.3 }] },
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
  });
}

function initGeo() {
  if (chargeParRegion.length) {
    new Chart(document.getElementById('chartRegion'), {
      type: 'bar',
      data: {
        labels: chargeParRegion.map(r => r.region),
        datasets: [{ label: 'Charge totale (DT)', data: chargeParRegion.map(r => r.charge_totale),
          backgroundColor: [BLEU, VIOLET, TURQUOISE, WARNING, VERT_VIF, DANGER, VERT_CLAIR] }]
      },
      options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
    });
  }
  if (sinistraliteMarque.length) {
    new Chart(document.getElementById('chartMarque'), {
      type: 'bar',
      data: {
        labels: sinistraliteMarque.map(m => m.marque),
        datasets: [{ label: 'Taux de sinistralité', data: sinistraliteMarque.map(m => m.taux_sinistralite),
          backgroundColor: sinistraliteMarque.map(m => m.taux_sinistralite >= 2 ? DANGER : (m.taux_sinistralite >= 1 ? WARNING : VERT_VIF)) }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
    });
  }
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    const tab = btn.dataset.tab;
    document.getElementById('tab-' + tab).classList.add('active');
    if (!chartsInit[tab]) {
      if (tab === 'global') initGlobal();
      if (tab === 'risque') initRisque();
      if (tab === 'processus') initProcessus();
      if (tab === 'geo') initGeo();
      chartsInit[tab] = true;
    }
  });
});
initGlobal();
chartsInit.global = true;
</script>
</body>
</html>