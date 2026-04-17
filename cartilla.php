<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

require_login('index.php');

function ha_iniciat_ruta(array $user): bool {
    if (empty($user['checkins'])) return false;
    foreach ($user['checkins'] as $ci) {
        if (!empty($ci['inici'])) return true;
        if (!empty($ci['tipus']) && $ci['tipus'] === 'inici_ruta') return true;
    }
    return false;
}

$user = current_user();
if (!$user) {
    logout_user();
    header('Location: index.php');
    exit;
}

// Llegir settings dinàmics
$settings = get_settings();
$parades  = $settings['parades'] ?? [];
$gps_radi = $settings['checkin']['radi_metres'] ?? 200;
$require_gps = $settings['checkin']['require_gps'] ?? false;
$avis     = $settings['event']['avis_global'] ?? '';

// Filtrar parades per ruta de l'usuari
$ruta = $user['ruta'] ?? 'curta';
$parades_ruta = array_values(array_filter($parades, function($p) use ($ruta) {
    $pr = $p['rutes'] ?? [];
    if (empty($pr) && isset($p['ruta'])) {
        $pr = $p['ruta'] === 'ambdues' ? ['llarga', 'curta'] : [$p['ruta']];
    }
    return in_array($ruta, $pr);
}));

$checkin_ids = array_column($user['checkins'] ?? [], 'parada_id');

// Calcular progrés
$progress = get_user_progress($user, $parades);

// Detectar parada final
$id_final = null;
foreach ($parades as $p) {
    if (!empty($p['es_final']) || !empty($p['final'])) { $id_final = $p['id']; break; }
}
$acabat = $id_final !== null ? in_array($id_final, $checkin_ids) : in_array(10, $checkin_ids);

// Propera parada (primera no completada, excloent el punt d'inici)
$propera_parada = null;
foreach ($parades_ruta as $p) {
    if (!empty($p['es_inici']) || !empty($p['inici'])) continue;
    if (!in_array($p['id'], $checkin_ids)) {
        $propera_parada = $p;
        break;
    }
}

$gps_override = is_gps_override();
$ha_iniciat = ha_iniciat_ruta($user);
$cal_modal_inici = !empty($settings['checkin']['codi_inici']) && !$ha_iniciat;

$inici_ruta_iso = null;
if (!empty($settings['event']['data_esdeveniment']) && !empty($settings['event']['inici_ruta_hora'])) {
    $inici_ruta_iso = $settings['event']['data_esdeveniment'] . 'T' . $settings['event']['inici_ruta_hora'] . ':00';
}

// JSON per JavaScript
$parades_json  = json_encode($parades_ruta);
$checkins_json = json_encode($checkin_ids);
$propera_json  = json_encode($propera_parada);
$checkins_data = json_encode($user['checkins'] ?? []);

$nom_curt = explode(' ', $user['nom'])[0];
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>La meva Cartilla — Caminada Montserrat 2026</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="assets/css/spait.css">
  <style>
    body { overflow-x: hidden; }
    .app-container { display: flex; flex-direction: column; height: 100vh; }
    .map-section { flex: 0 0 auto; position: relative; }
    .cartilla-section { flex: 1 1 auto; overflow-y: auto; }
    .cartilla-section.no-scroll { overflow-y: visible; }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-spait navbar-expand-lg px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="cartilla.php">
    <?php
      $logo = $settings['visual']['logo_local'] ?: $settings['visual']['logo_url'];
    ?>
    <img src="<?= htmlspecialchars($logo) ?>" height="36" alt="splaiT">
    <span><?= htmlspecialchars($settings['event']['nom'] ?? 'Caminada 2026') ?></span>
  </a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <span class="text-white small d-none d-sm-inline">
      <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($nom_curt) ?>
    </span>
    <?php if ($progress['completades'] > 0): ?>
    <a href="download_pdf.php" class="btn btn-sm btn-spait-groc" title="Descarregar cartilla PDF">
      <i class="bi bi-file-pdf me-1"></i><span class="d-none d-sm-inline">PDF</span>
    </a>
    <?php endif; ?>
    <a href="ranking.php" class="btn btn-sm btn-outline-light" title="Ranking">
      <i class="bi bi-trophy"></i><span class="d-none d-sm-inline ms-1">Ranking</span>
    </a>
    <a href="logout.php" class="btn btn-sm btn-outline-light" title="Sortir">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</nav>

