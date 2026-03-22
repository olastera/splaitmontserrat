<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');



$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: participants.php');
    exit;
}

$user = get_user($id);
if (!$user) {
    header('Location: participants.php');
    exit;
}

$nova_pw = null;
$msg_ok  = '';
$msg_err = '';

// Reset contrasenya
if (isset($_GET['reset']) || isset($_POST['reset_pw'])) {
    $nova_pw = reset_password($id);
    $user    = get_user($id);
    $msg_ok  = 'Nova contrasenya generada.';
}

// Check-in manual des de l'admin
if (isset($_POST['admin_checkin'])) {
    $parada_id = intval($_POST['parada_id'] ?? -1);
    if ($parada_id >= 0 && !has_checkin($id, $parada_id)) {
        add_checkin($id, $parada_id, []);
        $user   = get_user($id);
        $msg_ok = 'Check-in afegit correctament.';
    } else {
        $msg_err = 'Parada no vàlida o ja té check-in.';
    }
}

// Eliminar check-in
if (isset($_POST['admin_remove_checkin'])) {
    $parada_id = intval($_POST['parada_id'] ?? -1);
    $user['checkins'] = array_values(array_filter(
        $user['checkins'] ?? [],
        fn($c) => $c['parada_id'] !== $parada_id
    ));
    save_user($user);
    $user   = get_user($id);
    $msg_ok = 'Check-in eliminat.';
}

$parades_map = [];
foreach ($PARADES as $p) { $parades_map[$p['id']] = $p; }

$ruta = $user['ruta'] ?? 'curta';
$prog = get_user_progress($user, $PARADES);
$pw_plain = null; // no es mostra la contrasenya actual
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($user['nom']) ?> — Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
</head>
<body>

<nav class="navbar navbar-spait px-3 py-2">
  <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
    <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
         height="32" alt="splaiT">
    <span>Panel Admin 2026</span>
  </a>
  <div class="ms-auto d-flex gap-2">
    <a href="participants.php" class="btn btn-sm btn-outline-light">
      <i class="bi bi-arrow-left me-1"></i>Participants
    </a>
    <a href="logout.php" class="btn btn-sm btn-outline-danger">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</nav>

