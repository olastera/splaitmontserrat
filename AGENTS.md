# AGENTS.md — Referència tècnica per agents IA

> Font de veritat per a qualsevol agent que treballi en aquest projecte.
> Branca activa: `admin-universal`. Branca estable: `master`.

---

## Resum del projecte

Web app PHP per gestionar la **Caminada splaiT a Montserrat 2026**.
Cartilla virtual de pelegrí, check-ins per codi secret, mapa GPS en temps real, ranking i panel d'administració.

**Stack**: PHP 8+ pur · Bootstrap 5 · Leaflet.js + OSM · FPDF · SortableJS · Vanilla JS  
**Persistència**: fitxers JSON a `/data/users/` — **sense base de dades, sense frameworks**  
**Dependències externes**: totes per CDN (cap `npm`, cap `composer`)

---

## Estructura de fitxers (estat real)

```
/
├── index.php                  ← login / registre participant
├── cartilla.php               ← app principal (mapa + parades + check-in)
├── checkin.php                ← validació codi parada (POST)
├── normes.php                 ← pàgina pública de normes HTML (renderitza normes_data.php)
├── ranking.php                ← rànking general i per parada (pestanyes)
├── logout.php
├── download_pdf.php           ← generació cartilla PDF (FPDF)
├── update_position.php        ← endpoint AJAX posició GPS
├── toggle_location.php        ← endpoint AJAX preferència tracking participant
├── iniciar_ruta.php           ← endpoint AJAX per marcar inici de ruta (codi_inici)
├── diagnostico.php            ← pàgina de diagnòstic (dev/debug)
│
├── admin/
│   ├── index.php              ← login admin
│   ├── dashboard.php          ← estadístiques globals (usuaris, rutes, parades)
│   ├── mapa.php               ← mapa temps real participants
│   ├── usuaris.php            ← gestió usuaris (CRUD + zona de perill)
│   ├── participants.php       ← pàgina antiga de participants (legacy, coexisteix)
│   ├── participant_detail.php ← detall participant + reset contrasenya
│   ├── parades.php            ← llista parades (drag & drop SortableJS)
│   ├── parada_edit.php        ← crear/editar parada + mapa Leaflet + preguntes
│   ├── configuracio.php       ← configuració event, aparença, checkin, control
│   ├── export_csv.php         ← exportació CSV usuaris
│   ├── toggle_gps.php         ← toggle GPS override global (admin)
│   ├── logout.php
│   └── api/
│       ├── save_settings.php      ← desa secció del settings.json
│       ├── delete_user.php        ← elimina usuari per id
│       ├── delete_all_users.php   ← elimina tots els usuaris
│       ├── delete_test_users.php  ← elimina usuaris creats abans de data_esdeveniment
│       ├── delete_parada.php      ← elimina parada del settings
│       ├── reorder_parades.php    ← reordena parades (drag & drop)
│       ├── upload_logo.php        ← puja logo a assets/img/
│       ├── export_excel.php       ← exportació Excel usuaris (amb capçaleres)
│       ├── import_excel.php       ← importació Excel (preview + import)
│       └── download_template.php  ← descarrega plantilla Excel importació
│
├── includes/
│   ├── config.php             ← constants, $PARADES/$TESTS (fallback), get_settings(),
│   │                             save_settings(), get_default_settings(), is_gps_override()
│   │                             get_ranking_by_stop(), get_overall_ranking(),
│   │                             get_parada_name(), get_medal()
│   ├── auth.php               ← login/logout/sessió participant i admin
│   ├── user.php               ← CRUD usuaris JSON + GPS + progrés
│   ├── crypto.php             ← encrypt_password / decrypt_password (AES-256-CBC)
│   └── normes_data.php        ← contingut de la pàgina de normes (array PHP)
│
├── data/
│   ├── .htaccess              ← Deny from all (protecció total)
│   ├── settings.json          ← configuració dinàmica (NO al git)
│   └── users/
│       └── {uuid}.json        ← un fitxer per participant
│
├── assets/
│   ├── css/spait.css
│   └── img/
│
├── tests/
│   ├── TestRunner.php
│   ├── AuthTest.php
│   ├── FeaturesTest.php
│   └── PermissionTest.php
│
├── vendor/
│   └── fpdf/fpdf.php          ← instal·lat manualment (sense composer)
│
├── CLAUDE.md                  ← instruccions de col·laboració (pot tenir info desactualitzada)
└── AGENTS.md                  ← aquest fitxer (font de veritat tècnica)
```