<?php if (!empty($avis)): ?>
<div class="alert alert-warning alert-dismissible fade show mb-0 rounded-0 text-center py-2" role="alert">
  <i class="bi bi-megaphone-fill me-2"></i><?= htmlspecialchars($avis) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tanca"></button>
</div>
<?php endif; ?>

<div class="app-container">

  <!-- MAPA -->
  <div class="map-section">
    <div id="map"></div>

    <!-- BARRA INFO + CHECK-IN -->
    <div class="checkin-bar d-flex flex-wrap align-items-center justify-content-between gap-2">

      <!-- Toggle compartir ubicació -->
      <div class="d-flex align-items-center gap-2 w-100 border-bottom pb-1 mb-1">
        <span class="text-white small">📍 Ubicació:</span>
        <div class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox"
                 id="toggle-share-location"
                 <?= user_shares_location($user) ? 'checked' : '' ?>>
          <label class="form-check-label small text-white" for="toggle-share-location"
                 id="share-location-label">
            <?= user_shares_location($user) ? 'Temps real activat' : 'Actualitzacions pausades' ?>
          </label>
        </div>
        <button type="button"
                class="btn btn-link btn-sm p-0 text-white-50"
                data-bs-toggle="popover"
                data-bs-placement="bottom"
                data-bs-trigger="focus"
                data-bs-content="Per seguretat, l'última posició coneguda sempre és visible pels organitzadors. Pots pausar les actualitzacions en temps real.">
          <i class="bi bi-info-circle"></i>
        </button>
      </div>

      <div class="distancia-info">
        <?php if ($acabat): ?>
          <span class="text-warning"><i class="bi bi-trophy-fill me-1"></i><?= htmlspecialchars($settings['event']['missatge_final'] ?? 'HO HAS ACONSEGUIT!') ?></span>
        <?php elseif ($propera_parada): ?>
          <span><i class="bi bi-geo-alt me-1"></i>
            Proper punt: <strong><?= htmlspecialchars($propera_parada['nom']) ?></strong>
          </span>
          <span class="ms-3" id="distancia-text">
            <i class="bi bi-rulers me-1"></i><span id="dist-val">—</span>
            &bull; <i class="bi bi-clock me-1"></i><span id="temps-val">—</span>
          </span>
        <?php else: ?>
          <span class="text-warning"><i class="bi bi-mountains me-1"></i><?= htmlspecialchars($settings['event']['missatge_benvinguda'] ?? 'Benvingut/da!') ?></span>
        <?php endif; ?>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="text-white small" id="gps-status">
          <i class="bi bi-reception-0" id="gps-icon"></i>
        </span>
        <?php if (!$acabat && $propera_parada): ?>
        <button class="btn btn-success btn-checkin" id="btn-checkin"
                <?= $require_gps ? 'disabled' : '' ?>
                data-parada-id="<?= $propera_parada['id'] ?>"
                data-parada-nom="<?= htmlspecialchars($propera_parada['nom']) ?>">
          <i class="bi bi-qr-code-scan me-1"></i>Check-in!
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CARTILLA -->
  <div class="cartilla-section no-scroll">
    <?php if ($acabat): ?>
    <div class="missatge-final m-3">
      <div style="font-size:3rem;">🏔️🎉</div>
      <h2><?= htmlspecialchars($settings['event']['missatge_final'] ?? 'HO HAS ACONSEGUIT!') ?></h2>
      <p class="mb-1 fs-5">Som d'esplai, res no ens atura!</p>
      <p class="mb-3">Benvingut/da a Montserrat, <strong><?= htmlspecialchars($nom_curt) ?></strong>!</p>
      <a href="download_pdf.php" class="btn btn-spait btn-lg">
        <i class="bi bi-file-pdf me-2"></i>Descarrega la teva Cartilla PDF
      </a>
    </div>
    <?php endif; ?>

    <!-- Capçalera cartilla -->
    <div class="cartilla-header">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h2 class="mb-0 fs-5">
            <i class="bi bi-journal-bookmark me-2"></i>La meva Cartilla
          </h2>
          <small class="opacity-75">
            <?= htmlspecialchars($user['nom']) ?> &bull;
            Ruta <?= $ruta === 'llarga' ? 'Llarga (Barcelona)' : 'Curta (Terrassa)' ?>
          </small>
        </div>
        <div class="text-end">
          <div class="fw-bold fs-5"><?= $progress['completades'] ?>/<?= $progress['total'] ?></div>
          <small class="opacity-75">parades</small>
        </div>
      </div>
      <div class="mt-2">
        <div class="progress progress-spait" style="height:12px; border-radius:8px;">
          <div class="progress-bar" role="progressbar"
               style="width: <?= $progress['percent'] ?>%"
               aria-valuenow="<?= $progress['percent'] ?>" aria-valuemin="0" aria-valuemax="100">
            <?php if ($progress['percent'] > 15): ?>
              <?= $progress['percent'] ?>%
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Motivació -->
    <?php if (!empty($user['motivacio'])): ?>
    <div class="mx-3 mt-3">
      <div class="motivacio-card card p-3">
        <p class="mb-0 text-center">
          <i class="bi bi-quote fs-4 text-warning"></i>
          <?= htmlspecialchars($user['motivacio']) ?>
          <i class="bi bi-quote fs-4 text-warning"></i>
        </p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Segells -->
    <div class="segells-grid">
      <?php foreach ($parades_ruta as $p):
        $completat  = in_array($p['id'], $checkin_ids);
        $es_propera = ($propera_parada && $p['id'] === $propera_parada['id']);
        $es_final   = !empty($p['es_final']) || !empty($p['final']);

        // Hora check-in
        $hora_checkin = '';
        foreach ($user['checkins'] as $ci) {
            if ($ci['parada_id'] === $p['id']) {
                $hora_checkin = date('H:i', strtotime($ci['timestamp']));
                break;
            }
        }

        $classe = $completat ? 'completat' : ($es_propera ? 'propera' : 'pendent');
        if ($es_final) $classe .= ' final';

        if ($completat) {
            $icon = $es_final ? '🏆' : '✅';
        } elseif ($es_propera) {
            $icon = '📍';
        } else {
            $icon = $es_final ? '🏔️' : '⬜';
        }
      ?>
      <div class="segell <?= $classe ?>">
        <div class="segell-icon"><?= $icon ?></div>
        <div><?= htmlspecialchars($p['nom']) ?></div>
        <?php if ($hora_checkin): ?>
          <div class="segell-hora"><i class="bi bi-clock"></i> <?= $hora_checkin ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($progress['completades'] > 0 && !$acabat): ?>
    <div class="px-3 pb-3">
      <div class="alert alert-success alert-spait">
        <i class="bi bi-lightning-charge-fill me-2"></i>
        Endavant, <?= htmlspecialchars($nom_curt) ?>! Ja queda menys! 💪
        &nbsp;
        <a href="download_pdf.php" class="btn btn-sm btn-outline-success ms-2">
          <i class="bi bi-file-pdf me-1"></i>Cartilla PDF
        </a>
      </div>
    </div>
    <?php endif; ?>

    <div class="footer-spait">
      <a href="<?= htmlspecialchars($settings['event']['web'] ?? 'https://esplaispait.com') ?>" target="_blank">
        <?= htmlspecialchars($settings['event']['web'] ?? 'esplaispait.com') ?>
      </a> &bull;
      Som d'esplai, res no ens atura!
    </div>
  </div>
