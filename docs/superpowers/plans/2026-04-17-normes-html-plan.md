# Normes 2026 HTML Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Exposar les normes de la caminada en una pàgina HTML (`normes.php`) i enllaçar-hi des de la cartilla, login i modal d'inici.

**Architecture:** Contingut textual extret del PDF es defineix en un array PHP propi i es pinta en una pàgina pública amb estil sorra/muntanya. Es reutilitzen `config.php` i Bootstrap, afegint noves classes a `assets/css/spait.css`. Botons a navbar, login i modal apunten a la nova pàgina.

**Tech Stack:** PHP 8 + Bootstrap 5 + CSS personalitzat + fonts Google (Nunito/Open Sans).

---

### Task 1: Estructura de dades de les normes

**Files:**
- Create: `includes/normes_data.php`

- [ ] **Step 1: Crear fitxer de dades**

`includes/normes_data.php`
```php
<?php
return [
    'etapes' => [
        ['tram' => 'Barcelona ➜ Sant Cugat', 'notes' => 'Sopem. Pot variar si Collserola està tancada.', 'ruta' => 'llarga'],
        ['tram' => 'Sant Cugat ➜ Can Barata', 'notes' => '', 'ruta' => 'llarga'],
        ['tram' => 'Can Barata ➜ Les Fonts', 'notes' => 'Ens unim a la ruta curta.', 'ruta' => 'ambdues'],
        ['tram' => 'Les Fonts ➜ Quatre Vents', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Quatre Vents ➜ Can Cabassa', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Can Cabassa ➜ Oasi', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Oasi ➜ Olesa (esmorzar)', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Olesa ➜ Aeri', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Aeri ➜ Monistrol', 'notes' => '', 'ruta' => 'ambdues'],
        ['tram' => 'Monistrol ➜ Monestir', 'notes' => 'Pendent de concretar', 'ruta' => 'ambdues'],
    ],
    'materials' => [
        'caminar' => [
            'Motxilla petita còmode',
            'Cantimplora petita',
            'Botes de muntanya',
            'Mitjons d’esport + recanvi',
            'Capelina',
            'Roba d’abric capa a capa',
            'Ulleres de sol i crema solar',
            'Got reutilitzable amb nom',
            'Buff i guants',
            'Cacao labial + cremes calor/vaselina',
            'Esmorzar i sopar',
            'Frontal + piles',
            'Pals i barret (optatiu)',
            'Targeta 3 zones (ruta curta)',
        ],
        'pernocta' => [
            'Sac de dormir i coixí',
            'Pijama + sabatilles',
            'Tovallola i necesser',
            'Mudes dissabte i diumenge',
            'Calçat còmode',
            'Dinar dissabte',
            'Motxilla extra a l’esplai divendres',
        ],
    ],
    'consells' => [
        'Fes esport lleuger les setmanes prèvies.',
        'No et tallis les ungles el dia abans.',
        'Porta sabates ja utilitzades i en bon estat.',
        'Dina carbohidrats i sopa lleuger però contundent.',
        'Assegura piles del frontal i descansa bé.',
        'Evita carregar massa pes.',
        'No oblidis caputxa per pluja i vent.',
    ],
    'normes' => [
        'generals' => [
            'Caminem en grup, sense adelantar el primer moni.',
            'Ningú queda enrere del/la monitor/a escombra.',
            'El ritme el marca el monitor de capçalera.',
            'Si cal parar, avisa immediatament a un moni.',
            'Fem cas als cotxes escombra en cada parada.',
        ],
        'tritons' => [
            'Recorda que anem amb menors.',
            'No fumar mentre estiguem amb el grup.',
            'Donem exemple en tot moment.',
        ],
    ],
    'termos' => 'Necessitem termos amb llet, cafè o caldo per divendres a la tarda. Si pots aportar o omplir-ne, contacta amb la comissió.',
    'preguntes' => 'Tens dubtes? Escriu al contacte oficial que apareix al formulari o parla amb el teu moni de referència.',
];
```

- [ ] **Step 2: Validar fitxer**