---

## API de funcions clau

### `includes/auth.php`

| Funció | Descripció |
|--------|-----------|
| `login_user(string $identifier, string $password): bool` | Login participant (email o telèfon) |
| `logout_user(): void` | Tanca sessió participant |
| `is_logged_in(): bool` | Comprova si hi ha sessió participant activa |
| `current_user(): ?array` | Retorna l'array de l'usuari actual o null |
| `require_login(string $redirect): void` | Redirigeix si no autenticat |
| `login_admin(string $user, string $pass): bool` | Login admin |
| `logout_admin(): void` | Tanca sessió admin |
| `is_admin_logged(): bool` | Comprova sessió admin |
| `require_admin(string $redirect): void` | Redirigeix si no és admin |

> **Atenció**: CLAUDE.md menciona `get_current_user_session()` i `is_admin_logged_in()` — **no existeixen**. Usar `current_user()` i `is_admin_logged()`.

### `includes/user.php`

| Funció | Descripció |
|--------|-----------|
| `create_user(array $data): array` | Crea usuari nou (uuid, encripta password) |
| `get_user(string $id): ?array` | Llegeix usuari per id (amb normalització de camps) |
| `get_user_by_email(string $email): ?array` | Cerca per email o telèfon |
| `update_user(string $id, array $data): bool` | Actualitza camps d'un usuari |
| `save_user(array $user): bool` | Escriu fitxer JSON (LOCK_EX) |
| `get_all_users(): array` | Tots els usuaris del directori |
| `add_checkin(string $id, int $parada_id, array $test): bool` | Afegeix check-in |
| `has_checkin(string $id, int $parada_id): bool` | Comprova si ja ha fet check-in |
| `reset_password(string $id, string $new_plain): string` | Genera/desa nova contrasenya |
| `set_share_location(string $id, bool $share): bool` | Toggle tracking (MAI esborra last_position) |
| `update_user_position(string $id, float $lat, float $lng, float $accuracy): bool` | Actualitza GPS |
| `get_active_positions(): array` | Totes les posicions (per mapa admin) |
| `get_user_progress(array $user, array $parades): array` | `{total, completades, percent, acabat}` |
| `get_last_checkin_name(array $user): string` | Nom de l'última parada feta |

### `includes/config.php`

| Funció | Descripció |
|--------|-----------|
| `get_settings(): array` | Llegeix settings.json (cache estàtica per request) |
| `save_settings(array $settings): bool` | Escriu settings.json (LOCK_EX) |
| `get_default_settings(): array` | Valors per defecte (fallback si no hi ha JSON) |
| `is_gps_override(): bool` | Retorna `settings['gps_override']` |
| `get_ranking_by_stop(int $parada_id): array` | Top 10 primers a arribar a una parada |
| `get_overall_ranking(): array` | Top 10 per parades completades |
| `get_parada_name(int $parada_id): string` | Nom d'una parada per id |
| `get_medal(int $posicion): string` | Emoji medalla (🥇🥈🥉) |

### `includes/crypto.php`

| Funció | Descripció |
|--------|-----------|
| `encrypt_password(string $plain): string` | AES-256-CBC → base64 |
| `decrypt_password(string $enc): string` | Inversa |

---

## Format de dades

### `data/settings.json`

