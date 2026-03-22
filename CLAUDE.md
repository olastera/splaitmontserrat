# CLAUDE.md — Cartilla Virtual Caminada Spai-T a Montserrat

## Qui soc i context del projecte

Soc un assistent de desenvolupament per a l'app **Cartilla Virtual de la Caminada Spai-T a Montserrat**.
Treballo amb l'**Oscar**, desenvolupador del projecte, membre de l'Esplai Spai-T.

### L'app en una frase
Web app en PHP + Bootstrap per gestionar una caminada de Barcelona/Terrassa a Montserrat,
amb cartilla virtual de pelegrí, check-ins per codi secret, mapa en temps real i panel d'administració.

### Organització
- **Esplai**: Spai-T — https://esplaispait.com
- **Barri**: La Marina de Port, Barcelona
- **Valors**: cristians, infants i joves responsables i protagonistes
- **Eslògan**: "Som d'esplai, res no ens atura!"

---

## Stack tècnic

| Capa | Tecnologia |
|------|-----------|
| Backend | PHP 8+ pur (sense frameworks) |
| Frontend | Bootstrap 5 + Vanilla JS |
| Mapes | Leaflet.js + OpenStreetMap (sense API key) |
| Persistència | Fitxers JSON a `/data/users/` (sense base de dades) |
| Sessions | PHP sessions natives |
| PDF | FPDF o mPDF |
| Drag & drop | SortableJS (CDN) |
| Icones | Bootstrap Icons |
| Fonts | Google Fonts — Nunito + Open Sans |

### Dependències externes (CDN, sense npm)
```html
Bootstrap 5: https://cdn.jsdelivr.net/npm/bootstrap@5
Bootstrap Icons: https://cdn.jsdelivr.net/npm/bootstrap-icons
Leaflet.js: https://unpkg.com/leaflet
SortableJS: https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js
```

---

## Estructura del projecte

```
/
├── index.php                  ← login / registre participant
├── cartilla.php               ← app principal (mapa + parades)
├── checkin.php                ← validació codi parada (POST)
├── logout.php
├── download_pdf.php           ← generació cartilla PDF
├── update_position.php        ← endpoint AJAX posició GPS
├── toggle_location.php        ← endpoint AJAX preferència tracking
│
├── admin/
│   ├── index.php              ← login admin
│   ├── dashboard.php          ← estadístiques
│   ├── mapa.php               ← mapa temps real participants
│   ├── usuaris.php            ← gestió usuaris
│   ├── participant_detail.php ← detall + reset contrasenya
│   ├── parades.php            ← llista parades (drag & drop)
│   ├── parada_edit.php        ← crear/editar parada + mapa + preguntes
│   ├── configuracio.php       ← configuració general + visual
│   ├── export_csv.php
│   ├── logout.php
│   └── api/
│       ├── save_settings.php
│       ├── delete_user.php
│       ├── delete_all_users.php
│       ├── delete_test_users.php
│       ├── delete_parada.php
│       ├── reorder_parades.php
│       ├── get_positions.php
│       ├── toggle_gps.php
│       └── upload_logo.php
│
├── includes/
│   ├── config.php             ← constants sistema + get_settings() + save_settings()
│   ├── auth.php               ← login/logout/sessió participant i admin
│   ├── user.php               ← CRUD usuaris JSON
│   └── crypto.php             ← encriptació reversible contrasenyes (AES-256-CBC)
│
├── data/                      ← protegida amb .htaccess (Deny from all)
│   ├── .htaccess
│   ├── .gitkeep
│   ├── settings.json          ← TOTA la configuració dinàmica (NO al git)
│   └── users/
│       ├── .gitkeep
│       └── {uuid}.json        ← un fitxer per participant
│
├── assets/
│   ├── css/spait.css
│   └── img/
│
├── vendor/                    ← FPDF/mPDF
├── CLAUDE.md                  ← aquest fitxer
├── .gitignore
└── .htaccess
```

---

## Arquitectura de dades

