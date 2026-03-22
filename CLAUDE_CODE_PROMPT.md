# Prompt per Claude Code — Cartilla Virtual Caminada Spai-T a Montserrat 2026

## Context del projecte

Construeix una web app completa en **PHP 8+ i Bootstrap 5** (sense base de dades) per a la caminada anual de l'**Esplai Spai-T** (https://esplaispait.com) de Barcelona/Terrassa a Montserrat.

Els participants fan una caminada amb 10 parades. En cada parada un responsable dona un codi secret que valida l'arribada. Al final es pot descarregar una "cartilla de pelegrí" en PDF.

---

## Stack tècnic

- **Backend**: PHP 8+ pur (sense frameworks)
- **Frontend**: Bootstrap 5 + Leaflet.js + OpenStreetMap
- **Persistència**: Fitxers JSON a `/data/users/` (sense BD)
- **Sessions**: PHP sessions natives
- **PDF**: Llibreria **FPDF** o **mPDF** (escull la més lleugera)
- **Mapes**: Leaflet.js + tiles OpenStreetMap (gratuït, sense API key)
- **Icones**: Bootstrap Icons

---

## Branding Spai-T

- **Logo**: `https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png`
- **Eslògan**: "Som d'esplai, res no ens atura!"
- **Colors**:
```css
--spait-vermell: #C0392B;
--spait-verd:    #27AE60;
--spait-groc:    #F1C40F;
--spait-fosc:    #2C3E50;
--spait-blanc:   #FFFFFF;
```
- **Tipografia**: Google Fonts — `Nunito` (titols) + `Open Sans` (cos)

---

## Estructura de fitxers a generar

```
/
├── index.php                  ← login / registre participant
├── cartilla.php               ← app principal (mapa + parades)
├── checkin.php                ← validació codi parada (POST)
├── logout.php                 ← tancar sessió
├── download_pdf.php           ← generació i descàrrega cartilla PDF
│
├── admin/
│   ├── index.php              ← login admin
│   ├── dashboard.php          ← estadístiques generals
│   ├── participants.php       ← llista participants + cerca
│   ├── participant_detail.php ← detall + reset contrasenya
│   ├── export_csv.php         ← exportació CSV
│   └── logout.php
│
├── includes/
│   ├── config.php             ← tota la configuració (punts GPS, codis, admin pw)
│   ├── auth.php               ← funcions login/logout/sessió
│   ├── user.php               ← CRUD usuaris en JSON
│   └── crypto.php             ← encriptació reversible contrasenyes
│
├── data/
│   ├── .htaccess              ← Deny from all
│   └── users/                 ← un fitxer .json per participant
│
├── assets/
│   ├── css/
│   │   └── spait.css          ← estils personalitzats
│   └── img/
│       └── (logo local opcional)
│
├── vendor/                    ← FPDF o mPDF (instal·lat amb composer o manual)
└── .htaccess                  ← protecció carpeta data/
```

---

## `includes/config.php` — Configuració completa

