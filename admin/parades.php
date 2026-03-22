<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings = get_settings();
$parades  = $settings['parades'] ?? [];
$rutes    = $settings['rutes']   ?? [];
$logo     = ($settings['visual']['logo_local'] ?? '') ?: ($settings['visual']['logo_url'] ?? '');

// Mapa ruta id → nom
$rutes_nom = [];
foreach ($rutes as $r) $rutes_nom[$r['id']] = $r['nom'];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Parades — Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
  <style>
    .drag-handle { cursor: grab; color: #adb5bd; font-size: 1.2rem; }
    .drag-handle:active { cursor: grabbing; }
    .parada-card { transition: box-shadow .2s; }
    .parada-card.sortable-ghost { opacity: 0.4; }
  </style>
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
      <a href="usuaris.php" class="btn btn-sm btn-outline-light"><i class="bi bi-people me-1"></i>Usuaris</a>
      <a href="parades.php" class="btn btn-sm btn-light"><i class="bi bi-pin-map me-1"></i>Parades</a>
      <a href="configuracio.php" class="btn btn-sm btn-outline-light"><i class="bi bi-gear me-1"></i>Configuració</a>
      <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Sortir</a>
    </div>
  </div>
</nav>

<div class="container-fluid py-4" style="max-width:900px">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h2 class="mb-0"><i class="bi bi-pin-map me-2"></i>Parades
      <span class="badge bg-secondary ms-2"><?= count($parades) ?></span>
    </h2>
    <a href="parada_edit.php" class="btn btn-spait">
      <i class="bi bi-plus-circle me-2"></i>Nova parada
    </a>
  </div>

  <!-- Toast -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="toast-ok" class="toast align-items-center text-white bg-success border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle me-2"></i><span id="toast-msg">Fet!</span></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <div id="parades-list">
    <?php foreach ($parades as $p):
      $rutes_p = $p['rutes'] ?? [];
      if (empty($rutes_p) && isset($p['ruta'])) {
          $rutes_p = $p['ruta'] === 'ambdues' ? ['llarga', 'curta'] : [$p['ruta']];
      }
      $n_preguntes = count($p['preguntes'] ?? []);
      $es_final  = !empty($p['es_final']) || !empty($p['final']);
      $es_inici  = !empty($p['es_inici']) || !empty($p['inici']);
    ?>
    <div class="card parada-card shadow-sm mb-2" data-id="<?= $p['id'] ?>">
      <div class="card-body py-2 px-3 d-flex align-items-center gap-3">

        <!-- Handle arrossegament -->
        <span class="drag-handle"><i class="bi bi-grip-vertical"></i></span>

        <!-- ID -->
        <span class="badge bg-dark" style="min-width:32px"><?= $p['id'] ?></span>

        <!-- Info principal -->
        <div class="flex-grow-1">
          <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
            <strong><?= htmlspecialchars($p['nom']) ?></strong>
            <?php if ($es_inici): ?>
              <span class="badge bg-success">Inici</span>
            <?php endif; ?>
            <?php if (!empty($p['es_inici_ruta'])): ?>
              <span class="badge bg-info text-dark">Inici <?= htmlspecialchars($p['es_inici_ruta']) ?></span>
            <?php endif; ?>
            <?php if ($es_final): ?>
              <span class="badge bg-warning text-dark">Meta</span>
            <?php endif; ?>
          </div>
          <small class="text-muted">
            <i class="bi bi-geo me-1"></i><?= number_format($p['lat'] ?? 0, 5) ?>, <?= number_format($p['lng'] ?? 0, 5) ?>
            &bull; <i class="bi bi-key me-1"></i><?= $p['codi'] ? htmlspecialchars($p['codi']) : '<em>sense codi</em>' ?>
            &bull; <i class="bi bi-question-circle me-1"></i><?= $n_preguntes ?> preguntes
          </small>
        </div>

        <!-- Rutes badges -->
        <div class="d-flex flex-column gap-1 text-end">
          <?php foreach ($rutes_p as $r): ?>
            <span class="badge <?= $r === 'llarga' ? 'badge-ruta-llarga' : 'badge-ruta-curta' ?> text-white">
              <?= htmlspecialchars($rutes_nom[$r] ?? $r) ?>
            </span>
          <?php endforeach; ?>
        </div>

        <!-- Accions -->
        <div class="d-flex gap-1">
          <a href="parada_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
            <i class="bi bi-pencil"></i>
          </a>
          <button class="btn btn-sm btn-outline-danger btn-eliminar" data-id="<?= $p['id'] ?>"
                  data-nom="<?= htmlspecialchars(addslashes($p['nom'])) ?>" title="Eliminar">
            <i class="bi bi-trash"></i>
          </button>
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if (empty($parades)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-pin-map fs-1 d-block mb-2"></i>
      Encara no hi ha parades. <a href="parada_edit.php">Crea la primera!</a>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
const toastEl  = document.getElementById('toast-ok');
const toastMsg = document.getElementById('toast-msg');
const toastBS  = new bootstrap.Toast(toastEl, { delay: 3000 });

function showToast(msg, type = 'success') {
  toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
  toastMsg.textContent = msg;
  toastBS.show();
}

// Drag & Drop ordenació
Sortable.create(document.getElementById('parades-list'), {
  handle: '.drag-handle',
  animation: 150,
  ghostClass: 'sortable-ghost',
  onEnd: function() {
    const order = [...document.querySelectorAll('.parada-card')]
      .map(el => el.dataset.id);
    fetch('api/reorder_parades.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ order })
    }).then(r => r.json()).then(data => {
      if (data.ok) showToast('Ordre desat!', 'success');
      else         showToast('Error desant l\'ordre', 'danger');
    });
  }
});

// Eliminar parada
document.querySelectorAll('.btn-eliminar').forEach(btn => {
  btn.addEventListener('click', function() {
    const id  = this.dataset.id;
    const nom = this.dataset.nom;
    if (!confirm(`Segur que vols eliminar la parada "${nom}"?\n\nEls check-ins existents d'aquesta parada es conservaran als participants.`)) return;

    fetch('api/delete_parada.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    }).then(r => r.json()).then(data => {
      if (data.ok) {
        showToast('Parada eliminada', 'success');
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast('Error eliminant la parada', 'danger');
      }
    });
  });
});
</script>
</body>
</html>