Execute: `php -l includes/normes_data.php`
Expected: `No syntax errors detected in includes/normes_data.php`

### Task 2: Crear pàgina pública normes.php

**Files:**
- Create: `normes.php`

- [ ] **Step 1: Base HTML i hero**

`normes.php`
```php
<?php
require_once __DIR__ . '/includes/config.php';
$settings = get_settings();
$normes = require __DIR__ . '/includes/normes_data.php';
$event = $settings['event'] ?? [];
$logo = $settings['visual']['logo_local'] ?: ($settings['visual']['logo_url'] ?? '');
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Normes — <?= htmlspecialchars($event['nom'] ?? 'Caminada 2026') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/spait.css">
</head>
<body class="normes-body">
  <header class="normes-hero text-center text-white">
    <div class="container py-5">
      <?php if ($logo): ?><img src="<?= htmlspecialchars($logo) ?>" alt="Logo Spai-T" class="normes-logo mb-3"><?php endif; ?>
      <p class="normes-subtitle">Reunió Ruta 2026</p>
      <h1 class="display-3 fw-bold">Normes de la Caminada</h1>
      <p class="lead">Tot el que necessites abans de posar-te les botes.</p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="#etapes" class="btn btn-spait">Com començar</a>
        <a href="docs/Ruta 2026.pdf" class="btn btn-outline-light" target="_blank" rel="noopener">Descarrega PDF</a>
      </div>
    </div>
  </header>
```

- [ ] **Step 2: Seccions dinàmiques**

Continuar el fitxer amb:
```php
  <main class="normes-content container py-5">
    <section id="etapes" class="normes-section">
      <span class="normes-tag">01</span>
      <h2 class="mb-3">Etapes de la ruta</h2>
      <ol class="normes-timeline">
        <?php foreach ($normes['etapes'] as $i => $etapa): ?>
          <li>
            <div class="badge normes-ruta-<?= $etapa['ruta'] ?>">
              <?= $etapa['ruta'] === 'llarga' ? 'Ruta llarga' : ($etapa['ruta'] === 'curta' ? 'Ruta curta' : 'Ambdues') ?>
            </div>
            <div>
              <strong><?= htmlspecialchars(($i + 1) . '. ' . $etapa['tram']) ?></strong>
              <?php if ($etapa['notes']): ?>
                <p class="text-muted small mb-0"><?= htmlspecialchars($etapa['notes']) ?></p>
              <?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>

    <section class="normes-section">
      <span class="normes-tag">02</span>
      <h2 class="mb-3">Què necessitem?</h2>
      <div class="normes-grid two-cols">
        <div class="normes-card">
          <h3><i class="bi bi-backpack2 me-2"></i>Per caminar</h3>
          <ul class="normes-list">
            <?php foreach ($normes['materials']['caminar'] as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="normes-card">
          <h3><i class="bi bi-moon-stars me-2"></i>Si dormim fora</h3>
          <ul class="normes-list">
            <?php foreach ($normes['materials']['pernocta'] as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <!-- Consells, Normes i Termos -->
  </main>

  <footer class="normes-footer text-center py-4">
    <p class="mb-2">Som d'esplai, res no ens atura!</p>
    <div class="d-flex justify-content-center gap-3">
      <a href="cartilla.php" class="btn btn-link text-warning">Torna a la cartilla</a>
      <a href="index.php" class="btn btn-link text-warning">Inicia sessió</a>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```
Completar seccions restants (consells → targetes, normes → dues columnes, termos/preguntes → CTA) amb HTML + dades.

- [ ] **Step 3: Validar**

Run: `php -l normes.php`
Expected: `No syntax errors detected in normes.php`

### Task 3: Afegir estils específics

**Files:**
- Modify: `assets/css/spait.css`

- [ ] **Step 1: Afegir classes**