</div>

<!-- MODAL INICI RUTA -->
<div class="modal fade" id="modalInici" data-bs-backdrop="static" 
     data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-spait">
        <h5 class="modal-title">
          <i class="bi bi-flag-fill me-2"></i>Activa la teva ruta!
        </h5>
      </div>
      <div class="modal-body text-center">
        
        <!-- Comptador (visible si encara no és l'hora) -->
        <div id="comptador-container" class="comptador-container" style="display:none;">
          <p class="text-muted mb-1">La caminada comença en:</p>
          <div class="comptador-display">
            <span id="comptador-val" class="fs-2 fw-bold text-spait">--:--:--</span>
          </div>
        </div>
        
        <!-- Formulari codi -->
        <p class="text-muted" id="missatge-codi">Introdueix el codi secret per començar la caminada.</p>
        <div class="mb-3">
          <input type="text" class="form-control form-control-lg text-center text-uppercase"
                 id="codi-inici" placeholder="CODI SECRET" 
                 style="letter-spacing:4px; font-size:1.3rem;" autocomplete="off">
        </div>
        <div id="inici-error" class="alert alert-danger d-none"></div>
        <button class="btn btn-spait btn-lg w-100" id="btn-iniciar-ruta">
          <i class="bi bi-play-fill me-2"></i>Comença la caminada!
        </button>
        
        <!-- Separador -->
        <div class="text-muted my-3">
          <small>o bé</small>
        </div>
        
        <!-- Botó logout -->
        <a href="logout.php" class="btn btn-outline-secondary btn-sm w-100">
          <i class="bi bi-box-arrow-left me-1"></i>Sortir / Tancar sessió
        </a>
        
      </div>
    </div>
  </div>
</div>

<!-- MODAL CHECK-IN -->
<div class="modal fade" id="modalCheckin" tabindex="-1" aria-labelledby="modalCheckinLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header modal-header-spait">
        <h5 class="modal-title" id="modalCheckinLabel">
          <i class="bi bi-qr-code-scan me-2"></i>Check-in a <span id="modal-parada-nom"></span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tanca"></button>
      </div>
      <div class="modal-body">

        <!-- FASE 1: Codi -->
        <div id="fase-codi">
          <p class="text-muted small">El responsable de la parada et donarà el codi secret.</p>
          <div class="mb-3">
            <label for="codi-secret" class="form-label fw-bold">Codi secret de la parada:</label>
            <input type="text" class="form-control form-control-lg text-center text-uppercase"
                   id="codi-secret" placeholder="XXXX" autocomplete="off"
                   style="letter-spacing:4px; font-size:1.3rem;">
          </div>
          <div id="codi-error" class="alert alert-danger d-none"></div>
          <button class="btn btn-spait w-100 btn-lg" id="btn-validar-codi">
            <i class="bi bi-check-circle me-2"></i>Validar codi
          </button>
        </div>

        <!-- FASE 2: Test -->
        <div id="fase-test" class="d-none">
          <div class="alert alert-success alert-spait mb-3">
            <i class="bi bi-check-circle-fill me-2"></i>Codi correcte! Respon les preguntes:
          </div>
          <div id="test-preguntes"></div>
          <button class="btn btn-spait-verd btn-lg w-100 mt-3" id="btn-confirmar-checkin">
            <i class="bi bi-star-fill me-2"></i>Confirmar check-in!
          </button>
        </div>

        <!-- FASE 3: Confirmació -->
        <div id="fase-ok" class="d-none text-center py-3">
          <div style="font-size:4rem;">✅</div>
          <h4 class="mt-2 text-success">Segell afegit!</h4>
          <p class="text-muted">La parada ha quedat registrada a la teva cartilla.</p>
          <button class="btn btn-spait" data-bs-dismiss="modal">Continua caminant!</button>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// Dades del servidor
const PARADES      = <?= $parades_json ?>;
const CHECKIN_IDS  = <?= $checkins_json ?>;
const PROPERA      = <?= $propera_json ?>;
const RUTA_USUARI  = <?= json_encode($ruta) ?>;
const ACABAT       = <?= json_encode($acabat) ?>;
const GPS_REQUIRE  = <?= json_encode($require_gps) ?>;
const GPS_RADI     = <?= (int)$gps_radi ?>;
const CAL_MODAL_INICI = <?= $cal_modal_inici ? 'true' : 'false' ?>;
const INICI_RUTA = <?= json_encode($inici_ruta_iso) ?>;

console.log('DEBUG - CAL_MODAL_INICI:', CAL_MODAL_INICI);
console.log('DEBUG - INICI_RUTA:', INICI_RUTA);

// ============= MAPA LEAFLET =============
const map = L.map('map', { zoomControl: true });
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 18,
}).addTo(map);

