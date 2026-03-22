<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');


$gps_override = is_gps_override();
$users = get_all_users();
$total = count($users);

$llarga = count(array_filter($users, fn($u) => ($u['ruta'] ?? '') === 'llarga'));
$curta  = count(array_filter($users, fn($u) => ($u['ruta'] ?? '') === 'curta'));

$acabats = 0;
$en_ruta = 0;
foreach ($users as $u) {
    $cids = array_column($u['checkins'] ?? [], 'parada_id');
    if (in_array(10, $cids)) {
        $acabats++;
    } elseif (!empty($cids)) {
        $en_ruta++;
    }
}

// Estadístiques per parada
$parada_stats = [];
foreach ($PARADES as $p) {
    $count = 0;
    foreach ($users as $u) {
        $cids = array_column($u['checkins'] ?? [], 'parada_id');
        if (in_array($p['id'], $cids)) $count++;
    }
    $parada_stats[$p['id']] = $count;
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin — Montserrat 2026</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
</head>
<body>

<!-- NAVBAR ADMIN -->
<nav class="navbar navbar-spait px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
    <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
         height="32" alt="splaiT">
    <span>Panel Admin 2026</span>
  </a>
  <div class="ms-auto d-flex gap-2">
    <a href="mapa.php" class="btn btn-sm btn-outline-light">
      <i class="bi bi-geo-alt me-1"></i>Mapa
    </a>
    <a href="participants.php" class="btn btn-sm btn-outline-light">
      <i class="bi bi-people me-1"></i>Participants
    </a>
    <a href="export_csv.php" class="btn btn-sm btn-outline-warning">
      <i class="bi bi-download me-1"></i>CSV
    </a>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">
      <i class="bi bi-box-arrow-right me-1"></i>Sortir
    </a>
  </div>
</nav>

<div class="container-fluid py-4">
  <h2 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Resum General</h2>

  <!-- Targetes resum -->
  <div class="row g-3 mb-5">
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#2C3E50,#4a6a8a)">
        <div class="card-body text-white">
          <div class="stat-num"><?= $total ?></div>
          <div class="stat-label">Total inscrits</div>
          <i class="bi bi-people-fill stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#27AE60,#2ecc71)">
        <div class="card-body text-white">
          <div class="stat-num"><?= $llarga ?></div>
          <div class="stat-label">Ruta llarga</div>
          <i class="bi bi-signpost-2-fill stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#2980b9,#3498db)">
        <div class="card-body text-white">
          <div class="stat-num"><?= $curta ?></div>
          <div class="stat-label">Ruta curta</div>
          <i class="bi bi-signpost-fill stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#C0392B,#e74c3c)">
        <div class="card-body text-white">
          <div class="stat-num"><?= $acabats ?></div>
          <div class="stat-label">Han arribat!</div>
          <i class="bi bi-trophy-fill stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#8e44ad,#9b59b6)">
        <div class="card-body text-white">
          <div class="stat-num"><?= $en_ruta ?></div>
          <div class="stat-label">En ruta ara</div>
          <i class="bi bi-geo-alt-fill stat-icon"></i>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="stat-card card h-100 position-relative" style="background:linear-gradient(135deg,#F1C40F,#f39c12)">
        <div class="card-body" style="color:#2C3E50">
          <div class="stat-num"><?= $total > 0 ? round($acabats / $total * 100) : 0 ?>%</div>
          <div class="stat-label">Taxa finalització</div>
          <i class="bi bi-bar-chart-fill stat-icon"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Taula parades -->
  <div class="card shadow-sm mb-4">
    <div class="card-header" style="background:#2C3E50; color:white;">
      <h5 class="mb-0"><i class="bi bi-map me-2"></i>Participants per parada</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-striped mb-0">
          <thead class="table-participants">
            <tr>
              <th>#</th>
              <th>Parada</th>
              <th>Ruta</th>
              <th>Participants</th>
              <th>% del total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($PARADES as $p):
              $count = $parada_stats[$p['id']] ?? 0;
              $pct   = $total > 0 ? round($count / $total * 100) : 0;
            ?>
            <tr>
              <td><?= $p['id'] ?></td>
              <td><?= htmlspecialchars($p['nom']) ?></td>
              <td>
                <?php if ($p['ruta'] === 'llarga'): ?>
                  <span class="badge badge-ruta-llarga text-white">Llarga</span>
                <?php elseif ($p['ruta'] === 'curta'): ?>
                  <span class="badge badge-ruta-curta text-white">Curta</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Ambdues</span>
                <?php endif; ?>
              </td>
              <td><strong><?= $count ?></strong></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="progress progress-spait flex-grow-1" style="height:8px;">
                    <div class="progress-bar" style="width:<?= $pct ?>%"></div>
                  </div>
                  <span class="small"><?= $pct ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Control GPS Override -->
  <div class="card shadow-sm mb-4 border-<?= $gps_override ? 'warning' : 'secondary' ?>">
    <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3"
         style="background:<?= $gps_override ? '#fff8e1' : '#f8f9fa' ?>;">
      <div>
        <h5 class="mb-1">
          <i class="bi bi-geo-alt<?= $gps_override ? '-fill text-warning' : ' text-secondary' ?> me-2"></i>
          Restricció GPS del check-in
        </h5>
        <?php if ($gps_override): ?>
          <p class="mb-0 text-warning fw-semibold">
            <i class="bi bi-unlock-fill me-1"></i>
            Mode lliure activat — els participants poden fer check-in sense estar a prop de la parada.
          </p>
        <?php else: ?>
          <p class="mb-0 text-muted">
            <i class="bi bi-lock-fill me-1"></i>
            Normal — cal estar a menys de 200 m de la parada per activar el botó de check-in.
          </p>
        <?php endif; ?>
      </div>
      <a href="toggle_gps.php"
         class="btn btn-lg <?= $gps_override ? 'btn-warning' : 'btn-outline-secondary' ?>"
         onclick="return confirm('<?= $gps_override ? 'Desactivar el mode lliure i tornar a exigir GPS?' : 'Activar mode lliure? Els participants podran fer check-in sense GPS.' ?>')">
        <i class="bi bi-<?= $gps_override ? 'lock' : 'unlock' ?> me-2"></i>
        <?= $gps_override ? 'Desactivar mode lliure' : 'Activar mode lliure (sense GPS)' ?>
      </a>
    </div>
  </div>

  <!-- Accés ràpid -->
  <div class="d-flex flex-wrap gap-3">
    <a href="participants.php" class="btn btn-spait btn-lg">
      <i class="bi bi-people me-2"></i>Veure tots els participants
    </a>
    <a href="export_csv.php" class="btn btn-spait-groc btn-lg">
      <i class="bi bi-file-earmark-spreadsheet me-2"></i>Exportar CSV
    </a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
