# Prompt Claude Code — Admin Universal (Configuració, Punts, Preguntes i Usuaris)

## Objectiu

Convertir l'admin en un panell de configuració complet que permeti a qualsevol
esplai adaptar l'app a la seva caminada sense tocar codi.

Tota la configuració dinàmica es guarda a `/data/settings.json`.
El fitxer `includes/config.php` passa a ser només valors per defecte i constants
de sistema (admin user/pass, paths). Tot el rest ve del JSON.

---

## Estructura nova de `/data/settings.json`

Aquest fitxer conté TOTA la configuració editable des de l'admin:

```json
{
  "event": {
    "nom": "Caminada a Montserrat 2026",
    "organitzacio": "Esplai Spai-T",
    "data_esdeveniment": "2026-04-19",
    "web": "https://esplaispait.com",
    "contacte": "722 313 772",
    "missatge_benvinguda": "Benvingut/da a la caminada! Som d'esplai, res no ens atura!",
    "missatge_final": "HO HAS ACONSEGUIT! Ets increïble! 🏔️🎉",
    "avis_global": "",
    "mode_prova": false,
    "dates_actives": {
      "inici": "2026-04-01",
      "fi": "2026-04-20"
    }
  },
  "visual": {
    "logo_url": "https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png",
    "logo_local": "",
    "color_primari": "#C0392B",
    "color_secundari": "#27AE60",
    "color_accent": "#F1C40F",
    "nom_app": "Cartilla del Pelegrí"
  },
  "checkin": {
    "require_gps": false,
    "radi_metres": 200,
    "codi_mestre": ""
  },
  "rutes": [
    {
      "id": "llarga",
      "nom": "Ruta Llarga (Barcelona - Mundet)",
      "descripcio": "Sortida des de Mundet, Barcelona"
    },
    {
      "id": "curta",
      "nom": "Ruta Curta (Terrassa - Les Fonts)",
      "descripcio": "Sortida des de Les Fonts, Terrassa"
    }
  ],
  "parades": [
    {
      "id": 0,
      "nom": "Inici — Mundet (Barcelona)",
      "rutes": ["llarga"],
      "lat": 41.439356,
      "lng": 2.147705,
      "codi": null,
      "radi_metres": null,
      "es_inici": true,
      "es_final": false,
      "missatge_arribada": "Bon camí! Endavant! 💪",
      "preguntes": []
    },
    {
      "id": 3,
      "nom": "3a Parada — Les Fonts",
      "rutes": ["llarga", "curta"],
      "lat": 41.527771,
      "lng": 2.037094,
      "codi": "FONTS3",
      "radi_metres": null,
      "es_inici_ruta": "curta",
      "es_final": false,
      "missatge_arribada": "Perfecte! Ja s'uneix la gent de Terrassa! 🎉",
      "preguntes": [
        {
          "id": "p3_1",
          "text": "Com t'has sentit incorporant-te / rebent la gent de Terrassa?",
          "tipus": "opcions",
          "opcions": ["Alegre", "Emocionat/da", "Normal"]
        },
        {
          "id": "p3_2",
          "text": "Estàs gaudint del paisatge?",
          "tipus": "opcions",
          "opcions": ["Molt", "Bastant", "Estic massa cansat/da per mirar"]
        },
        {
          "id": "p3_3",
          "text": "Dedica una paraula a algú que portes al cor avui",
          "tipus": "text"
        }
      ]
    },
    {
      "id": 10,
      "nom": "MONTSERRAT!!!",
      "rutes": ["llarga", "curta"],
      "lat": 41.593338,
      "lng": 1.837625,
      "codi": "MORENETA2026",
      "radi_metres": null,
      "es_inici": false,
      "es_final": true,
      "missatge_arribada": "HO HAS ACONSEGUIT! Som d'esplai, res no ens atura! 🏔️🎉",
      "preguntes": [
        {
          "id": "p10_1",
          "text": "HO HEM ACONSEGUIT! Quin sentiment predomina?",
          "tipus": "opcions",
          "opcions": ["Alegria", "Emoció", "Orgull", "Pau interior", "Tot alhora 🎉"]
        },
        {
          "id": "p10_2",
          "text": "Repetiràs l'any que ve?",
          "tipus": "opcions",
          "opcions": ["Sí sense dubte!", "Crec que sí", "Pregunta'm d'aquí uns dies"]
        },
        {
          "id": "p10_3",
          "text": "Un missatge per guardar per sempre",
          "tipus": "text"
        }
      ]
    }
  ]
}
```