```php
<?php
// ADMIN
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'spait2026');
define('CRYPTO_KEY', 'SpaiT_2026_SecretKey_Montserrat');

// RUTES
define('DATA_PATH', __DIR__ . '/../data/users/');

// PUNTS DE PARADA
// ruta: 'llarga' = només ruta Barcelona | 'ambdues' = les dues rutes
$PARADES = [
  [
    'id'        => 0,
    'nom'       => 'Inici — Mundet (Barcelona)',
    'ruta'      => 'llarga',
    'lat'       => 41.439356,
    'lng'       => 2.147705,
    'codi'      => null,   // punt de sortida, no necessita codi
    'inici'     => true,
  ],
  [
    'id'        => 1,
    'nom'       => '1a Parada — Sant Cugat',
    'ruta'      => 'llarga',
    'lat'       => 41.4731,
    'lng'       => 2.0862,
    'codi'      => 'CUGAT1',
  ],
  [
    'id'        => 2,
    'nom'       => '2a Parada — Can Barata',
    'ruta'      => 'llarga',
    'lat'       => 41.510952,
    'lng'       => 2.066352,
    'codi'      => 'BARATA2',
  ],
  [
    'id'        => 3,
    'nom'       => '3a Parada — Les Fonts (Inici Ruta Curta)',
    'ruta'      => 'ambdues',
    'lat'       => 41.527771,
    'lng'       => 2.037094,
    'codi'      => 'FONTS3',
    'inici_curt' => true,  // punt d'incorporació ruta Terrassa
  ],
  [
    'id'        => 4,
    'nom'       => '4a Parada — Quatre Vents',
    'ruta'      => 'ambdues',
    'lat'       => 41.541902,
    'lng'       => 1.992529,
    'codi'      => 'VENTS4',
  ],
  [
    'id'        => 5,
    'nom'       => '5a Parada — Can Cabassa',
    'ruta'      => 'ambdues',
    'lat'       => 41.535821,
    'lng'       => 1.963115,
    'codi'      => 'CABASSA5',
  ],
  [
    'id'        => 6,
    'nom'       => '6a Parada — Oasi',
    'ruta'      => 'ambdues',
    'lat'       => 41.538307,
    'lng'       => 1.931132,
    'codi'      => 'OASI6',
  ],
  [
    'id'        => 7,
    'nom'       => '7a Parada — Olesa de Montserrat',
    'ruta'      => 'ambdues',
    'lat'       => 41.543889,
    'lng'       => 1.886111,
    'codi'      => 'OLESA7',
  ],
  [
    'id'        => 8,
    'nom'       => '8a Parada — Aeri',
    'ruta'      => 'ambdues',
    'lat'       => 41.591353,
    'lng'       => 1.852986,
    'codi'      => 'AERI8',
  ],
  [
    'id'        => 9,
    'nom'       => '9a Parada — Monistrol',
    'ruta'      => 'ambdues',
    'lat'       => 41.609691,
    'lng'       => 1.842395,
    'codi'      => 'MONISTROL9',
  ],
  [
    'id'        => 10,
    'nom'       => '🏆 MONTSERRAT!!!',
    'ruta'      => 'ambdues',
    'lat'       => 41.593338,
    'lng'       => 1.837625,
    'codi'      => 'MORENETA2026',
    'final'     => true,
  ],
];
```

---

## `includes/crypto.php` — Encriptació reversible

```php
<?php
// Encriptació AES reversible per contrasenyes
// L'admin pot recuperar la contrasenya original d'un participant

function encrypt_password(string $plain): string {
    $key = substr(hash('sha256', CRYPTO_KEY), 0, 32);
    $iv  = openssl_random_pseudo_bytes(16);
    $enc = openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . '::' . $enc);
}

function decrypt_password(string $encrypted): string {
    $key   = substr(hash('sha256', CRYPTO_KEY), 0, 32);
    $parts = explode('::', base64_decode($encrypted), 2);
    if (count($parts) !== 2) return '';
    [$iv, $enc] = $parts;
    return openssl_decrypt($enc, 'AES-256-CBC', $key, 0, $iv);
}
```

---

## `includes/user.php` — Estructura JSON de cada usuari

Cada participant es guarda com `/data/users/{uuid}.json`:

```json
{
  "id": "uuid-v4",
  "nom": "Maria Garcia",
  "email": "maria@example.com",
  "telefon": "612345678",
  "password_enc": "base64encodedencryptedstring",
  "ruta": "llarga",
  "motivacio": "Ho faig per tradició familiar i per la fe",
  "created_at": "2026-04-15T08:00:00",
  "checkins": [
    {
      "parada_id": 0,
      "timestamp": "2026-04-15T06:30:00",
      "tipus": "inici"
    },
    {
      "parada_id": 1,
      "timestamp": "2026-04-15T09:15:00",
      "test": {
        "p1": "Bé",
        "p2": "Moderat",
        "p3": "El bosc de pi, és molt bonic"
      }
    }
  ]
}
```

