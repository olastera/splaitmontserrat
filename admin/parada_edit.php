<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings = get_settings();
$parades  = $settings['parades'] ?? [];
$rutes    = $settings['rutes']   ?? [];
$logo     = ($settings['visual']['logo_local'] ?? '') ?: ($settings['visual']['logo_url'] ?? '');

// Mode edició o creació?
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$parada  = null;
if ($edit_id !== null) {
    foreach ($parades as $p) {
        if ($p['id'] === $edit_id) { $parada = $p; break; }
    }
}

$is_new = ($parada === null);
$parada = $parada ?? [
    'id'               => max(array_column($parades, 'id') ?: [0]) + 1,
    'nom'              => '',
    'rutes'            => [],
    'lat'              => 41.5,
    'lng'              => 1.9,
    'codi'             => '',
    'radi_metres'      => null,
    'es_inici'         => false,
    'es_final'         => false,
    'missatge_arribada' => '',
    'preguntes'        => [],
];

// Normalitzar rutes (format antic → nou)
if (empty($parada['rutes']) && isset($parada['ruta'])) {
    $parada['rutes'] = $parada['ruta'] === 'ambdues' ? ['llarga', 'curta'] : [$parada['ruta']];
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $is_new ? 'Nova parada' : 'Editar parada' ?> — Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
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
      <a href="parades.php" class="btn btn-sm btn-outline-light"><i class="bi bi-arrow-left me-1"></i>Parades</a>
      <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Sortir</a>
    </div>
  </div>
</nav>

<div class="container py-4" style="max-width:820px">

  <h2 class="mb-4">
    <i class="bi bi-<?= $is_new ? 'plus-circle' : 'pencil' ?> me-2"></i>
    <?= $is_new ? 'Nova parada' : 'Editar: ' . htmlspecialchars($parada['nom']) ?>
  </h2>

  <!-- Toast -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="toast-ok" class="toast align-items-center text-white bg-success border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle me-2"></i><span id="toast-msg">Desat!</span></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <input type="hidden" id="parada-id" value="<?= $parada['id'] ?>">

  <!-- SECCIÓ 1: Dades bàsiques -->
  <div class="card shadow-sm mb-4">
    <div class="card-header" style="background:#2C3E50; color:white;">
      <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Dades bàsiques</h5>
    </div>
    <div class="card-body">

      <div class="mb-3">
        <label class="form-label fw-semibold">Nom de la parada <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="p_nom" value="<?= htmlspecialchars($parada['nom']) ?>" required>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Flags</label>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="p_inici" <?= !empty($parada['es_inici']) || !empty($parada['inici']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="p_inici">És punt d'inici de ruta</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="p_final" <?= !empty($parada['es_final']) || !empty($parada['final']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="p_final">És la meta / punt final</label>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Rutes que hi passen</label>
          <?php foreach ($rutes as $r): ?>
          <div class="form-check">
            <input class="form-check-input ruta-check" type="checkbox"
                   id="ruta_<?= $r['id'] ?>" value="<?= $r['id'] ?>"
                   <?= in_array($r['id'], $parada['rutes'] ?? []) ? 'checked' : '' ?>>
            <label class="form-check-label" for="ruta_<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Codi secret</label>
          <div class="input-group">
            <input type="text" class="form-control text-uppercase" id="p_codi"
                   value="<?= htmlspecialchars($parada['codi'] ?? '') ?>"
                   placeholder="Deixa en blanc per parades sense codi"
                   style="letter-spacing:2px;">
            <button class="btn btn-outline-secondary" type="button" onclick="generarCodi()">
              <i class="bi bi-shuffle"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" onclick="toggleCodi()">
              <i class="bi bi-eye" id="icon-codi"></i>
            </button>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Radi GPS (metres)</label>
          <input type="number" class="form-control" id="p_radi" min="50" max="5000"
                 value="<?= $parada['radi_metres'] ?? '' ?>" placeholder="200 per defecte">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Missatge en arribar</label>
        <input type="text" class="form-control" id="p_missatge"
               value="<?= htmlspecialchars($parada['missatge_arribada'] ?? '') ?>"
               placeholder="Molt bé! Endavant! 💪">
      </div>
    </div>
  </div>

  <!-- SECCIÓ 2: Coordenades GPS -->
  <div class="card shadow-sm mb-4">
    <div class="card-header" style="background:#2C3E50; color:white;">
      <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Coordenades GPS</h5>
    </div>
    <div class="card-body">
      <div id="edit-map" style="height:350px; border-radius:8px; border:1px solid #dee2e6;"></div>
      <div class="row mt-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Latitud</label>
          <input type="number" id="p_lat" class="form-control"
                 step="0.000001" value="<?= $parada['lat'] ?? '' ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Longitud</label>
          <input type="number" id="p_lng" class="form-control"
                 step="0.000001" value="<?= $parada['lng'] ?? '' ?>">
        </div>
      </div>
      <small class="text-muted">Clica al mapa per seleccionar la ubicació, o introdueix les coordenades manualment.</small>
    </div>
  </div>

  <!-- SECCIÓ 3: Preguntes del test -->
  <div class="card shadow-sm mb-4">
    <div class="card-header" style="background:#2C3E50; color:white;">
      <h5 class="mb-0"><i class="bi bi-question-circle me-2"></i>Preguntes del test</h5>
    </div>
    <div class="card-body">
      <div id="preguntes-container">
        <!-- Les preguntes es generen amb JS -->
      </div>
      <button class="btn btn-outline-secondary btn-sm" id="btn-add-pregunta">
        <i class="bi bi-plus-circle me-1"></i>Afegir pregunta
      </button>
      <small class="text-muted ms-2">Fins a 5 preguntes per parada.</small>
    </div>
  </div>

  <!-- Botons acció -->
  <div class="d-flex gap-3 flex-wrap">
    <button class="btn btn-spait btn-lg" onclick="desarParada()">
      <i class="bi bi-floppy me-2"></i>Desar parada
    </button>
    <a href="parades.php" class="btn btn-outline-secondary btn-lg">
      <i class="bi bi-x-circle me-2"></i>Cancel·lar
    </a>
    <?php if (!$is_new): ?>
    <button class="btn btn-outline-danger btn-lg ms-auto" onclick="eliminarParada()">
      <i class="bi bi-trash me-2"></i>Eliminar parada
    </button>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Dades inicials
const IS_NEW    = <?= json_encode($is_new) ?>;
const PARADA_ID = <?= json_encode($parada['id']) ?>;
const INIT_PREGUNTES = <?= json_encode($parada['preguntes'] ?? []) ?>;

// Toast
const toastEl  = document.getElementById('toast-ok');
const toastMsg = document.getElementById('toast-msg');
const toastBS  = new bootstrap.Toast(toastEl, { delay: 3000 });
function showToast(msg, type = 'success') {
  toastEl.className = `toast align-items-center text-white bg-${type} border-0`;
  toastMsg.textContent = msg;
  toastBS.show();
}

// ============= MAPA =============
const editMap = L.map('edit-map').setView([41.5, 1.9], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap'
}).addTo(editMap);

let editMarker = null;

function setMapMarker(lat, lng) {
  if (editMarker) editMap.removeLayer(editMarker);
  editMarker = L.marker([lat, lng]).addTo(editMap);
  editMap.setView([lat, lng], 15);
}

const initLat = parseFloat(document.getElementById('p_lat').value);
const initLng = parseFloat(document.getElementById('p_lng').value);
if (initLat && initLng) setMapMarker(initLat, initLng);

editMap.on('click', function(e) {
  const { lat, lng } = e.latlng;
  document.getElementById('p_lat').value = lat.toFixed(6);
  document.getElementById('p_lng').value = lng.toFixed(6);
  setMapMarker(lat, lng);
});

['p_lat', 'p_lng'].forEach(id => {
  document.getElementById(id).addEventListener('change', function() {
    const lat = parseFloat(document.getElementById('p_lat').value);
    const lng = parseFloat(document.getElementById('p_lng').value);
    if (lat && lng) setMapMarker(lat, lng);
  });
});

// ============= PREGUNTES =============
const MAX_PREGUNTES = 5;
let preguntes = JSON.parse(JSON.stringify(INIT_PREGUNTES));

function renderPreguntes() {
  const container = document.getElementById('preguntes-container');
  container.innerHTML = '';

  preguntes.forEach((q, idx) => {
    const div = document.createElement('div');
    div.className = 'border rounded p-3 mb-3';
    div.innerHTML = `
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Pregunta ${idx + 1}</strong>
        <button type="button" class="btn btn-sm btn-outline-danger btn-del-q" data-idx="${idx}">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <div class="mb-2">
        <input type="text" class="form-control q-text" data-idx="${idx}"
               value="${escapeHtml(q.text || '')}" placeholder="Escriu la pregunta...">
      </div>
      <div class="mb-2">
        <select class="form-select q-tipus" data-idx="${idx}">
          <option value="opcions"  ${q.tipus === 'opcions'  ? 'selected' : ''}>Opcions (selecció única)</option>
          <option value="text"     ${q.tipus === 'text'     ? 'selected' : ''}>Text lliure</option>
          <option value="estrelles" ${q.tipus === 'estrelles' ? 'selected' : ''}>Estrelles 1–5</option>
        </select>
      </div>
      ${q.tipus === 'opcions' ? renderOpcions(q.opcions || [], idx) : ''}
    `;
    container.appendChild(div);
  });

  // Events pregunta
  container.querySelectorAll('.q-text').forEach(inp => {
    inp.addEventListener('input', e => {
      preguntes[e.target.dataset.idx].text = e.target.value;
    });
  });
  container.querySelectorAll('.q-tipus').forEach(sel => {
    sel.addEventListener('change', e => {
      const idx = parseInt(e.target.dataset.idx);
      preguntes[idx].tipus = e.target.value;
      if (e.target.value === 'opcions' && !preguntes[idx].opcions?.length) {
        preguntes[idx].opcions = [];
      }
      renderPreguntes();
    });
  });
  container.querySelectorAll('.btn-del-q').forEach(btn => {
    btn.addEventListener('click', e => {
      preguntes.splice(parseInt(btn.dataset.idx), 1);
      renderPreguntes();
    });
  });

  // Events opcions
  container.querySelectorAll('.tag-input-field').forEach(inp => {
    inp.addEventListener('keydown', e => {
      if (e.key === 'Enter' && inp.value.trim()) {
        e.preventDefault();
        const idx = parseInt(inp.dataset.idx);
        preguntes[idx].opcions = preguntes[idx].opcions || [];
        preguntes[idx].opcions.push(inp.value.trim());
        inp.value = '';
        renderPreguntes();
      }
    });
  });
  container.querySelectorAll('.btn-del-opció').forEach(btn => {
    btn.addEventListener('click', () => {
      const idx  = parseInt(btn.dataset.idx);
      const oidx = parseInt(btn.dataset.oidx);
      preguntes[idx].opcions.splice(oidx, 1);
      renderPreguntes();
    });
  });

  document.getElementById('btn-add-pregunta').disabled = (preguntes.length >= MAX_PREGUNTES);
}

function renderOpcions(opcions, idx) {
  const tags = opcions.map((o, oidx) =>
    `<span class="badge bg-secondary me-1 mb-1 d-inline-flex align-items-center gap-1">
      ${escapeHtml(o)}
      <button type="button" class="btn-close btn-close-white btn-del-opció"
              style="font-size:.6em" data-idx="${idx}" data-oidx="${oidx}"></button>
    </span>`
  ).join('');
  return `<div class="mt-2">
    <small class="text-muted d-block mb-1">Opcions (prem Enter per afegir):</small>
    <div class="mb-1">${tags}</div>
    <input type="text" class="form-control form-control-sm tag-input-field" data-idx="${idx}"
           placeholder="Escriu una opció i prem Enter" style="max-width:300px;">
  </div>`;
}

function escapeHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.getElementById('btn-add-pregunta').addEventListener('click', () => {
  if (preguntes.length >= MAX_PREGUNTES) return;
  const id = 'p' + PARADA_ID + '_' + (preguntes.length + 1);
  preguntes.push({ id, text: '', tipus: 'opcions', opcions: [] });
  renderPreguntes();
});

renderPreguntes();

// ============= CODI =============
function generarCodi() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < 6; i++) code += chars[Math.floor(Math.random() * chars.length)];
  document.getElementById('p_codi').value = code;
}
function toggleCodi() {
  const inp  = document.getElementById('p_codi');
  const icon = document.getElementById('icon-codi');
  if (inp.type === 'password') {
    inp.type = 'text'; icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password'; icon.className = 'bi bi-eye';
  }
}