---

## Fitxers nous a crear

```
admin/
├── configuracio.php          ← configuració general + visual
├── parades.php               ← llista i gestió de parades
├── parada_edit.php           ← crear / editar parada (amb mapa i preguntes)
├── rutes.php                 ← gestió de rutes
├── usuaris.php               ← gestió usuaris (substitueix participants.php)
│
api/
├── save_settings.php         ← desa qualsevol secció del settings.json
├── delete_parada.php         ← elimina una parada
├── reorder_parades.php       ← reordena parades (drag & drop)
├── delete_user.php           ← elimina un usuari individual
├── delete_all_users.php      ← elimina tots els usuaris
├── delete_test_users.php     ← elimina usuaris de prova
└── upload_logo.php           ← puja logo local
```

## Fitxers a modificar

```
includes/config.php           ← afegir funció get_settings()
includes/user.php             ← adaptar a settings dinàmics
admin/dashboard.php           ← afegir accés ràpid a nova configuració
```

---

## 1. `includes/config.php` — Funció `get_settings()`

Afegir al final del fitxer:

```php
/**
 * Retorna la configuració completa des de settings.json
 * amb fallback als valors per defecte hardcoded.
 */
function get_settings(): array {
    static $settings = null; // cache per evitar llegir el fitxer múltiples vegades
    if ($settings !== null) return $settings;

    $file = __DIR__ . '/../data/settings.json';
    if (file_exists($file)) {
        $loaded = json_decode(file_get_contents($file), true);
        if ($loaded) {
            $settings = $loaded;
            return $settings;
        }
    }

    // Fallback: valors per defecte (la configuració actual hardcoded)
    $settings = get_default_settings();
    return $settings;
}

function get_default_settings(): array {
    global $PARADES; // l'array existent al config.php
    return [
        'event' => [
            'nom'                  => 'Caminada a Montserrat 2026',
            'organitzacio'         => 'Esplai Spai-T',
            'data_esdeveniment'    => '2026-04-19',
            'web'                  => 'https://esplaispait.com',
            'contacte'             => '722 313 772',
            'missatge_benvinguda'  => 'Benvingut/da a la caminada!',
            'missatge_final'       => 'HO HAS ACONSEGUIT! 🏔️🎉',
            'avis_global'          => '',
            'mode_prova'           => false,
        ],
        'visual' => [
            'logo_url'       => 'https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png',
            'logo_local'     => '',
            'color_primari'  => '#C0392B',
            'color_secundari'=> '#27AE60',
            'color_accent'   => '#F1C40F',
            'nom_app'        => 'Cartilla del Pelegrí',
        ],
        'checkin' => [
            'require_gps'  => false,
            'radi_metres'  => 200,
            'codi_mestre'  => '',
        ],
        'rutes'   => [
            ['id' => 'llarga', 'nom' => 'Ruta Llarga (Barcelona)', 'descripcio' => ''],
            ['id' => 'curta',  'nom' => 'Ruta Curta (Terrassa)',   'descripcio' => ''],
        ],
        'parades' => $PARADES, // array existent
    ];
}

function save_settings(array $settings): bool {
    $file = __DIR__ . '/../data/settings.json';
    return file_put_contents(
        $file,
        json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    ) !== false;
}
```

---

## 2. `admin/configuracio.php` — Configuració general + visual

Pàgina amb tres seccions en tabs Bootstrap:

### Tab 1 — Informació de l'esdeveniment

```
Nom de l'esdeveniment    [input text]
Organització             [input text]
Data de l'esdeveniment   [input date]
Web                      [input url]
Telèfon de contacte      [input text]
Missatge de benvinguda   [textarea]
Missatge final (meta)    [textarea]
```