<div class="container py-4">

  <?php if ($msg_ok): ?>
  <div class="alert alert-success alert-spait mb-3">
    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($msg_ok) ?>
  </div>
  <?php endif; ?>
  <?php if ($msg_err): ?>
  <div class="alert alert-danger mb-3">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($msg_err) ?>
  </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Col esquerra: dades -->
    <div class="col-12 col-lg-4">

      <!-- Perfil -->
      <div class="card shadow-sm mb-4">
        <div class="card-header" style="background:#2C3E50;color:white">
          <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Perfil del participant</h5>
        </div>
        <div class="card-body">
          <h4><?= htmlspecialchars($user['nom']) ?></h4>
          <?php if (!empty($user['email'])): ?>
            <p class="mb-1"><i class="bi bi-envelope me-2 text-muted"></i><?= htmlspecialchars($user['email']) ?></p>
          <?php endif; ?>
          <?php if (!empty($user['telefon'])): ?>
            <p class="mb-1"><i class="bi bi-phone me-2 text-muted"></i><?= htmlspecialchars($user['telefon']) ?></p>
          <?php endif; ?>
          <p class="mb-1">
            <i class="bi bi-signpost-2 me-2 text-muted"></i>
            Ruta: <strong><?= $ruta === 'llarga' ? 'Llarga (Barcelona)' : 'Curta (Terrassa)' ?></strong>
          </p>
          <p class="mb-0">
            <i class="bi bi-calendar me-2 text-muted"></i>
            Registre: <strong><?= $user['created_at'] ? date('d/m/Y H:i', strtotime($user['created_at'])) : '—' ?></strong>
          </p>
        </div>
      </div>

      <!-- Motivació -->
      <?php if (!empty($user['motivacio'])): ?>
      <div class="motivacio-card card p-3 mb-4 shadow-sm">
        <p class="mb-1 fw-bold"><i class="bi bi-heart-fill text-danger me-2"></i>Motivació:</p>
        <p class="mb-0 fst-italic">"<?= htmlspecialchars($user['motivacio']) ?>"</p>
      </div>
      <?php endif; ?>

      <!-- Progrés -->
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h6 class="fw-bold mb-2">Progrés</h6>
          <div class="progress progress-spait mb-2" style="height:14px;">
            <div class="progress-bar" style="width:<?= $prog['percent'] ?>%">
              <?php if ($prog['percent'] > 20): ?><?= $prog['percent'] ?>%<?php endif; ?>
            </div>
          </div>
          <p class="mb-0 text-muted small">
            <?= $prog['completades'] ?>/<?= $prog['total'] ?> parades completades
            <?php if ($prog['acabat']): ?>
              &bull; <span class="text-warning fw-bold">🏆 Ha arribat!</span>
            <?php endif; ?>
          </p>
        </div>
      </div>

      <!-- Zona seguretat -->
      <div class="card shadow-sm border-warning mb-4">
        <div class="card-header bg-warning text-dark">
          <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Zona de seguretat</h6>
        </div>
        <div class="card-body" style="background:#fffbea">
          <?php if ($nova_pw): ?>
          <div class="alert alert-success p-2 small">
            <i class="bi bi-key me-1"></i>Nova contrasenya generada:
            <strong class="fs-5 ms-2"><?= htmlspecialchars($nova_pw) ?></strong>
          </div>
          <?php endif; ?>
          <form method="POST" action="participant_detail.php?id=<?= urlencode($id) ?>">
            <input type="hidden" name="reset_pw" value="1">
            <button type="submit" class="btn btn-warning w-100"
                    onclick="return confirm('Generar nova contrasenya aleatòria?')">
              <i class="bi bi-key-fill me-2"></i>Generar nova contrasenya
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Col dreta: historial + tests -->
    <div class="col-12 col-lg-8">

      <!-- Historial check-ins -->
      <div class="card shadow-sm mb-4">
        <div class="card-header" style="background:#2C3E50;color:white">
          <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Historial de check-ins</h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($user['checkins'])): ?>
            <div class="p-3 text-muted">Encara no ha fet cap check-in.</div>
          <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($user['checkins'] as $ci):
              $p = $parades_map[$ci['parada_id']] ?? null;
              $hora = $ci['timestamp'] ? date('d/m/Y H:i:s', strtotime($ci['timestamp'])) : '—';
              $es_final = !empty($p['final']);
            ?>
            <li class="list-group-item d-flex align-items-center gap-3">
              <span class="fs-4"><?= $es_final ? '🏆' : '✅' ?></span>
              <div>
                <strong><?= htmlspecialchars($p ? $p['nom'] : 'Parada ' . $ci['parada_id']) ?></strong><br>
                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= $hora ?></small>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>

      <!-- Check-in manual admin -->
      <?php
      $parades_ruta = array_values(array_filter($PARADES, function($p) use ($ruta) {
          return $p['ruta'] === 'ambdues' || $p['ruta'] === $ruta;
      }));
      $checkin_ids = array_column($user['checkins'] ?? [], 'parada_id');
      $pendents = array_filter($parades_ruta, fn($p) => !in_array($p['id'], $checkin_ids));
      $fetes    = array_filter($parades_ruta, fn($p) =>  in_array($p['id'], $checkin_ids));
      ?>
      <div class="card shadow-sm mb-4 border-primary">
        <div class="card-header" style="background:#1a6fc4;color:white">
          <h5 class="mb-0"><i class="bi bi-person-check me-2"></i>Check-in manual (admin)</h5>
        </div>
        <div class="card-body">

          <?php if (!empty($pendents)): ?>
          <p class="text-muted small mb-2">Parades pendents — fes clic per afegir el check-in:</p>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($pendents as $p): ?>
            <form method="POST" action="participant_detail.php?id=<?= urlencode($id) ?>"
                  onsubmit="return confirm('Afegir check-in a «<?= htmlspecialchars($p['nom'], ENT_QUOTES) ?>»?')">
              <input type="hidden" name="admin_checkin" value="1">
              <input type="hidden" name="parada_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i><?= htmlspecialchars($p['nom']) ?>
              </button>
            </form>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-success small mb-2"><i class="bi bi-trophy-fill me-1"></i>Ha completat totes les parades!</p>
          <?php endif; ?>

          <?php if (!empty($fetes)): ?>
          <p class="text-muted small mb-2">Parades fetes — fes clic per eliminar el check-in:</p>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($fetes as $p): ?>
            <form method="POST" action="participant_detail.php?id=<?= urlencode($id) ?>"
                  onsubmit="return confirm('Eliminar check-in de «<?= htmlspecialchars($p['nom'], ENT_QUOTES) ?>»?')">
              <input type="hidden" name="admin_remove_checkin" value="1">
              <input type="hidden" name="parada_id" value="<?= $p['id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i><?= htmlspecialchars($p['nom']) ?>
              </button>
            </form>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div>
      </div>

      <!-- Tests per parada -->
      <?php
      $checkins_amb_test = array_filter($user['checkins'] ?? [], fn($c) => !empty($c['test']));
      ?>
      <?php if (!empty($checkins_amb_test)): ?>
      <div class="card shadow-sm">
        <div class="card-header" style="background:#2C3E50;color:white">
          <h5 class="mb-0"><i class="bi bi-chat-square-text me-2"></i>Respostes als tests</h5>
        </div>
        <div class="card-body">
          <div class="accordion" id="accordionTests">
            <?php foreach ($checkins_amb_test as $idx => $ci):
              $pid = $ci['parada_id'];
              $p   = $parades_map[$pid] ?? null;
              $test_def = $TESTS[$pid] ?? [];
              $hora = $ci['timestamp'] ? date('H:i d/m/Y', strtotime($ci['timestamp'])) : '';
            ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?= $idx ?>">
                <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>">
                  ✅ <?= htmlspecialchars($p ? $p['nom'] : 'Parada ' . $pid) ?>
                  <small class="ms-2 text-muted"><?= $hora ?></small>
                </button>
              </h2>
              <div id="collapse<?= $idx ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                   data-bs-parent="#accordionTests">
                <div class="accordion-body">
                  <?php foreach ($ci['test'] as $key => $val):
                    $pregunta = $test_def[$key]['pregunta'] ?? $key;
                  ?>
                  <div class="mb-2">
                    <span class="fw-semibold text-muted small"><?= htmlspecialchars($pregunta) ?></span><br>
                    <span><?= htmlspecialchars($val ?: '—') ?></span>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
