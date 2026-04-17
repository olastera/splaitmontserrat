<?php
require_once __DIR__ . '/includes/config.php';

$settings = get_settings();
$normes = require __DIR__ . '/includes/normes_data.php';

$event = $settings['event'] ?? [];
$visual = $settings['visual'] ?? [];
$logo = trim($visual['logo_local'] ?? '') ?: trim($visual['logo_url'] ?? '');
$contacte = trim($event['contacte'] ?? '');
$appName = $visual['nom_app'] ?? 'Cartilla del Pelegrí';
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
    <div class="container py-3">
      <?php if ($logo): ?>
        <img src="<?= htmlspecialchars($logo) ?>" alt="Logo Esplai Spai-T" class="normes-logo mb-2" height="40">
      <?php endif; ?>
      <p class="normes-subtitle mb-1">Reunió Ruta 2026</p>
      <h1 class="h2 fw-bold mb-2">Normes de la Caminada</h1>
      <p class="small mb-0">Tot el que necessites abans de posar-te les botes.</p>
    </div>
  </header>

  <main class="normes-content container py-5">
    <?php if (!empty($normes['com_funciona'])): ?>
    <section id="com-funciona" class="normes-section">
      <span class="normes-tag">00</span>
      <h2 class="mb-3"><?= htmlspecialchars($normes['com_funciona']['titol']) ?></h2>
      <p class="mb-4"><?= htmlspecialchars($normes['com_funciona']['intro']) ?></p>
      <div class="normes-comfun">
        <?php foreach ($normes['com_funciona']['passos'] as $pas): ?>
          <div class="normes-comfun-item">
            <div class="normes-comfun-icon">
              <i class="<?= htmlspecialchars($pas['icona']) ?>"></i>
            </div>
            <div>
              <h4><?= htmlspecialchars($pas['titol']) ?></h4>
              <p class="mb-0 text-muted"><?= htmlspecialchars($pas['text']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <section id="etapes" class="normes-section">
      <span class="normes-tag">01</span>
      <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div>
          <h2 class="mb-1">Etapes de la ruta</h2>
          <p class="text-muted mb-0">Organitza't i coneix cada tram abans de sortir.</p>
        </div>
        <div class="small fw-semibold text-uppercase text-secondary">Ruta <?= htmlspecialchars($event['nom'] ?? 'Spai-T') ?></div>
      </div>
      <ol class="normes-timeline">
        <?php foreach ($normes['etapes'] as $index => $etapa): ?>
          <?php
            $badgeText = 'Ambdues';
            if ($etapa['ruta'] === 'llarga') {
                $badgeText = 'Ruta llarga';
            } elseif ($etapa['ruta'] === 'curta') {
                $badgeText = 'Ruta curta';
            }
          ?>
          <li>
            <div class="badge normes-ruta-<?= htmlspecialchars($etapa['ruta']) ?> text-uppercase"><?= $badgeText ?></div>
            <div>
              <strong><?= htmlspecialchars(($index + 1) . '. ' . $etapa['tram']) ?></strong>
              <?php if (!empty($etapa['notes'])): ?>
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
          <h3 class="h5">
            <i class="bi bi-backpack2 me-2" aria-hidden="true"></i>Per caminar
          </h3>
          <ul class="normes-list">
            <?php foreach ($normes['materials']['caminar'] as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="normes-card">
          <h3 class="h5">
            <i class="bi bi-moon-stars me-2" aria-hidden="true"></i>Si dormim fora
          </h3>
          <ul class="normes-list">
            <?php foreach ($normes['materials']['pernocta'] as $item): ?>
              <li><?= htmlspecialchars($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <section class="normes-section">
      <span class="normes-tag">04</span>
      <h2 class="mb-3">Consells ràpids</h2>
      <div class="normes-consells">
        <?php foreach ($normes['consells'] as $tip): ?>
          <article class="normes-consell">
            <i class="bi bi-lightning-charge-fill text-warning me-2" aria-hidden="true"></i>
            <span><?= htmlspecialchars($tip) ?></span>
          </article>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="normes-section">
      <span class="normes-tag">04</span>
      <h2 class="mb-3">Normes de convivència</h2>
      <div class="normes-grid two-cols">
        <div class="normes-card">
          <h3 class="h5 mb-3">
            <i class="bi bi-people-fill me-2" aria-hidden="true"></i>Generals
          </h3>
          <ul class="normes-list">
            <?php foreach ($normes['normes']['generals'] as $rule): ?>
              <li><?= htmlspecialchars($rule) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="normes-card">
          <h3 class="h5 mb-3">
            <i class="bi bi-stars me-2" aria-hidden="true"></i>Tritons
          </h3>
          <ul class="normes-list">
            <?php foreach ($normes['normes']['tritons'] as $rule): ?>
              <li><?= htmlspecialchars($rule) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>

    <section class="normes-section text-center">
      <span class="normes-tag">05</span>
      <h2 class="mb-3">Termos i preguntes</h2>
      <p class="lead mb-4">
        <?= htmlspecialchars($normes['termos']) ?>
      </p>
      <div class="normes-card mx-auto" style="max-width: 620px;">
        <p class="mb-3"><?= htmlspecialchars($normes['preguntes']) ?></p>
        <?php if ($contacte): ?>
          <p class="fw-semibold mb-4">
            Contacte oficial: <span class="text-primary"><?= htmlspecialchars($contacte) ?></span>
          </p>
        <?php endif; ?>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
          <a href="cartilla.php" class="btn btn-spait">
            <i class="bi bi-map me-2" aria-hidden="true"></i>Obre la cartilla
          </a>
          <a href="index.php" class="btn btn-outline-secondary">
            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i>Inicia sessió
          </a>
        </div>
      </div>
    </section>
  </main>

  <footer class="normes-footer text-center py-4">
    <div class="d-flex flex-wrap justify-content-center gap-3 mb-3">
      <a href="#com-funciona" class="btn btn-spait">
        <i class="bi bi-arrow-repeat me-1"></i>Començar de nou
      </a>
      <a href="Normes-Ruta-2026.pdf" class="btn btn-light text-dark" target="_blank" rel="noopener" download>
        <i class="bi bi-file-earmark-pdf me-2" aria-hidden="true"></i>Descarrega PDF
      </a>
    </div>
    <div class="d-flex justify-content-center gap-3">
      <a href="cartilla.php" class="btn btn-link text-warning">Torna a la cartilla</a>
      <a href="index.php" class="btn btn-link text-warning">Inicia sessió</a>
    </div>
    <p class="small text-muted mb-0">© <?= date('Y') ?> <?= htmlspecialchars($event['organitzacio'] ?? 'Esplai Spai-T') ?> · <?= htmlspecialchars($appName) ?></p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