### Tab 2 — Aparença

```
Logo actual              [imatge preview]
URL del logo             [input url]  ← o puja un fitxer →  [input file]
Color primari            [input color] (color picker HTML5)
Color secundari          [input color]
Color accent             [input color]
Nom de l'app             [input text]

[Preview en temps real] ← mostra com quedarà la navbar i el botó
```

El preview en temps real funciona amb JS escoltant els canvis dels inputs
i actualitzant CSS variables en un div de mostra:

```javascript
document.getElementById('color_primari').addEventListener('input', function() {
    document.getElementById('preview-navbar').style.backgroundColor = this.value;
    document.getElementById('preview-btn').style.backgroundColor = this.value;
});
```

### Tab 3 — Avisos i control

```
Avís global (banner)     [textarea]  ← si no és buit, apareix a tots els participants
Mode prova               [toggle]    ← check-ins no compten
Codi mestre              [input text + botó "Generar aleatori"]
Radi GPS check-in        [input number] metres
Requerir GPS             [toggle]
```

**Botó "Desar canvis"** → POST a `api/save_settings.php`

---

## 3. `admin/parades.php` — Llista de parades

**Capçalera**: títol + botó "Nova parada +"

**Llista de parades** (cards o taula) amb drag & drop per reordenar:

```
┌──────────────────────────────────────────────────────────┐
│ ⠿  0  │ 🟢 Inici — Mundet        │ Rutes: Llarga        │
│        │ 📍 41.4393, 2.1477       │ Codi: —              │
│        │ ❓ 0 preguntes           │ [Editar] [Eliminar]  │
├──────────────────────────────────────────────────────────┤
│ ⠿  1  │ 1a Parada — Sant Cugat   │ Rutes: Llarga        │
│        │ 📍 41.4731, 2.0862       │ Codi: CUGAT1         │
│        │ ❓ 3 preguntes           │ [Editar] [Eliminar]  │
└──────────────────────────────────────────────────────────┘
```

- Icona ⠿ a l'esquerra = handle per drag & drop (SortableJS)
- Badge de color per rutes: verd=llarga, blau=curta, groc=ambdues
- Badge especial per inici i final
- Confirmació abans d'eliminar: "Segur que vols eliminar aquest punt? Els check-ins existents d'aquesta parada es conservaran."

**Implementació drag & drop amb SortableJS** (CDN):
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
Sortable.create(document.getElementById('parades-list'), {
    handle: '.drag-handle',
    animation: 150,
    onEnd: function(evt) {
        const order = [...document.querySelectorAll('.parada-item')]
            .map(el => el.dataset.id);
        fetch('../api/reorder_parades.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order })
        });
    }
});
</script>
```

---

## 4. `admin/parada_edit.php` — Editar / Crear parada

URL: `parada_edit.php?id=3` per editar, `parada_edit.php` per crear nova.

**Secció 1 — Dades bàsiques**:
```
Nom de la parada         [input text]
És punt d'inici?         [checkbox]
És punt final?           [checkbox]
Rutes que hi passen      [checkboxes: una per cada ruta]
Codi secret              [input text] + [botó "Generar"] + [botó "Mostrar/Ocultar"]
Radi GPS (metres)        [input number] placeholder="200 per defecte"
Missatge en arribar      [input text] placeholder="Endavant! Ja queda menys 💪"
```

**Secció 2 — Coordenades GPS** (amb mapa interactiu):

```html
<!-- Mapa Leaflet per seleccionar coordenades clicant -->
<div id="edit-map" style="height: 350px; border-radius: 8px;"></div>
<div class="row mt-2">
  <div class="col">
    <label>Latitud</label>
    <input type="number" id="lat" name="lat" step="0.000001" class="form-control">
  </div>
  <div class="col">
    <label>Longitud</label>
    <input type="number" id="lng" name="lng" step="0.000001" class="form-control">
  </div>
