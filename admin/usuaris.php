<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings = get_settings();
$parades  = $settings['parades'] ?? $PARADES;
$logo     = ($settings['visual']['logo_local'] ?? '') ?: ($settings['visual']['logo_url'] ?? '');

$users    = get_all_users();
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

// Mapa parada_id → nom
$parades_map = [];
foreach ($parades as $p) $parades_map[$p['id']] = $p;
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuaris — Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-spait navbar-expand-lg px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
    <img src="<?= htmlspecialchars($logo) ?>" height="32" alt="splaiT">
    <span>Admin</span>
  </a>
  <button class="navbar-toggler border-light" type="button" data-bs-toggle="collapse" data-bs-target="#navAdmin">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navAdmin">
    <div class="navbar-nav ms-auto d-flex flex-row flex-wrap gap-1 align-items-center">
      <a href="dashboard.php" class="btn btn-sm btn-outline-light"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
      <a href="mapa.php" class="btn btn-sm btn-outline-light"><i class="bi bi-geo-alt me-1"></i>Mapa</a>
      <a href="usuaris.php" class="btn btn-sm btn-light"><i class="bi bi-people me-1"></i>Usuaris</a>
      <a href="parades.php" class="btn btn-sm btn-outline-light"><i class="bi bi-pin-map me-1"></i>Parades</a>
      <a href="configuracio.php" class="btn btn-sm btn-outline-light"><i class="bi bi-gear me-1"></i>Configuració</a>
      <a href="export_csv.php" class="btn btn-sm btn-outline-warning"><i class="bi bi-download me-1"></i>CSV</a>
      <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Sortir</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4">

  <!-- Toast -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="toast-ok" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle me-2"></i><span id="toast-msg">Fet!</span></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <!-- ZONA DE PERILL -->
  <div class="card border-danger mb-4">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center"
         data-bs-toggle="collapse" data-bs-target="#zona-perill" style="cursor:pointer">
      <span><i class="bi bi-exclamation-triangle-fill me-2"></i>Zona de perill — Eliminació d'usuaris</span>
      <i class="bi bi-chevron-down"></i>
    </div>
    <div class="collapse" id="zona-perill">
      <div class="card-body bg-danger bg-opacity-10">
        <div class="row g-3">

          <div class="col-md-4">
            <div class="card h-100 border-warning">
              <div class="card-body text-center">
                <h6 class="fw-bold"><i class="bi bi-eraser me-1"></i>Eliminar usuaris de prova</h6>
                <p class="small text-muted">Elimina els participants registrats ABANS de la data de l'esdeveniment (<?= htmlspecialchars($settings['event']['data_esdeveniment'] ?? '—') ?>)</p>
                <button class="btn btn-warning btn-sm" id="btn-delete-test">
                  <i class="bi bi-eraser me-1"></i>Eliminar proves
                </button>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card h-100 border-danger">
              <div class="card-body text-center">
                <h6 class="fw-bold"><i class="bi bi-trash3 me-1"></i>Eliminar TOTS els usuaris</h6>
                <p class="small text-muted">Esborra tots els participants. Útil per preparar l'app per a un nou any.</p>
                <button class="btn btn-danger btn-sm" id="btn-delete-all">
                  <i class="bi bi-trash3 me-1"></i>Eliminar tots
                </button>
              </div>
            </div>
          </div>

          <div class="col-md-4">
            <div class="card h-100 border-danger">
              <div class="card-body text-center">
                <h6 class="fw-bold"><i class="bi bi-arrow-clockwise me-1"></i>Reset per nou any</h6>
                <p class="small text-muted">Elimina tots els participants i reseteja el codi mestre. Manté la configuració.</p>
                <button class="btn btn-danger btn-sm" id="btn-reset-year">
                  <i class="bi bi-arrow-clockwise me-1"></i>Nou any
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- LLISTA D'USUARIS -->
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
              $ruta   = $u['ruta'] ?? 'curta';
              $prog   = get_user_progress($u, $parades);
              $acabat = $prog['acabat'];

              $ultima_parada = '';
              if (!empty($u['checkins'])) {
                  $last_ci       = end($u['checkins']);
                  $ultima_parada = $parades_map[$last_ci['parada_id']]['nom'] ?? 'Parada ' . $last_ci['parada_id'];
              }
              $created = !empty($u['created_at']) ? date('d/m/Y H:i', strtotime($u['created_at'])) : '—';
            ?>
            <tr class="fila-participant">
              <td>
                <strong><?= htmlspecialchars($u['nom']) ?></strong>
                <?php if ($acabat): ?>
                  <span class="badge bg-warning text-dark ms-1">🏆</span>
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
              <td><small><?= $created ?></small></td>
              <td>
                <a href="participant_detail.php?id=<?= urlencode($u['id']) ?>"
                   class="btn btn-sm btn-outline-primary me-1" title="Veure detall">
                  <i class="bi bi-eye"></i>
                </a>
                <a href="participant_detail.php?id=<?= urlencode($u['id']) ?>&reset=1"
                   class="btn btn-sm btn-outline-warning me-1" title="Reset contrasenya"
                   onclick="return confirm('Generar nova contrasenya per <?= htmlspecialchars(addslashes($u['nom'])) ?>?')">
                  <i class="bi bi-key"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger btn-del-user"
                        data-id="<?= htmlspecialchars($u['id']) ?>"
                        data-nom="<?= htmlspecialchars(addslashes($u['nom'])) ?>"
                        title="Eliminar usuari">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php if (empty($users)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-people fs-1 d-block mb-2"></i>
      Encara no hi ha participants registrats.
    </div>
  <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const toastEl  = document.getElementById('toast-ok');
const toastMsg = document.getElementById('toast-msg');
const toastBS  = new bootstrap.Toast(toastEl, { delay: 3000 });

function showToast(msg, type = 'success') {
  toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
  toastMsg.textContent = msg;
  toastBS.show();
}

// Cerca en temps real
document.getElementById('cerca').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.fila-participant').forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// Ordenació
document.querySelectorAll('.sortable').forEach(th => {
  let asc = true;
  th.addEventListener('click', () => {
    const col   = parseInt(th.dataset.col);
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

// Eliminar usuari individual
document.querySelectorAll('.btn-del-user').forEach(btn => {
  btn.addEventListener('click', function() {
    const id  = this.dataset.id;
    const nom = this.dataset.nom;
    if (!confirm(`Eliminar l'usuari "${nom}"? Aquesta acció no es pot desfer.`)) return;

    fetch(`api/delete_user.php?id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          showToast(`"${nom}" eliminat correctament`, 'success');
          setTimeout(() => location.reload(), 1200);
        } else {
          showToast('Error eliminant l\'usuari', 'danger');
        }
      });
  });
});

// Eliminar usuaris de prova
document.getElementById('btn-delete-test').addEventListener('click', function() {
  if (!confirm('Eliminar els participants registrats ABANS de la data de l\'esdeveniment?')) return;
  fetch('api/delete_test_users.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showToast(`${data.deleted} usuaris de prova eliminats`, 'warning');
        setTimeout(() => location.reload(), 1500);
      }
    });
});

// Eliminar tots
document.getElementById('btn-delete-all').addEventListener('click', function() {
  if (!confirm('Segur que vols eliminar TOTS els participants? Aquesta acció no es pot desfer.')) return;
  const paraula = prompt('Escriu ELIMINAR per confirmar:');
  if (paraula !== 'ELIMINAR') { alert('Acció cancel·lada.'); return; }

  fetch('api/delete_all_users.php', { method: 'POST' })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showToast(`${data.deleted} usuaris eliminats`, 'danger');
        setTimeout(() => location.reload(), 1500);
      }
    });
});

// Reset per nou any
document.getElementById('btn-reset-year').addEventListener('click', function() {
  if (!confirm('Reset per nou any: eliminar TOTS els participants i reseteja el codi mestre?')) return;
  const paraula = prompt('Escriu RESET per confirmar:');
  if (paraula !== 'RESET') { alert('Acció cancel·lada.'); return; }

  Promise.all([
    fetch('api/delete_all_users.php', { method: 'POST' }).then(r => r.json()),
    fetch('api/save_settings.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ section: 'checkin', data: { require_gps: false, radi_metres: 200, codi_mestre: '' } })
    }).then(r => r.json())
  ]).then(([del, cfg]) => {
    if (del.ok) {
      showToast(`Reset completat: ${del.deleted} usuaris eliminats`, 'warning');
      setTimeout(() => location.reload(), 1800);
    }
  });
});
</script>
</body>
</html>