Funcions a implementar a `user.php`:
- `create_user(array $data): array`
- `get_user(string $id): ?array`
- `get_user_by_email(string $email): ?array`
- `update_user(string $id, array $data): bool`
- `get_all_users(): array`
- `add_checkin(string $id, int $parada_id, array $test = []): bool`
- `has_checkin(string $id, int $parada_id): bool`
- `reset_password(string $id, string $new_plain): string` ← retorna la nova en pla

---

## `index.php` — Registre i Login

**Dues pestanyes Bootstrap**: "Registre" i "Ja tinc compte"

**Formulari de registre**:
- Nom complet (required)
- E-mail o telèfon (almenys un dels dos, required)
- Contrasenya (required, mínim 6 caràcters)
- Ruta: selector `Llarga (Barcelona - Mundet)` / `Curta (Terrassa - Les Fonts)`
- Motivació: textarea — "Què t'impulsa a fer aquesta romeria?"
- Botó "Registrar-me i començar!"

**Formulari de login**:
- E-mail o telèfon
- Contrasenya
- Botó "Entrar"

**Disseny**: Fons degradat fosc amb el logo Spai-T centrat a dalt, targeta blanca centrada, colors corporatius.

---

## `cartilla.php` — App principal participant

**Layout**: Navbar Spai-T + dues seccions principals

### Secció 1 — Mapa Leaflet (meitat superior pantalla)
- Mapa OpenStreetMap amb Leaflet.js
- Marcador blau parpellejant = posició actual GPS de l'usuari
- Marcadors per cada parada:
  - ✅ Verd = parada completada
  - 🔵 Blau = propera parada (la següent a completar)
  - ⚪ Gris = parada pendent
  - 🏆 Especial = Montserrat final
- Línia de ruta dibuixada entre els punts
- Popup a cada marcador amb nom + estat
- **Distància en línia recta** fins a la propera parada (calculada en JS amb fórmula Haversine)
- **Temps estimat** (distància / 4.5 km/h)
- Si l'usuari és a menys de **200 metres** del punt → botó "Fer check-in aquí!" s'activa

### Secció 2 — La Cartilla (meitat inferior)
- Capçalera amb logo Spai-T + nom del participant + ruta
- Graella de parades com a "segells":
  - Cada parada = quadre amb icona de muntanya
  - Completada → fons verd + hora de pas
  - Pendent → fons gris clar