</div>
<small class="text-muted">Clica al mapa per seleccionar la ubicació, o introdueix les coordenades manualment.</small>
```

```javascript
// Mapa editable — clic posa marcador i omple els inputs
const editMap = L.map('edit-map').setView([41.5, 1.9], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(editMap);

let editMarker = null;

// Si editem una parada existent, mostrar marcador inicial
const initLat = parseFloat(document.getElementById('lat').value);
const initLng = parseFloat(document.getElementById('lng').value);
if (initLat && initLng) {
    editMarker = L.marker([initLat, initLng]).addTo(editMap);
    editMap.setView([initLat, initLng], 15);
}

editMap.on('click', function(e) {
    const { lat, lng } = e.latlng;
    document.getElementById('lat').value = lat.toFixed(6);
    document.getElementById('lng').value = lng.toFixed(6);
    if (editMarker) editMap.removeLayer(editMarker);
    editMarker = L.marker([lat, lng]).addTo(editMap);
});

// Sincronitzar inputs → mapa
['lat', 'lng'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        const lat = parseFloat(document.getElementById('lat').value);
        const lng = parseFloat(document.getElementById('lng').value);
        if (lat && lng) {
            if (editMarker) editMap.removeLayer(editMarker);
            editMarker = L.marker([lat, lng]).addTo(editMap);
            editMap.setView([lat, lng], 15);
        }
    });
});
```

**Secció 3 — Preguntes del test**

Fins a 3 preguntes per parada. Cada pregunta té:
```
Pregunta [N]             [input text] placeholder="Escriu la pregunta..."
Tipus de resposta        [select: "Opcions / Text lliure / Estrelles 1-5"]

Si tipus = "Opcions":
  Opcions                [tags input] ← escriu opció + Enter per afegir
                         (fins a 5 opcions per pregunta)

[+ Afegir pregunta]  (fins a 3)
[× Eliminar pregunta]
```

Implementació del tags input per opcions (sense llibreries externes):
```javascript
function addTagInput(containerId) {
    const container = document.getElementById(containerId);
    const input = container.querySelector('.tag-input');

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && this.value.trim()) {
            e.preventDefault();
            const tag = document.createElement('span');
            tag.className = 'badge bg-secondary me-1 mb-1';
            tag.innerHTML = `${this.value.trim()} <i class="bi bi-x" onclick="this.parentElement.remove()"></i>`;
            tag.dataset.value = this.value.trim();
            container.insertBefore(tag, input);
            this.value = '';
        }
    });
}
```

**Botons finals**:
- `[Desar parada]` → POST a `api/save_settings.php` amb la parada actualitzada
- `[Cancel·lar]` → torna a `parades.php`
- `[Eliminar parada]` (només si editem) → confirmar + DELETE a `api/delete_parada.php`

---

## 5. `admin/usuaris.php` — Gestió d'usuaris (substitueix participants.php)

**Zona de perill** (fons vermell clar, col·lapsable) a la part superior:

```html
<div class="card border-danger mb-4">
  <div class="card-header bg-danger text-white d-flex justify-content-between"
       data-bs-toggle="collapse" data-bs-target="#zona-perill" style="cursor:pointer">
    <span>⚠️ Zona de perill — Eliminació d'usuaris</span>
    <i class="bi bi-chevron-down"></i>
  </div>
  <div class="collapse" id="zona-perill">
    <div class="card-body">
      <div class="row g-3">

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body text-center">
              <h6>🧹 Eliminar usuaris de prova</h6>
              <p class="small text-muted">Elimina els usuaris creats durant les proves
              (registrats abans de la data de l'esdeveniment)</p>
              <button class="btn btn-warning btn-sm" id="btn-delete-test">
                Eliminar proves
              </button>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body text-center">
              <h6>🗑️ Eliminar TOTS els usuaris</h6>
              <p class="small text-muted">Esborra tots els participants.
              Útil per preparar l'app per a un nou any.</p>
              <button class="btn btn-danger btn-sm" id="btn-delete-all">
                Eliminar tots
              </button>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card h-100">
            <div class="card-body text-center">
              <h6>🔄 Reset per nou any</h6>
              <p class="small text-muted">Elimina tots els usuaris i reseteja
              els codis de parada. Manté la configuració visual i els punts.</p>
              <button class="btn btn-danger btn-sm" id="btn-reset-year">
                Nou any
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
```

**Confirmació doble per accions destructives** (modal Bootstrap):
```javascript
// Exemple per "Eliminar tots"
document.getElementById('btn-delete-all').addEventListener('click', function() {
    // Primera confirmació
    if (!confirm('Segur que vols eliminar TOTS els participants? Aquesta acció no es pot desfer.')) return;

    // Segona confirmació — escriure la paraula CONFIRMAR
    const word = prompt('Escriu ELIMINAR per confirmar:');
    if (word !== 'ELIMINAR') {
        alert('Acció cancel·lada.');
        return;
    }

    fetch('../api/delete_all_users.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                showToast(`${data.deleted} usuaris eliminats correctament`, 'success');
                setTimeout(() => location.reload(), 1500);
            }
        });
});
```

**Taula de participants** (la mateixa que participants.php, afegir columna d'accions):
- Botó [🗑️] per eliminar individual → confirmació simple → `api/delete_user.php?id=XXX`
- Botó [👁️] per veure fitxa → `participant_detail.php?id=XXX`
- Botó [🔑] per reset contrasenya → inline

---

## 6. Endpoints API (`admin/api/`)

### `save_settings.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body = json_decode(file_get_contents('php://input'), true);
$section  = $body['section']  ?? null;  // 'event', 'visual', 'parades', etc.
$data     = $body['data']     ?? null;