Append near end:
```css
.normes-body { background: radial-gradient(circle at top,#f8efe6,#f4d8bf); font-family:'Open Sans',sans-serif; color:#2C3E50; }
.normes-hero { background: linear-gradient(145deg,#C0392B 0%,#27AE60 100%); min-height:70vh; position:relative; overflow:hidden; }
.normes-hero::before { content:""; position:absolute; inset:0; background:url('../img/normes-muntanyes.svg') center/cover; opacity:0.35; }
.normes-hero .normes-subtitle { letter-spacing:0.3em; text-transform:uppercase; color:#F1C40F; }
.normes-content { max-width:960px; }
.normes-section { background:rgba(255,255,255,0.88); border-radius:24px; padding:2rem; margin-bottom:2rem; box-shadow:0 20px 60px rgba(44,62,80,0.15); backdrop-filter:blur(10px); }
.normes-tag { font-weight:700; letter-spacing:.2em; color:#C0392B; display:block; margin-bottom:0.5rem; }
.normes-timeline { list-style:none; padding-left:0; position:relative; }
.normes-timeline::before { content:""; position:absolute; left:12px; top:0; bottom:0; width:2px; background:#F1C40F; }
.normes-timeline li { display:flex; gap:1rem; padding:1rem 0 1rem 2.5rem; position:relative; }
.normes-timeline li::before { content:""; position:absolute; left:4px; top:18px; width:16px; height:16px; border-radius:50%; background:#fff; border:3px solid #C0392B; }
.normes-ruta-llarga { background:#C0392B; }
.normes-ruta-curta { background:#27AE60; }
.normes-ruta-ambdues { background:#F1C40F; color:#2C3E50; }
.normes-grid { display:grid; gap:1rem; }
.normes-grid.two-cols { grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); }
.normes-card { background:rgba(255,255,255,0.92); border-radius:16px; padding:1rem 1.5rem; box-shadow:0 10px 30px rgba(44,62,80,0.1); }
.normes-list { padding-left:1rem; margin-bottom:0; }
.normes-consells { display:grid; gap:1rem; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); }
.normes-consell { background:#fff; border-radius:14px; padding:1rem; border-left:4px solid #27AE60; }
.normes-footer { background:#2C3E50; color:white; }
.normes-footer a { color:#F1C40F; font-weight:600; }
```

- [ ] **Step 2: Afegir SVG**

Guardar `assets/img/normes-muntanyes.svg` (simple gradient) si cal; sinó, canviar `background` per un gradient sense imatge.

### Task 4: Afegir accessos (navbar, modal, login)

**Files:**
- Modify: `cartilla.php`
- Modify: `index.php`

- [ ] **Step 1: Botó navbar cartilla**

Al bloc de botons (després del PDF):
```php
<a href="normes.php" class="btn btn-sm btn-outline-light" title="Normes de la ruta">
  <i class="bi bi-journal-text"></i><span class="d-none d-sm-inline ms-1">Normes</span>
</a>
```

- [ ] **Step 2: Enllaç modal d'inici**

Al cos del modal d’inici (on es mostra el compte enrere):
```php
<div class="alert alert-warning text-center mt-3">
  Encara no pots començar. Mentrestant, llegeix les <a href="normes.php" target="_blank" class="fw-bold">normes de la ruta</a>.
</div>
```

- [ ] **Step 3: Botó login**

`index.php` (sota el formulari):
```html
<a class="btn btn-outline-secondary w-100 mt-3" href="normes.php" target="_blank">
  <i class="bi bi-journal-text me-1"></i>Consulta les normes de la ruta
</a>
```

### Task 5: Verificacions i commit

- [ ] **Step 1: Proves locals**

```bash
php -S localhost:8080 -t .
```
1. Obrir `http://localhost:8080/normes.php` i comprovar que totes les seccions carreguen i són responsives.
2. Obrir `cartilla.php` i verificar nou botó + modal.
3. Obrir `index.php` i confirmar el botó.

- [ ] **Step 2: Accessibilitat ràpida**

Fer Tab per comprovar focus i que els botons tenen text descriptiu.

- [ ] **Step 3: Commit**

```bash
git add includes/normes_data.php normes.php assets/css/spait.css cartilla.php index.php assets/img/normes-muntanyes.svg
git commit -m "✨ Pàgina HTML de normes i accessos"
```