// ============= DESAR =============
async function desarParada() {
  const nom = document.getElementById('p_nom').value.trim();
  const lat  = parseFloat(document.getElementById('p_lat').value);
  const lng  = parseFloat(document.getElementById('p_lng').value);

  if (!nom) { alert('El nom de la parada és obligatori'); return; }
  if (!lat || !lng) { alert('Cal especificar les coordenades GPS'); return; }

  const rutes_sel = [...document.querySelectorAll('.ruta-check:checked')].map(c => c.value);

  // Normalitzar preguntes (assegurar que cada una té id)
  const preguntes_final = preguntes.map((q, i) => ({
    id:      q.id || 'p' + PARADA_ID + '_' + (i + 1),
    text:    q.text || '',
    tipus:   q.tipus || 'opcions',
    opcions: q.opcions || [],
  }));

  const parada_data = {
    id:               PARADA_ID,
    nom,
    rutes:            rutes_sel,
    lat,
    lng,
    codi:             document.getElementById('p_codi').value.trim().toUpperCase() || null,
    radi_metres:      parseInt(document.getElementById('p_radi').value) || null,
    es_inici:         document.getElementById('p_inici').checked,
    es_final:         document.getElementById('p_final').checked,
    missatge_arribada: document.getElementById('p_missatge').value.trim(),
    preguntes:        preguntes_final,
  };

  // Obtenir la llista actual de parades i actualitzar / afegir
  const respSettings = await fetch('../data/settings.json');
  let allSettings;
  try {
    allSettings = await respSettings.json();
  } catch(e) {
    allSettings = { parades: [] };
  }
  const parades_act = allSettings.parades || [];

  if (IS_NEW) {
    parades_act.push(parada_data);
  } else {
    const idx = parades_act.findIndex(p => p.id == PARADA_ID);
    if (idx >= 0) parades_act[idx] = parada_data;
    else parades_act.push(parada_data);
  }

  const r = await fetch('api/save_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ section: 'parades', data: parades_act })
  });
  const res = await r.json();
  if (res.ok) {
    showToast('Parada desada correctament!', 'success');
    setTimeout(() => window.location.href = 'parades.php', 1500);
  } else {
    showToast('Error: ' + (res.error || 'No s\'ha pogut desar'), 'danger');
  }
}

// ============= ELIMINAR =============
async function eliminarParada() {
  if (!confirm('Segur que vols eliminar aquesta parada?\n\nEls check-ins existents es conservaran als participants.')) return;

  const r = await fetch('api/delete_parada.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id: PARADA_ID })
  });
  const res = await r.json();
  if (res.ok) {
    showToast('Parada eliminada', 'success');
    setTimeout(() => window.location.href = 'parades.php', 1500);
  } else {
    showToast('Error eliminant la parada', 'danger');
  }
}
</script>
</body>
</html>