if (!$section || !$data) {
    echo json_encode(['ok' => false, 'error' => 'Falten dades']);
    exit;
}

$settings = get_settings();

// Validació bàsica per secció
$allowed_sections = ['event', 'visual', 'checkin', 'rutes', 'parades'];
if (!in_array($section, $allowed_sections)) {
    echo json_encode(['ok' => false, 'error' => 'Secció no vàlida']);
    exit;
}

// Sanititzar inputs
if ($section === 'parades') {
    // Validar que cada parada tingui id, nom, lat, lng
    foreach ($data as $p) {
        if (!isset($p['id'], $p['nom'], $p['lat'], $p['lng'])) {
            echo json_encode(['ok' => false, 'error' => 'Parada incompleta']);
            exit;
        }
    }
}

$settings[$section] = $data;
$ok = save_settings($settings);

echo json_encode(['ok' => $ok]);
```

### `delete_user.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { echo json_encode(['ok' => false]); exit; }

$file = DATA_PATH . preg_replace('/[^a-zA-Z0-9\-]/', '', $id) . '.json';
$ok = file_exists($file) && unlink($file);

echo json_encode(['ok' => $ok]);
```

### `delete_all_users.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$files   = glob(DATA_PATH . '*.json');
$deleted = 0;
foreach ($files as $file) {
    if (unlink($file)) $deleted++;
}

echo json_encode(['ok' => true, 'deleted' => $deleted]);
```

### `delete_test_users.php`
```php
<?php
// Elimina usuaris registrats ABANS de la data de l'esdeveniment
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$settings        = get_settings();
$data_event      = $settings['event']['data_esdeveniment'] ?? date('Y-m-d');
$event_timestamp = strtotime($data_event);

$users   = get_all_users();
$deleted = 0;

foreach ($users as $user) {
    $created = strtotime($user['created_at'] ?? '2000-01-01');
    if ($created < $event_timestamp) {
        $file = DATA_PATH . $user['id'] . '.json';
        if (file_exists($file) && unlink($file)) $deleted++;
    }
}

echo json_encode(['ok' => true, 'deleted' => $deleted]);
```

### `delete_parada.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body     = json_decode(file_get_contents('php://input'), true);
$parada_id = $body['id'] ?? null;

if ($parada_id === null) { echo json_encode(['ok' => false]); exit; }

$settings = get_settings();
$settings['parades'] = array_values(
    array_filter($settings['parades'], fn($p) => $p['id'] != $parada_id)
);

echo json_encode(['ok' => save_settings($settings)]);
```

### `reorder_parades.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body  = json_decode(file_get_contents('php://input'), true);
$order = $body['order'] ?? []; // array d'ids en nou ordre

$settings = get_settings();
$parades  = $settings['parades'];

