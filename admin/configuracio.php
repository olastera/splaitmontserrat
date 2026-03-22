<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings = get_settings();
$ev       = $settings['event']   ?? [];
$vis      = $settings['visual']  ?? [];
$chk      = $settings['checkin'] ?? [];
$logo     = $vis['logo_local'] ?: ($vis['logo_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuració — Admin</title>
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
      <a href="usuaris.php" class="btn btn-sm btn-outline-light"><i class="bi bi-people me-1"></i>Usuaris</a>
      <a href="parades.php" class="btn btn-sm btn-outline-light"><i class="bi bi-pin-map me-1"></i>Parades</a>
      <a href="configuracio.php" class="btn btn-sm btn-light"><i class="bi bi-gear me-1"></i>Configuració</a>
      <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right me-1"></i>Sortir</a>
    </div>
  </div>
</nav>

<div class="container py-4" style="max-width:860px">
  <h2 class="mb-4"><i class="bi bi-gear me-2"></i>Configuració</h2>

  <!-- Toast notificació -->
  <div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
    <div id="toast-ok" class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive">
      <div class="d-flex">
        <div class="toast-body"><i class="bi bi-check-circle me-2"></i><span id="toast-msg">Canvis desats!</span></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="tab-event-btn" data-bs-toggle="tab" data-bs-target="#tab-event" type="button">
        <i class="bi bi-calendar-event me-1"></i>Esdeveniment
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-visual-btn" data-bs-toggle="tab" data-bs-target="#tab-visual" type="button">
        <i class="bi bi-palette me-1"></i>Aparença
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="tab-control-btn" data-bs-toggle="tab" data-bs-target="#tab-control" type="button">
        <i class="bi bi-sliders me-1"></i>Avisos i control
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- TAB 1: Informació de l'esdeveniment -->
    <div class="tab-pane fade show active" id="tab-event" role="tabpanel">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nom de l'esdeveniment</label>
            <input type="text" class="form-control" id="ev_nom" value="<?= htmlspecialchars($ev['nom'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Organització</label>
            <input type="text" class="form-control" id="ev_organitzacio" value="<?= htmlspecialchars($ev['organitzacio'] ?? '') ?>">
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Data de l'esdeveniment</label>
              <input type="date" class="form-control" id="ev_data" value="<?= htmlspecialchars($ev['data_esdeveniment'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Inici inscripcions</label>
              <input type="date" class="form-control" id="ev_inici" value="<?= htmlspecialchars($ev['dates_actives']['inici'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label fw-semibold">Fi inscripcions</label>
              <input type="date" class="form-control" id="ev_fi" value="<?= htmlspecialchars($ev['dates_actives']['fi'] ?? '') ?>">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Web</label>
              <input type="url" class="form-control" id="ev_web" value="<?= htmlspecialchars($ev['web'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label fw-semibold">Telèfon de contacte</label>
              <input type="text" class="form-control" id="ev_contacte" value="<?= htmlspecialchars($ev['contacte'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Missatge de benvinguda</label>
            <textarea class="form-control" id="ev_benvinguda" rows="2"><?= htmlspecialchars($ev['missatge_benvinguda'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Missatge final (quan arriben a la meta)</label>
            <textarea class="form-control" id="ev_final" rows="2"><?= htmlspecialchars($ev['missatge_final'] ?? '') ?></textarea>
          </div>
          <button class="btn btn-spait btn-lg" onclick="desarEvent()">
            <i class="bi bi-floppy me-2"></i>Desar canvis
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 2: Aparença -->
    <div class="tab-pane fade" id="tab-visual" role="tabpanel">
      <div class="card shadow-sm">
        <div class="card-body">

          <!-- Logo -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Logo actual</label>
            <div class="mb-2">
              <img id="logo-preview" src="<?= htmlspecialchars($logo) ?>"
                   style="max-height:80px; max-width:300px; background:#eee; padding:8px; border-radius:8px;"
                   alt="Logo actual" onerror="this.style.display='none'">
            </div>
            <div class="row g-2 mb-2">
              <div class="col-md-8">
                <label class="form-label">URL del logo</label>
                <input type="url" class="form-control" id="vis_logo_url"
                       value="<?= htmlspecialchars($vis['logo_url'] ?? '') ?>"
                       placeholder="https://...">
              </div>
              <div class="col-md-4">
                <label class="form-label">O puja un fitxer</label>
                <input type="file" class="form-control" id="logo-file" accept="image/*">
              </div>
            </div>
            <button class="btn btn-outline-secondary btn-sm" onclick="pujarLogo()">
              <i class="bi bi-upload me-1"></i>Pujar fitxer
            </button>
            <small class="text-muted ms-2">Màx 2MB · PNG, JPG, SVG</small>
          </div>

          <hr>

          <!-- Colors -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Nom de l'app</label>
            <input type="text" class="form-control" id="vis_nom_app" value="<?= htmlspecialchars($vis['nom_app'] ?? '') ?>">
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">Color primari</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" class="form-control form-control-color" id="vis_color_primari"
                       value="<?= htmlspecialchars($vis['color_primari'] ?? '#C0392B') ?>" title="Color primari">
                <input type="text" class="form-control form-control-sm" id="vis_color_primari_hex"
                       value="<?= htmlspecialchars($vis['color_primari'] ?? '#C0392B') ?>" maxlength="7">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Color secundari</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" class="form-control form-control-color" id="vis_color_secundari"
                       value="<?= htmlspecialchars($vis['color_secundari'] ?? '#27AE60') ?>" title="Color secundari">
                <input type="text" class="form-control form-control-sm" id="vis_color_secundari_hex"
                       value="<?= htmlspecialchars($vis['color_secundari'] ?? '#27AE60') ?>" maxlength="7">
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Color accent</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" class="form-control form-control-color" id="vis_color_accent"
                       value="<?= htmlspecialchars($vis['color_accent'] ?? '#F1C40F') ?>" title="Color accent">
                <input type="text" class="form-control form-control-sm" id="vis_color_accent_hex"
                       value="<?= htmlspecialchars($vis['color_accent'] ?? '#F1C40F') ?>" maxlength="7">
              </div>
            </div>
          </div>

          <!-- Preview en temps real -->
          <div class="mb-4">
            <label class="form-label fw-semibold">Preview en temps real</label>
            <div id="preview-zona" class="rounded p-3" style="background:#f8f9fa; border:1px solid #dee2e6;">
              <div id="preview-navbar" class="d-flex align-items-center gap-2 px-3 py-2 rounded mb-2 text-white"
                   style="background:<?= htmlspecialchars($vis['color_primari'] ?? '#C0392B') ?>">
                <strong id="preview-nom-app"><?= htmlspecialchars($vis['nom_app'] ?? 'Cartilla del Pelegrí') ?></strong>
              </div>
              <button id="preview-btn" class="btn text-white"
                      style="background:<?= htmlspecialchars($vis['color_primari'] ?? '#C0392B') ?>">
                Botó principal
              </button>
              <button id="preview-btn-sec" class="btn ms-2 text-white"
                      style="background:<?= htmlspecialchars($vis['color_secundari'] ?? '#27AE60') ?>">
                Botó secundari
              </button>
              <span id="preview-badge" class="badge ms-2"
                    style="background:<?= htmlspecialchars($vis['color_accent'] ?? '#F1C40F') ?>; color:#2C3E50">
                Badge accent
              </span>
            </div>
          </div>

          <button class="btn btn-spait btn-lg" onclick="desarVisual()">
            <i class="bi bi-floppy me-2"></i>Desar canvis
          </button>
        </div>
      </div>
    </div>

    <!-- TAB 3: Avisos i control -->
    <div class="tab-pane fade" id="tab-control" role="tabpanel">
      <div class="card shadow-sm">
        <div class="card-body">

          <div class="mb-4">
            <label class="form-label fw-semibold">Avís global (banner a tots els participants)</label>
            <textarea class="form-control" id="ev_avis" rows="2"
                      placeholder="Deixa en blanc per no mostrar cap avís"><?= htmlspecialchars($ev['avis_global'] ?? '') ?></textarea>
            <small class="text-muted">Si hi ha text, apareix com a banner groc a la cartilla de tots els participants.</small>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold d-block">Mode prova</label>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="ev_mode_prova" role="switch"
                     <?= !empty($ev['mode_prova']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="ev_mode_prova">
                Activar mode prova (els check-ins no es registren)
              </label>
            </div>
          </div>

          <!-- Toggle registre obert/tancat -->
          <div class="card mb-4 border-warning">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-1">📝 Registre de participants</h6>
                  <small class="text-muted">
                    Si està desactivat, els participants no es poden registrar sols.<br>
                    Només l'admin pot donar-los d'alta mitjançant Excel o manualment.
                  </small>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input" type="checkbox"
                         id="toggle-registre"
                         <?= ($ev['registre_obert'] ?? true) ? 'checked' : '' ?>
                         style="width: 3em; height: 1.5em;">
                  <label class="form-check-label fw-bold <?= ($ev['registre_obert'] ?? true) ? 'text-success' : 'text-danger' ?>"
                         id="toggle-registre-label" for="toggle-registre">
                    <?= ($ev['registre_obert'] ?? true) ? 'OBERT' : 'TANCAT' ?>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <hr>

          <h6 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>GPS i check-in</h6>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Radi GPS del check-in (metres)</label>
              <input type="number" class="form-control" id="chk_radi" min="50" max="5000"
                     value="<?= (int)($chk['radi_metres'] ?? 200) ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold d-block">Requerir GPS</label>
              <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="chk_require_gps" role="switch"
                       <?= !empty($chk['require_gps']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="chk_require_gps">
                  Cal estar a prop de la parada per fer check-in
                </label>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Codi mestre</label>
            <div class="input-group">
              <input type="text" class="form-control text-uppercase" id="chk_codi_mestre"
                     value="<?= htmlspecialchars($chk['codi_mestre'] ?? '') ?>"
                     placeholder="Deixa en blanc per desactivar"
                     style="letter-spacing:2px;">
              <button class="btn btn-outline-secondary" type="button" onclick="generarCodiMestre()">
                <i class="bi bi-shuffle me-1"></i>Generar
              </button>
              <button class="btn btn-outline-secondary" type="button" onclick="toggleCodiMestre()">
                <i class="bi bi-eye" id="icon-codi-mestre"></i>
              </button>
            </div>
            <small class="text-muted">El codi mestre val per qualsevol parada. Útil per a admins o urgències.</small>
          </div>

          <button class="btn btn-spait btn-lg" onclick="desarControl()">
            <i class="bi bi-floppy me-2"></i>Desar canvis
          </button>
        </div>
      </div>
    </div>

  </div><!-- /tab-content -->
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

async function saveSection(section, data) {
  const r = await fetch('api/save_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ section, data })
  });
  const res = await r.json();
  if (res.ok) {
    showToast('Canvis desats correctament!', 'success');
  } else {
    showToast('Error: ' + (res.error || 'No s\'ha pogut desar'), 'danger');
  }
}

function desarEvent() {
  saveSection('event', {
    nom:                 document.getElementById('ev_nom').value,
    organitzacio:        document.getElementById('ev_organitzacio').value,
    data_esdeveniment:   document.getElementById('ev_data').value,
    web:                 document.getElementById('ev_web').value,
    contacte:            document.getElementById('ev_contacte').value,
    missatge_benvinguda: document.getElementById('ev_benvinguda').value,
    missatge_final:      document.getElementById('ev_final').value,
    avis_global:         document.getElementById('ev_avis')?.value ?? '',
    mode_prova:          document.getElementById('ev_mode_prova')?.checked ?? false,
    registre_obert:      document.getElementById('toggle-registre')?.checked ?? true,
    dates_actives: {
      inici: document.getElementById('ev_inici').value,
      fi:    document.getElementById('ev_fi').value,
    }
  });
}

function desarVisual() {
  saveSection('visual', {
    logo_url:        document.getElementById('vis_logo_url').value,
    logo_local:      '<?= addslashes($vis['logo_local'] ?? '') ?>',
    color_primari:   document.getElementById('vis_color_primari').value,
    color_secundari: document.getElementById('vis_color_secundari').value,
    color_accent:    document.getElementById('vis_color_accent').value,
    nom_app:         document.getElementById('vis_nom_app').value,
  });
}

function desarControl() {
  // Desar event (avis + mode prova + registre_obert) + checkin
  saveSection('event', {
    nom:                 document.getElementById('ev_nom')?.value ?? '',
    organitzacio:        document.getElementById('ev_organitzacio')?.value ?? '',
    data_esdeveniment:   document.getElementById('ev_data')?.value ?? '',
    web:                 document.getElementById('ev_web')?.value ?? '',
    contacte:            document.getElementById('ev_contacte')?.value ?? '',
    missatge_benvinguda: document.getElementById('ev_benvinguda')?.value ?? '',
    missatge_final:      document.getElementById('ev_final')?.value ?? '',
    avis_global:         document.getElementById('ev_avis').value,
    mode_prova:          document.getElementById('ev_mode_prova').checked,
    registre_obert:      document.getElementById('toggle-registre').checked,
  });
  saveSection('checkin', {
    require_gps:  document.getElementById('chk_require_gps').checked,
    radi_metres:  parseInt(document.getElementById('chk_radi').value) || 200,
    codi_mestre:  document.getElementById('chk_codi_mestre').value.trim().toUpperCase(),
  });
}

// Preview en temps real — colors
function syncColor(inputId, hexId, previewTargets) {
  const inp = document.getElementById(inputId);
  const hex = document.getElementById(hexId);
  inp.addEventListener('input', () => {
    hex.value = inp.value;
    previewTargets.forEach(t => {
      const el = document.getElementById(t.id);
      if (el) el.style[t.prop] = inp.value;
    });
  });
  hex.addEventListener('input', () => {
    if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) {
      inp.value = hex.value;
      previewTargets.forEach(t => {
        const el = document.getElementById(t.id);
        if (el) el.style[t.prop] = hex.value;
      });
    }
  });
}

syncColor('vis_color_primari', 'vis_color_primari_hex', [
  { id: 'preview-navbar', prop: 'background' },
  { id: 'preview-btn',    prop: 'background' },
]);
syncColor('vis_color_secundari', 'vis_color_secundari_hex', [
  { id: 'preview-btn-sec', prop: 'background' },
]);
syncColor('vis_color_accent', 'vis_color_accent_hex', [
  { id: 'preview-badge', prop: 'background' },
]);

document.getElementById('vis_nom_app').addEventListener('input', function() {
  document.getElementById('preview-nom-app').textContent = this.value;
});

document.getElementById('vis_logo_url').addEventListener('change', function() {
  document.getElementById('logo-preview').src = this.value;
});

// Codi mestre
function generarCodiMestre() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
  const inp = document.getElementById('chk_codi_mestre');
  inp.value = code;
  inp.type  = 'text';
}

function toggleCodiMestre() {
  const inp  = document.getElementById('chk_codi_mestre');
  const icon = document.getElementById('icon-codi-mestre');
  if (inp.type === 'password') {
    inp.type = 'text';
    icon.className = 'bi bi-eye-slash';
  } else {
    inp.type = 'password';
    icon.className = 'bi bi-eye';
  }
}

// Toggle registre obert/tancat
document.getElementById('toggle-registre').addEventListener('change', function() {
  const obert = this.checked;
  const label = document.getElementById('toggle-registre-label');
  label.textContent = obert ? 'OBERT' : 'TANCAT';
  label.className = 'form-check-label fw-bold ' + (obert ? 'text-success' : 'text-danger');

  fetch('api/save_settings.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      section: 'event',
      data: {
        nom:                 document.getElementById('ev_nom')?.value ?? '',
        organitzacio:        document.getElementById('ev_organitzacio')?.value ?? '',
        data_esdeveniment:   document.getElementById('ev_data')?.value ?? '',
        web:                 document.getElementById('ev_web')?.value ?? '',
        contacte:            document.getElementById('ev_contacte')?.value ?? '',
        missatge_benvinguda: document.getElementById('ev_benvinguda')?.value ?? '',
        missatge_final:      document.getElementById('ev_final')?.value ?? '',
        avis_global:         document.getElementById('ev_avis')?.value ?? '',
        mode_prova:          document.getElementById('ev_mode_prova')?.checked ?? false,
        registre_obert:      obert,
      }
    })
  }).then(() => {
    showToast(obert
      ? '✅ Registre obert — els participants es poden registrar'
      : '🔒 Registre tancat — només l\'admin pot donar d\'alta',
      obert ? 'success' : 'warning'
    );
  });
});

// Pujar logo
async function pujarLogo() {
  const file = document.getElementById('logo-file').files[0];
  if (!file) { alert('Selecciona un fitxer primer'); return; }
  const fd = new FormData();
  fd.append('logo', file);
  const r = await fetch('api/upload_logo.php', { method: 'POST', body: fd });
  const res = await r.json();
  if (res.ok) {
    document.getElementById('logo-preview').src = res.url;
    showToast('Logo pujat correctament!', 'success');
  } else {
    showToast('Error: ' + (res.error || 'No s\'ha pogut pujar'), 'danger');
  }
}
</script>
</body>
</html>
