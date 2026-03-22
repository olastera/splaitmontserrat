<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$positions_json = json_encode(get_active_positions(), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mapa en temps real — Admin Caminada 2026</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
  <style>
    #map { height: calc(100vh - 56px); }
    .map-legend {
      position: absolute;
      bottom: 30px;
      right: 10px;
      z-index: 1000;
      background: rgba(255,255,255,0.95);
      min-width: 220px;
    }
    .dot {
      display: inline-block;
      width: 12px; height: 12px;
      border-radius: 50%;
      margin-right: 6px;
      vertical-align: middle;
    }
    .bg-gold { background: #FFD700; }
    .popup-content { min-width: 180px; }
  </style>
</head>
<body>

<nav class="navbar navbar-spait px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
    <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
         height="32" alt="splaiT">
    <span>Mapa en temps real</span>
  </a>
  <div class="ms-auto d-flex gap-2">
    <button class="btn btn-sm btn-outline-light" onclick="location.reload()">
      <i class="bi bi-arrow-clockwise me-1"></i>Actualitzar
    </button>
    <a href="dashboard.php" class="btn btn-sm btn-outline-light">
      <i class="bi bi-arrow-left me-1"></i>Dashboard
    </a>
  </div>
</nav>

<div style="position:relative;">
  <div id="map"></div>

  <div class="map-legend card p-2 small">
    <strong class="d-block mb-1">Llegenda</strong>
    <div><span class="dot bg-success"></span> Temps real (actiu &lt;10min)</div>
    <div><span class="dot bg-primary"></span> Pausat voluntàriament</div>
    <div><span class="dot bg-warning"></span> Sense connexió 10-30min &#x26A0;</div>
    <div><span class="dot bg-danger"></span> Sense connexió +30min &#x1F6A8;</div>
    <div><span class="dot bg-gold"></span> Ha arribat a Montserrat &#x1F3C6;</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const POSITIONS = <?= $positions_json ?>;

const map = L.map('map').setView([41.593, 1.835], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 18,
}).addTo(map);

const STATUS_COLOR = {
  actiu:       '#28a745',
  tracking_off:'#0d6efd',
  desconnectat:'#ffc107',
  perdut:      '#dc3545',
  finished:    '#FFD700',
};

function markerIcon(status) {
  const color = STATUS_COLOR[status] || '#6c757d';
  return L.divIcon({
    html: `<div style="
      background:${color};
      border:3px solid white;
      border-radius:50%;
      width:18px; height:18px;
      box-shadow:0 2px 6px rgba(0,0,0,0.4);
    "></div>`,
    className: '',
    iconSize: [18, 18],
    iconAnchor: [9, 9],
    popupAnchor: [0, -12],
  });
}

function buildPopupContent(p) {
  const statusMap = {
    actiu:        { text: '🟢 Temps real',                     cls: 'text-success' },
    tracking_off: { text: '🔵 Ha pausat les actualitzacions',  cls: 'text-primary' },
    desconnectat: { text: `🟡 Sense connexió fa ${p.minutes_ago} min`, cls: 'text-warning' },
    perdut:       { text: `🔴 Sense connexió fa ${p.minutes_ago} min — Verificar!`, cls: 'text-danger fw-bold' },
    finished:     { text: '🏆 Ha arribat a Montserrat!',       cls: 'text-warning fw-bold' },
  };
  const s = statusMap[p.status] || { text: p.status, cls: '' };

  return `<div class="popup-content">
    <strong>${p.nom}</strong><br>
    <span class="${s.cls} small">${s.text}</span><br>
    <small class="text-muted">📍 ${p.ultima_parada}</small><br>
    <small class="text-muted">✅ ${p.parades_fetes}/10 parades</small><br>
    <small class="text-muted">🕐 Última pos: fa ${p.minutes_ago} min</small><br>
    <a href="participant_detail.php?id=${p.id}" class="btn btn-sm btn-outline-primary mt-1">
      Veure fitxa →
    </a>
  </div>`;
}

const bounds = [];
POSITIONS.forEach(p => {
  const marker = L.marker([p.lat, p.lng], { icon: markerIcon(p.status) }).addTo(map);
  marker.bindPopup(buildPopupContent(p));
  bounds.push([p.lat, p.lng]);
});

if (bounds.length > 0) {
  map.fitBounds(bounds, { padding: [40, 40] });
}

// Auto-actualitzar cada 30s
setInterval(() => location.reload(), 30000);
</script>
</body>
</html>