// Reordenar segons l'array rebut
$indexed = [];
foreach ($parades as $p) $indexed[$p['id']] = $p;

$reordered = [];
foreach ($order as $id) {
    if (isset($indexed[$id])) $reordered[] = $indexed[$id];
}

$settings['parades'] = $reordered;
echo json_encode(['ok' => save_settings($settings)]);
```

### `upload_logo.php`
```php
<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
$file = $_FILES['logo'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Error pujant fitxer']);
    exit;
}

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['ok' => false, 'error' => 'Tipus de fitxer no permès']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) { // 2MB màxim
    echo json_encode(['ok' => false, 'error' => 'El fitxer és massa gran (màx 2MB)']);
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'logo_' . time() . '.' . $ext;
$dest     = __DIR__ . '/../../assets/img/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut desar el fitxer']);
    exit;
}

$url = '/assets/img/' . $filename;

// Guardar a settings
$settings = get_settings();
$settings['visual']['logo_local'] = $url;
save_settings($settings);

echo json_encode(['ok' => true, 'url' => $url]);
```

---

## 7. Navbar admin — Actualitzar menú

Afegir al menú de navegació de l'admin les noves seccions:

```html
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--color-primari)">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <img src="<?= $settings['visual']['logo_local'] ?: $settings['visual']['logo_url'] ?>"
           height="35" alt="Logo">
      Admin
    </a>
    <div class="navbar-nav">
      <a class="nav-link" href="dashboard.php">📊 Dashboard</a>
      <a class="nav-link" href="mapa.php">🗺️ Mapa</a>
      <a class="nav-link" href="usuaris.php">👥 Usuaris</a>
      <a class="nav-link" href="parades.php">📍 Parades</a>
      <a class="nav-link" href="configuracio.php">⚙️ Configuració</a>
      <a class="nav-link text-warning" href="logout.php">Sortir</a>
    </div>
  </div>
</nav>
```

---

## 8. Adaptar `cartilla.php` i `checkin.php` per llegir settings dinàmics

Substituir les referències hardcoded a l'array `$PARADES` i constants per:

```php
// En lloc de: global $PARADES;
$settings = get_settings();
$parades  = $settings['parades'];
$rutes    = $settings['rutes'];

// En lloc de: define('REQUIRE_GPS_CHECKIN_RUNTIME', ...)
$require_gps = $settings['checkin']['require_gps'] ?? false;
$radi        = $settings['checkin']['radi_metres'] ?? 200;
$codi_mestre = $settings['checkin']['codi_mestre'] ?? '';

// Validació del codi a checkin.php — acceptar codi_mestre
function validate_codi(string $codi_introduit, array $parada, string $codi_mestre): bool {
    if ($codi_mestre && $codi_introduit === $codi_mestre) return true;
    return $parada['codi'] && $codi_introduit === $parada['codi'];
}

// Avís global — mostrar a cartilla.php si existeix
$avis = $settings['event']['avis_global'] ?? '';
// → mostrar com a banner alert-warning a la part superior si no és buit
```

---

## Resum de fitxers

| Fitxer | Acció |
|--------|-------|
| `admin/configuracio.php` | **Crear** |
| `admin/parades.php` | **Crear** |
| `admin/parada_edit.php` | **Crear** |
| `admin/usuaris.php` | **Crear** (substitueix participants.php) |
| `admin/api/save_settings.php` | **Crear** |
| `admin/api/delete_user.php` | **Crear** |
| `admin/api/delete_all_users.php` | **Crear** |
| `admin/api/delete_test_users.php` | **Crear** |
| `admin/api/delete_parada.php` | **Crear** |
| `admin/api/reorder_parades.php` | **Crear** |
| `admin/api/upload_logo.php` | **Crear** |
| `includes/config.php` | **Modificar** — afegir get_settings() i save_settings() |
| `cartilla.php` | **Modificar** — llegir settings dinàmics |
| `checkin.php` | **Modificar** — codi mestre + settings dinàmics |
| `admin/dashboard.php` | **Modificar** — navbar nova |
| `data/settings.json` | **Crear** amb valors per defecte Spai-T |