// Icones personalitzades
function createIcon(color, emoji) {
  return L.divIcon({
    html: `<div style="
      background:${color};
      border:3px solid white;
      border-radius:50% 50% 50% 0;
      width:36px; height:36px;
      display:flex; align-items:center; justify-content:center;
      font-size:16px;
      box-shadow:0 2px 8px rgba(0,0,0,0.4);
      transform:rotate(-45deg);
    "><span style="transform:rotate(45deg)">${emoji}</span></div>`,
    className: '',
    iconSize: [36, 36],
    iconAnchor: [18, 36],
    popupAnchor: [0, -36]
  });
}

const iconComplet  = createIcon('#27AE60', '✅');
const iconPropera  = createIcon('#3498DB', '📍');
const iconPendent  = createIcon('#95a5a6', '⬜');
const iconFinal    = createIcon('#C0392B', '🏆');

const bounds = [];

PARADES.forEach(p => {
  const completat = CHECKIN_IDS.includes(p.id);
  const esPropera = PROPERA && p.id === PROPERA.id;
  const esFinal   = !!p.es_final || !!p.final;

  let icon = iconPendent;
  if (completat)      icon = esFinal ? iconFinal : iconComplet;
  else if (esPropera) icon = iconPropera;
  else if (esFinal)   icon = iconFinal;

  const estat = completat ? '✅ Completada' : (esPropera ? '📍 Propera parada' : '⬜ Pendent');
  const marker = L.marker([p.lat, p.lng], { icon }).addTo(map);
  marker.bindPopup(`<strong>${p.nom}</strong><br><span style="color:#666">${estat}</span>`);

  bounds.push([p.lat, p.lng]);
});