- Barra de progrés global (% del camí completat)
- Botó "Descarregar Cartilla PDF" (visible quan s'ha completat com a mínim 1 parada)

### Modal de Check-in (apareix en clicar "Fer check-in")
1. Camp per introduir el **codi secret** de la parada
2. Si el codi és correcte → apareix el **test de 3 preguntes** de la parada
3. Confirmar → segell afegit a la cartilla

### Tests per parada (3 preguntes cadascuna, respostes ràpides)

```
Parada 1 — Sant Cugat:
  P1: Com et trobes físicament? [Genial / Bé / Regular / Cansat/da]
  P2: Com has trobat el camí fins aquí? [Fàcil / Moderat / Dur]
  P3: Una paraula per descriure com et sents ara (text lliure)

Parada 2 — Can Barata:
  P1: Com va l'energia? [Al 100% / Bé / Necessito descans]
  P2: T'has perdut en algun moment? [No / Una mica / Sí jaja]
  P3: Quin ha estat el millor moment fins ara? (text lliure)

Parada 3 — Les Fonts (confluència rutes):
  P1: Com t'has sentit incorporant-te / rebent la gent de Terrassa? [Alegre / Emocionat/da / Normal]
  P2: Estàs gaudint del paisatge? [Molt / Bastant / Estic massa cansat/da per mirar]
  P3: Dedica una paraula a algú que porta al cor avui (text lliure)

Parada 4 — Quatre Vents:
  P1: Portem la meitat del camí. Com et sents? [Fort/a / Bé / Aguantant / Dur]
  P2: Has menjat i begut prou? [Sí perfecte / Podria menjar més / He oblidat beure]
  P3: Anècdota del dia fins ara (text lliure)

Parada 5 — Can Cabassa:
  P1: Les cames com les tens? [Com nous / Bé / Una mica carregades / Pesades]
  P2: L'ambient del grup, com és? [Increïble / Molt bo / Bé / Silenciós jaja]
  P3: Missatge per als que venen darrere (text lliure)

Parada 6 — Oasi:
  P1: Ja es veu Montserrat! Quina emoció sents? [Emoció pura / Alegria / Alivio / Incredul/a]
  P2: Si poguessis tornar enrere, faries la caminada? [100% sí / Probablement sí / Preguntem quan arribem]
  P3: Quina és la teva motivació per acabar? (text lliure)

Parada 7 — Olesa:
  P1: Última parada urbana. Com et trobes? [Estic volant / Bé / Cansad/a però content/a / Molt cansat/a]
  P2: Has après alguna cosa avui? [Sí, molt / Una mica / Estic massa cansat per pensar]
  P3: Dedica una frase a la muntanya que ja veus (text lliure)

Parada 8 — Aeri:
  P1: Ja som a la falda de Montserrat! Quin sentiment tens? [Sagrat / Emocionat/da / Orgullós/a / Tot alhora]
  P2: Com tens els peus? [Perfectes / Alguna ampolla / Molts embenats]
  P3: Per a qui fas aquesta pujada final? (text lliure)

Parada 9 — Monistrol:
  P1: Últim tram! Quina velocitat duus? [Esprint final! / Ritme constant / A poc a poc però segur/a]
  P2: Com descriuries avui en una paraula? [Paraula lliure]
  P3: Quin consell donaries a algú que volgués fer-ho l'any que ve? (text lliure)

Parada 10 — MONTSERRAT FINAL:
  P1: HO HEM ACONSEGUIT! Quin sentiment predomina? [Alegria / Emoció / Orgull / Pau interior / Tot alhora 🎉]
  P2: Repetiràs l'any que ve? [Sí sense dubte! / Crec que sí / Pregunta'm d'aquí uns dies]
  P3: Un missatge per guardar per sempre (text lliure) ← aquest apareix a la cartilla PDF
```

---

## `download_pdf.php` — Cartilla PDF

Genera un PDF descàrregable amb:

**Pàgina 1 — La Cartilla**
- Capçalera: Logo Spai-T (esquerra) + "Caminada a Montserrat 2026" (centre) + data (dreta)
- Nom del pelegrí en gran
- Ruta escollida (Llarga / Curta)
- Motivació escrita pel participant en cursiva entre cometes
- Graella 2x5 de segells (un per parada):
  - Completada: requadre amb ✅ + nom parada + hora pas
  - No completada: requadre gris buit
- Barra de progrés visual (ex: ██████████░░ 9/10)
- Peu de pàgina: Logo + eslògan "Som d'esplai, res no ens atura!" + web esplaispait.com

**Pàgina 2 — El Camí Interior** (si ha fet algun test)
- Títol: "El teu camí interior"
- Per cada parada completada amb test:
  - Nom parada + hora
  - Les respostes al test (especialment el text lliure)
- Missatge final (resposta de la parada 10 si ha arribat)

---

## `admin/dashboard.php` — Tauler Admin

**Navbar**: Logo Spai-T + "Panel Admin" + botó Logout

**Targetes de resum** (Bootstrap cards en fila):
- 👥 Total participants inscrits
- 🟢 Participants ruta llarga
- 🔵 Participants ruta curta  
- 🏆 Han arribat a Montserrat
- 📍 Participants en ruta (han fet ≥1 checkin però no han acabat)

**Taula per parades** — Quants participants han passat per cada punt:
```
Parada          | Participants | % del total
Inici Mundet    |     45       |   100%
Sant Cugat      |     44       |    97%
Can Barata      |     43       |    95%
...
Montserrat      |     38       |    84%
```

**Accés ràpid**: Botó "Veure tots els participants" + Botó "Exportar CSV"

---

## `admin/participants.php` — Llista participants

**Barra de cerca**: per nom, email o telèfon (filtratge en JS, sense recàrrega)

**Taula Bootstrap** amb columnes:
- Nom
- E-mail / Telèfon
- Ruta (badge verd=llarga / blau=curta)
- Progrés (barra Bootstrap progress)
- Última parada
- Data registre
- Accions: [Veure] [Reset PW]

**Ordenació** per les columnes principals.

---

## `admin/participant_detail.php` — Detall participant

**Dades personals** + motivació en caixa destacada

**Historial de check-ins**: llista cronològica amb hora i nom de parada

**Respostes als tests**: accordion Bootstrap, una secció per parada

**Zona de seguretat** (fons groc clar):
- Contrasenya actual (desencriptada i mostrada) 
- Botó "Generar nova contrasenya" → genera una de 8 caràcters aleatòria, la guarda i la mostra en gran perquè l'admin pugui comunicar-la

---

## `admin/export_csv.php` — Exportació CSV

Genera un fitxer `.csv` descàrregable amb:
```
id, nom, email, telefon, ruta, data_registre, motivacio,
parades_completades, ha_acabat, hora_inici, hora_final,
test_p1_parada1, test_p2_parada1, test_p3_parada1,
... (una columna per cada resposta de test)
```

---

## Consideracions tècniques importants

### Seguretat
- Tota la carpeta `/data/` protegida amb `.htaccess` (`Deny from all`)
- Validació i sanitització de tots els inputs amb `htmlspecialchars()` i `filter_var()`
- Sessions PHP amb `session_regenerate_id()` al login
- L'admin té una sessió separada de la del participant

### GPS i Leaflet
- Demanar permís geolocalització a `cartilla.php` amb `navigator.geolocation.watchPosition()`
- Fórmula Haversine en JS per calcular distància usuari → propera parada
- Si no concedeix GPS → mostrar mapa igualment però sense posició actual
- Activar botó check-in quan distància < 200 metres (o permetre sempre amb avís)

### Responsive
- L'app ha de funcionar perfectament en **mòbil** (és una app per usar caminant)
- Mapa a pantalla completa en mòbil amb cartilla col·lapsable a sota
- Botons grans i fàcils de tocar

### Sense base de dades
- Cada usuari = 1 fitxer JSON a `/data/users/{uuid}.json`
- `get_all_users()` fa glob dels fitxers JSON
- Per evitar race conditions en escriure → usar `file_put_contents()` amb flag `LOCK_EX`

### Gestió d'errors
- Si `/data/users/` no existeix → crear-la automàticament
- Si un JSON està corrupte → ignorar-lo i continuar
- Missatges d'error en català, amables i amb el branding Spai-T

---

## To i veu de l'app

- Llengua: **Català** (tota la interfície)
- Tó: proper, alegre, d'esplai — res formal
- Missatges d'ànim a cada parada: "Endavant! Ja queda menys 💪"
- Missatge final a Montserrat: "HO HAS ACONSEGUIT! Som d'esplai, res no ens atura! 🏔️🎉"

---

## Ordre de generació recomanat

1. `includes/config.php`
2. `includes/crypto.php`
3. `includes/user.php`
4. `includes/auth.php`
5. `assets/css/spait.css`
6. `index.php`
7. `cartilla.php`
8. `checkin.php`
9. `download_pdf.php`
10. `admin/index.php`
11. `admin/dashboard.php`
12. `admin/participants.php`
13. `admin/participant_detail.php`
14. `admin/export_csv.php`
15. `data/.htaccess`