```json
{
  "gps_override": false,
  "event": {
    "nom": "Caminada a Montserrat 2026",
    "organitzacio": "splaiT",
    "data_esdeveniment": "2026-04-19",
    "web": "https://esplaispait.com",
    "contacte": "722 313 772",
    "missatge_benvinguda": "...",
    "missatge_final": "...",
    "avis_global": "",
    "mode_prova": false,
    "registre_obert": true
  },
  "visual": {
    "logo_url": "https://...",
    "logo_local": "",
    "color_primari": "#C0392B",
    "color_secundari": "#27AE60",
    "color_accent": "#F1C40F",
    "nom_app": "Cartilla del Pelegrí"
  },
  "checkin": {
    "require_gps": false,
    "radi_metres": 200,
    "codi_mestre": "",
    "codi_inici": ""
  },
  "rutes": [
    {"id": "llarga", "nom": "Ruta Llarga (Barcelona)", "descripcio": ""},
    {"id": "curta",  "nom": "Ruta Curta (Terrassa)",   "descripcio": ""}
  ],
  "parades": [
    {
      "id": 0,
      "nom": "Inici — Mundet",
      "rutes": ["llarga"],
      "lat": 41.439356, "lng": 2.147705,
      "codi": null,
      "radi_metres": null,
      "es_inici": true,
      "es_final": false,
      "missatge_arribada": "",
      "preguntes": []
    }
  ]
}
```

> **Format parades NOU**: `rutes: []` (array) en lloc de `ruta: string`.  
> `get_user_progress()` suporta ambdós formats per compatibilitat.  
> `es_inici`/`es_final` en lloc de `inici`/`final`.

### `data/users/{uuid}.json`

```json
{
  "id": "uuid-v4",
  "nom": "Maria Garcia",
  "email": "maria@example.com",
  "telefon": "612345678",
  "password_enc": "base64-aes",
  "ruta": "llarga",
  "motivacio": "...",
  "share_location": false,
  "created_at": "2026-04-15T08:00:00+02:00",
  "last_position": {
    "lat": 41.527771, "lng": 2.037094,
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

## Regles crítiques

### GPS i privacitat
- `last_position` **MAI s'esborra** — seguretat per menors
- `set_share_location()` només atura actualitzacions futures, no esborra el passat
- `update_user_position()` escriu sempre (independentment de share_location)
- L'admin sempre veu l'última posició coneguda

### Colors d'estat al mapa admin
| Color | Significat |
|-------|-----------|
| 🟢 verd | Actiu, posició <10 min |
| 🔵 blau | Tracking OFF |
| 🟡 groc | Posició entre 10-30 min |
| 🔴 vermell | Posició >30 min |
| 🏆 trofeu | Ha completat Montserrat |

### Check-in
- El codi mestre (`checkin.codi_mestre`) funciona per qualsevol parada
- El codi d'inici (`checkin.codi_inici`) és per `iniciar_ruta.php`
- `gps_override` a true força check-in sense GPS independentment de `require_gps`

### Persistència
- Sempre `file_put_contents($path, $data, LOCK_EX)`
- `get_settings()` té cache estàtica — una sola lectura per request

### Branding
- Nom correcte de l'organització: **splaiT** (no "Spai-T", no "spait")
- Classes CSS internes: `btn-spait`, `navbar-spait` (correcte)
- Llengua de la interfície: **català**

---

## Patrons de codi estàndard

**Pàgina participant protegida:**
```php
require_once 'includes/auth.php';
require_login('index.php');
$user = current_user();
```

**Pàgina admin protegida:**
```php
require_once __DIR__ . '/../includes/auth.php';
require_admin('index.php');
```

**Endpoint AJAX admin (POST JSON):**
```php
header('Content-Type: application/json');
if (!is_admin_logged()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
$body = json_decode(file_get_contents('php://input'), true);
// ... lògica ...
echo json_encode(['ok' => $ok]);
```

**Llegir configuració:**
```php
$settings = get_settings();
$parades  = $settings['parades'] ?? [];
```

---

## Coses que MAI fer

- Usar base de dades
- Usar frameworks PHP (Laravel, Symfony…)
- Usar npm / node
- Esborrar `last_position` quan l'usuari desactiva tracking
- Escriure fitxers sense `LOCK_EX`
- Posar dades sensibles fora de `/data/` (protegida per .htaccess)
- Trucar `is_admin_logged_in()` — **no existeix**, usar `is_admin_logged()`
- Trucar `get_current_user_session()` — **no existeix**, usar `current_user()`