// Línia de ruta
const latlngs = PARADES.map(p => [p.lat, p.lng]);
L.polyline(latlngs, { color: '#C0392B', weight: 3, opacity: 0.6, dashArray: '8,6' }).addTo(map);

if (bounds.length > 0) {
  map.fitBounds(bounds, { padding: [30, 30] });
}

// ============= GPS =============
let userLat = null, userLng = null;
let userMarker = null;

function haversine(lat1, lng1, lat2, lng2) {
  const R = 6371000;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLng = (lng2 - lng1) * Math.PI / 180;
  const a = Math.sin(dLat/2)**2 +
            Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2;
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
}

function formatDist(m) {
  return m >= 1000 ? (m/1000).toFixed(1) + ' km' : Math.round(m) + ' m';
}
function formatTemps(m) {
  const mins = Math.round(m / (4500/60));
  if (mins < 60) return mins + ' min';
  const h = Math.floor(mins/60), mm = mins % 60;
  return h + 'h ' + (mm > 0 ? mm + 'min' : '');
}

function updateGPS(pos) {
  userLat = pos.coords.latitude;
  userLng = pos.coords.longitude;

  const dotIcon = L.divIcon({
    html: '<div class="user-location-dot"></div>',
    className: '',
    iconSize: [16, 16],
    iconAnchor: [8, 8],
  });

  if (userMarker) {
    userMarker.setLatLng([userLat, userLng]);
  } else {
    userMarker = L.marker([userLat, userLng], { icon: dotIcon }).addTo(map);
    userMarker.bindPopup('<strong>Tu ets aquí</strong>');
  }

  document.getElementById('gps-icon').className = 'bi bi-reception-4 text-success';

  if (PROPERA && !ACABAT) {
    const dist = haversine(userLat, userLng, PROPERA.lat, PROPERA.lng);
    document.getElementById('dist-val').textContent  = formatDist(dist);
    document.getElementById('temps-val').textContent = formatTemps(dist);

    const btnCheckin = document.getElementById('btn-checkin');
    if (btnCheckin && GPS_REQUIRE) {
      if (dist <= GPS_RADI) {
        btnCheckin.disabled = false;
        btnCheckin.title = '';
      } else {
        btnCheckin.disabled = true;
        btnCheckin.title = 'Cal estar a menys de ' + GPS_RADI + ' m de la parada';
      }
    }
  }
}

function gpsError(err) {
  document.getElementById('gps-icon').className = 'bi bi-geo-alt-fill text-warning';
  document.getElementById('gps-status').title = 'GPS no disponible: ' + err.message;
  const btnCheckin = document.getElementById('btn-checkin');
  if (btnCheckin) {
    btnCheckin.disabled = false;
    btnCheckin.title = 'GPS no disponible — el responsable verificarà el codi';
  }
}

// ============= COMPARTIR POSICIÓ =============
let sharingLocation = <?= user_shares_location($user) ? 'true' : 'false' ?>;
let pendingPosition = null;

function sendPosition(lat, lng, accuracy) {
  if (!sharingLocation) return;
  fetch('update_position.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ lat, lng, accuracy })
  }).catch(function() {
    pendingPosition = { lat, lng, accuracy };
  });
}

function flushPending() {
  if (pendingPosition && sharingLocation) {
    const p = pendingPosition;
    pendingPosition = null;
    sendPosition(p.lat, p.lng, p.accuracy);
  }
}
setInterval(flushPending, 30000);