### `/data/settings.json` — Configuració dinàmica (editable des de l'admin)
```json
{
  "event": {
    "nom": "Caminada a Montserrat 2026",
    "organitzacio": "Esplai Spai-T",
    "data_esdeveniment": "2026-04-19",
    "web": "https://esplaispait.com",
    "contacte": "722 313 772",
    "missatge_benvinguda": "...",
    "missatge_final": "...",
    "avis_global": "",
    "mode_prova": false
  },
  "visual": {
    "logo_url": "https://esplaispait.com/...",
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
  "rutes": [...],
  "parades": [...]
}
```

### `/data/users/{uuid}.json` — Participant
```json
{
  "id": "uuid-v4",
  "nom": "Maria Garcia",
  "email": "maria@example.com",
  "telefon": "612345678",
  "password_enc": "base64-aes-encrypted",
  "ruta": "llarga",
  "motivacio": "Ho faig per tradició familiar",
  "share_location": false,
  "created_at": "2026-04-15T08:00:00+02:00",
  "last_position": {
    "lat": 41.527771,
    "lng": 2.037094,
    "accuracy": 15.5,
    "timestamp": "2026-04-15T10:32:00+02:00",
    "tracking_on": true
  },
  "checkins": [
    {
      "parada_id": 0,
      "timestamp": "2026-04-15T06:30:00+02:00",
      "tipus": "inici",
      "test": {}
    }
  ]
}
```

---

## Regles de negoci importants

### Seguretat i privacitat GPS
- `last_position` **MAI s'esborra** — seguretat per menors
- El toggle de privacitat de l'usuari només atura les **actualitzacions en temps real**
- `update_user_position()` comprova `share_location` abans d'escriure — si és false, no guarda
- L'admin sempre veu l'última posició coneguda (congelada si tracking OFF)
- Colors al mapa: 🟢 temps real | 🔵 tracking off | 🟡 >10min | 🔴 >30min | 🏆 final

