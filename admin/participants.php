<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');


$users = get_all_users();

// Ordenar per data de registre (més recent primer)
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$parades_map = [];
foreach ($PARADES as $p) { $parades_map[$p['id']] = $p; }
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Participants — Admin Montserrat 2026</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-spait px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
    <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
         height="32" alt="splaiT">
    <span>Panel Admin 2026</span>
  </a>
  <div class="ms-auto d-flex gap-2">
    <a href="dashboard.php" class="btn btn-sm btn-outline-light">
      <i class="bi bi-speedometer2 me-1"></i>Dashboard
    </a>
    <a href="export_csv.php" class="btn btn-sm btn-outline-warning">
      <i class="bi bi-download me-1"></i>CSV
    </a>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</nav>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-people me-2"></i>Participants
      <span class="badge bg-secondary ms-2"><?= count($users) ?></span>
    </h2>
    <div class="input-group" style="max-width:320px">
      <span class="input-group-text"><i class="bi bi-search"></i></span>
      <input type="text" class="form-control" id="cerca" placeholder="Cerca per nom, email o telèfon...">
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="taula-participants">
          <thead class="table-participants">
            <tr>
              <th class="sortable" data-col="0" style="cursor:pointer">Nom <i class="bi bi-arrow-down-up"></i></th>
              <th>Contacte</th>
              <th class="sortable" data-col="2" style="cursor:pointer">Ruta <i class="bi bi-arrow-down-up"></i></th>
              <th>Progrés</th>
              <th>Última parada</th>
              <th class="sortable" data-col="5" style="cursor:pointer">Registre <i class="bi bi-arrow-down-up"></i></th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u):
              $ruta  = $u['ruta'] ?? 'curta';
              $cids  = array_column($u['checkins'] ?? [], 'parada_id');
              $prog  = get_user_progress($u, $PARADES);
              $acabat = $prog['acabat'];

              // Última parada
              $ultima_parada = '';
              if (!empty($u['checkins'])) {
                  $last_ci = end($u['checkins']);
                  $ultima_parada = $parades_map[$last_ci['parada_id']]['nom'] ?? '';
              }
              $created = $u['created_at'] ? date('d/m/Y H:i', strtotime($u['created_at'])) : '—';
            ?>
            <tr class="fila-participant">
              <td>
                <strong><?= htmlspecialchars($u['nom']) ?></strong>
                <?php if ($acabat): ?>
                  <span class="badge bg-warning text-dark ms-1">🏆 Muntanya!</span>
                <?php endif; ?>
              </td>
              <td>
                <small>
                  <?php if (!empty($u['email'])): ?>
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($u['email']) ?><br>
                  <?php endif; ?>
                  <?php if (!empty($u['telefon'])): ?>
                    <i class="bi bi-phone me-1"></i><?= htmlspecialchars($u['telefon']) ?>
                  <?php endif; ?>
                </small>
              </td>
              <td>
                <?php if ($ruta === 'llarga'): ?>
                  <span class="badge badge-ruta-llarga text-white">Llarga</span>
                <?php else: ?>
                  <span class="badge badge-ruta-curta text-white">Curta</span>
                <?php endif; ?>
              </td>
              <td style="min-width:140px">
                <div class="d-flex align-items-center gap-2">
                  <div class="progress progress-spait flex-grow-1" style="height:10px;">
                    <div class="progress-bar" style="width:<?= $prog['percent'] ?>%"></div>
                  </div>
                  <small><?= $prog['completades'] ?>/<?= $prog['total'] ?></small>
                </div>
              </td>
              <td>
                <small class="text-muted"><?= htmlspecialchars(mb_strimwidth($ultima_parada, 0, 30, '...')) ?></small>
              </td>
              <td>
                <small><?= $created ?></small>
              </td>
              <td>
                <a href="participant_detail.php?id=<?= urlencode($u['id']) ?>"
                   class="btn btn-sm btn-outline-primary me-1" title="Veure detall">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="participant_detail.php?id=<?= urlencode($u['id']) ?>&reset=1"
                   class="btn btn-sm btn-outline-warning" title="Reset contrasenya"
                   onclick="return confirm('Generar nova contrasenya per <?= htmlspecialchars(addslashes($u['nom'])) ?>?')">
                  <i class="bi bi-key"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cerca en temps real
document.getElementById('cerca').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.fila-participant').forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(q) ? '' : 'none';
  });
});

// Ordenació de columnes
document.querySelectorAll('.sortable').forEach(th => {
  let asc = true;
  th.addEventListener('click', () => {
    const col  = parseInt(th.dataset.col);
    const tbody = document.querySelector('#taula-participants tbody');
    const rows  = Array.from(tbody.querySelectorAll('tr'));
    rows.sort((a, b) => {
      const va = a.cells[col]?.textContent.trim() ?? '';
      const vb = b.cells[col]?.textContent.trim() ?? '';
      return asc ? va.localeCompare(vb) : vb.localeCompare(va);
    });
    rows.forEach(r => tbody.appendChild(r));
    asc = !asc;
  });
});
</script>
</body>
</html>