document.getElementById('toggle-share-location').addEventListener('change', function() {
  const checkbox = this;
  sharingLocation = checkbox.checked;
  const label = document.getElementById('share-location-label');
  label.textContent = sharingLocation ? 'Temps real activat' : 'Actualitzacions pausades';

  fetch('toggle_location.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ share: sharingLocation })
  })
  .then(r => r.json())
  .then(data => {
    if (!data.ok) {
      // Revertir si el servidor no ha pogut guardar
      checkbox.checked = !sharingLocation;
      sharingLocation = checkbox.checked;
      label.textContent = sharingLocation ? 'Temps real activat' : 'Actualitzacions pausades';
      console.error('Error guardant ubicació:', data.error);
    }
  })
  .catch(err => {
    // Error de xarxa o resposta no vàlida — revertir
    checkbox.checked = !sharingLocation;
    sharingLocation = checkbox.checked;
    label.textContent = sharingLocation ? 'Temps real activat' : 'Actualitzacions pausades';
    console.error('Error de connexió en toggle ubicació:', err);
  });
});

// Inicialitzar popovers de Bootstrap
document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
  new bootstrap.Popover(el);
});

if (navigator.geolocation) {
  navigator.geolocation.watchPosition(function(pos) {
    updateGPS(pos);
    sendPosition(pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy);
  }, gpsError, {
    enableHighAccuracy: true,
    maximumAge: 15000,
    timeout: 10000,
  });
} else {
  gpsError({ message: 'GPS no suportat en aquest dispositiu' });
}

// ============= MODAL CHECK-IN =============
const modalEl    = document.getElementById('modalCheckin');
const modalBS    = new bootstrap.Modal(modalEl);
const btnCheckin = document.getElementById('btn-checkin');

if (btnCheckin) {
  btnCheckin.addEventListener('click', () => {
    document.getElementById('modal-parada-nom').textContent = btnCheckin.dataset.paradaNom;
    document.getElementById('codi-secret').value = '';
    document.getElementById('codi-error').classList.add('d-none');
    document.getElementById('fase-codi').classList.remove('d-none');
    document.getElementById('fase-test').classList.add('d-none');
    document.getElementById('fase-ok').classList.add('d-none');
    modalBS.show();
  });
}

// Validar codi
document.getElementById('btn-validar-codi').addEventListener('click', () => {
  const paradaId  = parseInt(btnCheckin?.dataset.paradaId ?? -1);
  const codiInput = document.getElementById('codi-secret').value.trim().toUpperCase();
  const errDiv    = document.getElementById('codi-error');

  fetch('checkin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `accio=validar_codi&parada_id=${paradaId}&codi=${encodeURIComponent(codiInput)}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      errDiv.classList.add('d-none');
      mostrarTest(paradaId);
    } else {
      errDiv.textContent = data.error || 'Codi incorrecte. Torna-ho a provar!';
      errDiv.classList.remove('d-none');
    }
  })
  .catch(() => {
    errDiv.textContent = 'Error de connexió. Torna-ho a provar.';
    errDiv.classList.remove('d-none');
  });
});

// Mostrar preguntes del test (format nou: preguntes[] dins de cada parada)
function mostrarTest(paradaId) {
  const parada    = PARADES.find(p => p.id === paradaId);
  const preguntes = parada?.preguntes ?? [];
  const container = document.getElementById('test-preguntes');
  container.innerHTML = '';

  document.getElementById('fase-codi').classList.add('d-none');
  document.getElementById('fase-test').classList.remove('d-none');

  if (!preguntes.length) {
    container.innerHTML = '<p class="text-muted">Ja pots confirmar el check-in!</p>';
    return;
  }

  preguntes.forEach((q, idx) => {
    const div = document.createElement('div');
    div.classList.add('mb-3');

    if (q.tipus === 'opcions' && q.opcions && q.opcions.length) {
      div.innerHTML = `<label class="form-label fw-semibold">${idx+1}. ${q.text}</label>
        <div class="d-flex flex-wrap gap-2">
          ${q.opcions.map(o => `
            <input type="radio" class="btn-check" name="test_${q.id}" id="opt_${q.id}_${o.replace(/\s/g,'_')}" value="${o}" autocomplete="off">
            <label class="btn btn-outline-secondary btn-sm" for="opt_${q.id}_${o.replace(/\s/g,'_')}">${o}</label>
          `).join('')}
        </div>`;
    } else {
      div.innerHTML = `<label class="form-label fw-semibold">${idx+1}. ${q.text}</label>
        <textarea class="form-control" name="test_${q.id}" rows="2" placeholder="Escriu aquí..."></textarea>`;
    }
    container.appendChild(div);
  });
}