### Check-in
- El botó check-in **sempre està visible** (l'admin decideix si cal GPS o no via toggle)
- `REQUIRE_GPS_CHECKIN_RUNTIME` llegeix de `settings.json` en temps real
- Existeix un **codi mestre** que funciona per qualsevol parada (configurable per l'admin)
- Validació: `codi_introduit === parada['codi'] || codi_introduit === codi_mestre`

### Contrasenyes
- Encriptació **AES-256-CBC reversible** (l'admin pot recuperar la contrasenya original)
- Clau: `CRYPTO_KEY` definida a `config.php`
- L'admin pot generar una nova contrasenya aleatòria de 8 caràcters

### Persistència
- Sempre usar `file_put_contents($file, $data, LOCK_EX)` per evitar race conditions
- `get_user()` normalitza camps nous amb valors per defecte (compatibilitat vers enrere):
  - `share_location` → `false`
  - `checkins` → `[]`
  - `last_position` → no s'inicialitza (null vol dir que mai ha obert l'app)

### Admin
- Usuari/contrasenya hardcoded a `config.php` (un sol admin)
- Sessió admin separada de la sessió participant
- Zona de perill per eliminar usuaris: confirmació doble (escriure "ELIMINAR")
- `delete_test_users`: elimina usuaris creats ABANS de `data_esdeveniment`

---

## Branding Spai-T

```css
--spait-vermell:  #C0392B;   /* color primari */
--spait-verd:     #27AE60;   /* color secundari */
--spait-groc:     #F1C40F;   /* accent */
--spait-fosc:     #2C3E50;   /* textos */
```

**Logo**: `https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png`

**Tipografia**: Nunito (títols) + Open Sans (cos)

**To i veu**: proper, alegre, d'esplai — res formal. Missatges d'ànim a cada parada.
Llengua per defecte de la interfície: **català**.

---

## Punts GPS de la ruta 2026

| ID | Nom | Lat | Lng | Ruta | Codi |
|----|-----|-----|-----|------|------|
| 0 | Inici — Mundet (Barcelona) | 41.439356 | 2.147705 | llarga | — |
| 1 | 1a — Sant Cugat | 41.4731 | 2.0862 | llarga | CUGAT1 |
| 2 | 2a — Can Barata | 41.510952 | 2.066352 | llarga | BARATA2 |
| 3 | 3a — Les Fonts ⭐ | 41.527771 | 2.037094 | ambdues | FONTS3 |
| 4 | 4a — Quatre Vents | 41.541902 | 1.992529 | ambdues | VENTS4 |
| 5 | 5a — Can Cabassa | 41.535821 | 1.963115 | ambdues | CABASSA5 |
| 6 | 6a — Oasi | 41.538307 | 1.931132 | ambdues | OASI6 |
| 7 | 7a — Olesa | 41.543889 | 1.886111 | ambdues | OLESA7 |
| 8 | 8a — Aeri | 41.591353 | 1.852986 | ambdues | AERI8 |
| 9 | 9a — Monistrol | 41.609691 | 1.842395 | ambdues | MONISTROL9 |
| 10 | 🏆 MONTSERRAT | 41.593338 | 1.837625 | ambdues | MORENETA2026 |

⭐ Les Fonts és el punt d'incorporació de la ruta curta (Terrassa)

---

## Branques Git

| Branca | Contingut |
|--------|-----------|
| `master` | Versió estable i funcional |
| `admin-universal` | Nova funcionalitat: admin configurable (en curs) |

### Workflow de commits
```bash
git add .
git commit -m "✨ descripció"   # nova funcionalitat
git commit -m "🐛 descripció"   # bug fix
git commit -m "🎨 descripció"   # canvis visuals
git commit -m "🔒 descripció"   # seguretat
git commit -m "♻️ descripció"   # refactorització
git commit -m "📝 descripció"   # documentació
```

---

## Prompts aplicats (historial)

| Fitxer | Estat | Contingut |
|--------|-------|-----------|
| `CLAUDE_CODE_PROMPT.md` | ✅ Aplicat a master | App completa inicial |
| `CLAUDE_CODE_PROMPT_TRACKING.md` | ✅ Aplicat a master | Mapa temps real + toggle GPS |
| `CLAUDE_CODE_PROMPT_PRIVACY_V2.md` | ✅ Aplicat a master | Privacitat GPS (v2, substitueix v1) |
| `CLAUDE_CODE_PROMPT_MIGRATION.md` | ✅ Aplicat a master | Compatibilitat usuaris existents |
| `CLAUDE_CODE_PROMPT_ADMIN_UNIVERSAL.md` | 🔄 En curs a `admin-universal` | Admin configurable |

---

## Com treballar en aquest projecte

### Abans de fer qualsevol canvi important
```bash
git status                    # assegurar-se que no hi ha canvis pendents
git checkout -b nom-branca    # crear branca nova
```

### Patrons de codi preferits

**Llegir configuració** (sempre dinàmica):
```php
$settings = get_settings();
$parades  = $settings['parades'];
```

**Escriure fitxers** (sempre amb LOCK_EX):
```php
file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
```

**Validar sessió** (a l'inici de cada pàgina protegida):
```php
require_once 'includes/auth.php';
$current_user = get_current_user_session();
if (!$current_user) { header('Location: index.php'); exit; }
```

**Endpoints AJAX** (estructura estàndard):
```php
header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$body = json_decode(file_get_contents('php://input'), true);
// ... lògica ...
echo json_encode(['ok' => $ok]);
```

### Coses que MAI fer
- ❌ Usar base de dades (tot és JSON)
- ❌ Frameworks PHP (Laravel, Symfony...)
- ❌ npm / node (tot via CDN)
- ❌ Esborrar `last_position` quan l'usuari desactiva el tracking
- ❌ Guardar posició sense comprovar `share_location`
- ❌ Fitxers sensibles fora de `/data/` protegida

### Coses que SEMPRE fer
- ✅ Sanititzar inputs amb `htmlspecialchars()` i `filter_var()`
- ✅ `session_regenerate_id()` al login
- ✅ LOCK_EX en tots els `file_put_contents()`
- ✅ Comprovar sessió admin a cada pàgina admin
- ✅ Missatges d'error en català, amables
- ✅ Responsive first — l'app s'usa des del mòbil caminant

---

## Entorn de desenvolupament

- **Usuari**: oscar
- **Màquina**: IESPAIOLS
- **Path del projecte**: `~/iespai/www/splaitmontserrat`
- **Sistema**: Linux
- **Git**: instal·lat, configurat com Oscar / oscar@esplaispait.com