// Confirmar check-in
document.getElementById('btn-confirmar-checkin').addEventListener('click', () => {
  const paradaId = parseInt(btnCheckin?.dataset.paradaId ?? -1);
  const testData = {};

  document.querySelectorAll('#test-preguntes input[type=radio]:checked').forEach(el => {
    const name = el.name.replace('test_', '');
    testData[name] = el.value;
  });
  document.querySelectorAll('#test-preguntes textarea').forEach(el => {
    const name = el.name.replace('test_', '');
    testData[name] = el.value;
  });

  const params = new URLSearchParams({
    accio: 'checkin',
    parada_id: paradaId,
    test: JSON.stringify(testData),
  });

  fetch('checkin.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: params.toString()
  })
  .then(r => r.json())
  .then(data => {
    if (data.ok) {
      document.getElementById('fase-test').classList.add('d-none');
      document.getElementById('fase-ok').classList.remove('d-none');
      modalEl.addEventListener('hidden.bs.modal', () => location.reload(), { once: true });
    } else {
      alert('Error: ' + (data.error || 'No s\'ha pogut registrar el check-in.'));
    }
  })
  .catch(() => alert('Error de connexió. Torna-ho a provar.'));
});

// ============= MODAL INICI RUTA =============
if (CAL_MODAL_INICI) {
    const modalInici = new bootstrap.Modal(document.getElementById('modalInici'));
    modalInici.show();
    
    if (INICI_RUTA) {
        actualitzarComptador();
        setInterval(actualitzarComptador, 1000);
    }
}

function actualitzarComptador() {
    if (!INICI_RUTA) return;
    
    const ara = new Date();
    const inici = new Date(INICI_RUTA);
    const diff = inici - ara;
    
    const comptadorContainer = document.getElementById('comptador-container');
    const codiInput = document.getElementById('codi-inici');
    const btnIniciar = document.getElementById('btn-iniciar-ruta');
    const missatgeCodi = document.getElementById('missatge-codi');
    
    if (diff <= 0) {
        if (comptadorContainer) comptadorContainer.style.display = 'none';
        if (codiInput) codiInput.disabled = false;
        if (btnIniciar) btnIniciar.disabled = false;
        if (missatgeCodi) missatgeCodi.textContent = 'Introdueix el codi secret per començar la caminada.';
        return;
    }
    
    if (comptadorContainer) comptadorContainer.style.display = 'block';
    if (codiInput) codiInput.disabled = true;
    if (btnIniciar) btnIniciar.disabled = true;
    if (missatgeCodi) missatgeCodi.textContent = 'Encara no és l\'hora! Esperem una mica...';
    
    const hores = Math.floor(diff / (1000 * 60 * 60));
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const segs = Math.floor((diff % (1000 * 60)) / 1000);
    
    const comptadorVal = document.getElementById('comptador-val');
    if (comptadorVal) {
        comptadorVal.textContent =
            hores.toString().padStart(2, '0') + ':' +
            mins.toString().padStart(2, '0') + ':' +
            segs.toString().padStart(2, '0');
    }
}

document.getElementById('btn-iniciar-ruta')?.addEventListener('click', function() {
    const codi = document.getElementById('codi-inici').value.trim().toUpperCase();
    const errorDiv = document.getElementById('inici-error');
    
    console.log('DEBUG - Codi introduit:', codi);
    
    if (!codi) {
        errorDiv.textContent = 'Introdueix el codi secret.';
        errorDiv.classList.remove('d-none');
        return;
    }
    
    this.disabled = true;
    console.log('DEBUG - Enviant request...');
    
    fetch('iniciar_ruta.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codi: codi })
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) {
            location.reload();
        } else {
            errorDiv.textContent = data.error || 'Codi incorrecte. Torna\'ho a provar!';
            errorDiv.classList.remove('d-none');
            document.getElementById('codi-inici').value = '';
            document.getElementById('codi-inici').focus();
            document.getElementById('btn-iniciar-ruta').disabled = false;
        }
    })
    .catch(function() {
        errorDiv.textContent = 'Error de connexió. Torna\'ho a provar.';
        errorDiv.classList.remove('d-none');
        document.getElementById('btn-iniciar-ruta').disabled = false;
    });
});

document.getElementById('codi-inici')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('btn-iniciar-ruta').click();
    }
});
</script>
</body>
</html>
