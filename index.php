<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: cartilla.php');
    exit;
}

$settings       = get_settings();
$registre_obert = $settings['event']['registre_obert'] ?? true;

$error   = '';
$success = '';
$tab     = $registre_obert ? 'registre' : 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accio = $_POST['accio'] ?? '';

    if ($accio === 'registre') {
        if (!$registre_obert) {
            $error = 'El registre està tancat.';
            $tab   = 'login';
        } else {
        $tab = 'registre';
        $nom      = trim($_POST['nom'] ?? '');
        $email    = strtolower(trim($_POST['email'] ?? ''));
        $telefon  = trim($_POST['telefon'] ?? '');
        $password = $_POST['password'] ?? '';
        $ruta     = in_array($_POST['ruta'] ?? '', ['llarga', 'curta']) ? $_POST['ruta'] : 'curta';
        $motivacio = trim($_POST['motivacio'] ?? '');

        if (empty($nom)) {
            $error = 'El nom complet és obligatori.';
        } elseif (empty($email) && empty($telefon)) {
            $error = 'Cal indicar almenys el correu electrònic o el telèfon.';
        } elseif (strlen($password) < 6) {
            $error = 'La contrasenya ha de tenir com a mínim 6 caràcters.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'El format del correu electrònic no és vàlid.';
        } else {
            $identifier = $email ?: $telefon;
            $existing = get_user_by_email($identifier);
            if ($existing) {
                $error = 'Ja existeix un compte amb aquest correu o telèfon.';
            } else {
                $user = create_user([
                    'nom'       => $nom,
                    'email'     => $email,
                    'telefon'   => $telefon,
                    'password'  => $password,
                    'ruta'      => $ruta,
                    'motivacio' => $motivacio,
                ]);
                login_user($identifier, $password);
                header('Location: cartilla.php');
                exit;
            }
        }
        } // end else (registre_obert)
    } elseif ($accio === 'login') {
        $tab = 'login';
        $identifier = strtolower(trim($_POST['identifier'] ?? ''));
        $password   = $_POST['password'] ?? '';
        if (empty($identifier) || empty($password)) {
            $error = 'Omple tots els camps per entrar.';
        } elseif (!login_user($identifier, $password)) {
            $error = 'Correu/telèfon o contrasenya incorrectes.';
        } else {
            header('Location: cartilla.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Caminada Montserrat 2026 — Esplai splaiT</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/spait.css">
</head>
<body>
<div class="login-bg">
  <div class="login-card card">

    <div class="login-header">
      <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
           alt="splaiT" class="login-logo mb-2">
      <h1><i class="bi bi-mountains"></i> Caminada a Montserrat 2026</h1>
      <p class="slogan mb-0">Som d'esplai, res no ens atura!</p>
    </div>

    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger alert-spait" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <!-- Pestanyes -->
      <ul class="nav nav-pills nav-pills-spait mb-4 justify-content-center" id="loginTabs" role="tablist">
        <?php if ($registre_obert): ?>
        <li class="nav-item" role="presentation">
          <button class="nav-link <?= $tab === 'registre' ? 'active' : '' ?>"
                  id="tab-registre" data-bs-toggle="pill" data-bs-target="#pane-registre"
                  type="button" role="tab">
            <i class="bi bi-person-plus me-1"></i>Registre
          </button>
        </li>
        <?php endif; ?>
        <li class="nav-item <?= $registre_obert ? 'ms-2' : '' ?>" role="presentation">
          <button class="nav-link <?= $tab === 'login' ? 'active' : '' ?>"
                  id="tab-login" data-bs-toggle="pill" data-bs-target="#pane-login"
                  type="button" role="tab">
            <i class="bi bi-box-arrow-in-right me-1"></i>Ja tinc compte
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- REGISTRE -->
        <div class="tab-pane fade <?= $tab === 'registre' ? 'show active' : '' ?>" id="pane-registre" role="tabpanel">
          <?php if (!$registre_obert): ?>
          <div class="alert alert-warning text-center mt-3">
            <h6><i class="bi bi-lock me-1"></i>El registre està tancat</h6>
            <p class="mb-0 small">
              Els organitzadors gestionen els participants directament.<br>
              Si necessites accés, contacta amb els organitzadors.
            </p>
            <?php if (!empty($settings['event']['contacte'])): ?>
              <a href="tel:<?= htmlspecialchars($settings['event']['contacte']) ?>"
                 class="btn btn-sm btn-primary mt-2">
                <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($settings['event']['contacte']) ?>
              </a>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <form method="POST" action="index.php" novalidate>
            <input type="hidden" name="accio" value="registre">

            <div class="mb-3">
              <label for="nom" class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-lg" id="nom" name="nom"
                     placeholder="Maria Garcia" required
                     value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-sm-6">
                <label for="email" class="form-label fw-semibold">Correu electrònic</label>
                <input type="email" class="form-control" id="email" name="email"
                       placeholder="maria@example.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
              </div>
              <div class="col-12 col-sm-6">
                <label for="telefon" class="form-label fw-semibold">Telèfon</label>
                <input type="tel" class="form-control" id="telefon" name="telefon"
                       placeholder="612345678"
                       value="<?= htmlspecialchars($_POST['telefon'] ?? '') ?>">
              </div>
            </div>
            <p class="text-muted small mt-n2 mb-3">Cal omplir com a mínim un dels dos camps.</p>

            <div class="mb-3">
              <label for="password-reg" class="form-label fw-semibold">Contrasenya <span class="text-danger">*</span></label>
              <input type="password" class="form-control" id="password-reg" name="password"
                     placeholder="Mínim 6 caràcters" required minlength="6">
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Ruta <span class="text-danger">*</span></label>
              <div class="d-flex gap-3">
                <div class="form-check flex-fill">
                  <input class="form-check-input" type="radio" name="ruta" id="ruta-llarga"
                         value="llarga" <?= (($_POST['ruta'] ?? '') === 'llarga') ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ruta-llarga">
                    <strong>Llarga</strong><br>
                    <small class="text-muted">Barcelona — Mundet</small>
                  </label>
                </div>
                <div class="form-check flex-fill">
                  <input class="form-check-input" type="radio" name="ruta" id="ruta-curta"
                         value="curta" <?= (($_POST['ruta'] ?? 'curta') !== 'llarga') ? 'checked' : '' ?>>
                  <label class="form-check-label" for="ruta-curta">
                    <strong>Curta</strong><br>
                    <small class="text-muted">Terrassa — Les Fonts</small>
                  </label>
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label for="motivacio" class="form-label fw-semibold">
                <i class="bi bi-heart me-1 text-danger"></i>Què t'impulsa a fer aquesta romeria?
              </label>
              <textarea class="form-control" id="motivacio" name="motivacio" rows="3"
                        placeholder="Explica'ns la teva motivació personal..."><?= htmlspecialchars($_POST['motivacio'] ?? '') ?></textarea>
            </div>

            <div class="alert alert-info small mt-2 mb-3">
              <i class="bi bi-shield-check me-1"></i>
              <strong>Sobre la teva ubicació:</strong>
              Durant la caminada, l'última posició coneguda del teu dispositiu és
              visible pels organitzadors per motius de seguretat, especialment
              si hi ha participants menors d'edat. Pots pausar les actualitzacions
              en temps real des de la cartilla, però l'última posició sempre
              quedarà guardada.
            </div>

            <button type="submit" class="btn btn-spait w-100 btn-lg">
              <i class="bi bi-person-check me-2"></i>Registrar-me i començar!
            </button>
          </form>
          <?php endif; ?>
        </div>

        <!-- LOGIN -->
        <div class="tab-pane fade <?= $tab === 'login' ? 'show active' : '' ?>" id="pane-login" role="tabpanel">
          <form method="POST" action="index.php">
            <input type="hidden" name="accio" value="login">

            <div class="mb-3">
              <label for="identifier" class="form-label fw-semibold">Correu electrònic o telèfon</label>
              <input type="text" class="form-control form-control-lg" id="identifier" name="identifier"
                     placeholder="maria@example.com o 612345678" required
                     value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>">
            </div>

            <div class="mb-4">
              <label for="password-login" class="form-label fw-semibold">Contrasenya</label>
              <input type="password" class="form-control form-control-lg" id="password-login" name="password"
                     placeholder="La teva contrasenya" required>
            </div>

            <button type="submit" class="btn btn-spait w-100 btn-lg">
              <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
            </button>
          </form>

          <?php if ($registre_obert): ?>
          <div class="text-center mt-3">
            <small class="text-muted">No tens compte?
              <a href="#" onclick="document.getElementById('tab-registre').click(); return false;">
                Registra't ara
              </a>
            </small>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-spait">
      <a href="https://www.iespai.com" target="_blank">iespai.com</a> &bull;
      <a href="https://esplaispait.com" target="_blank">esplaispait.com</a> &bull;
      Caminada Montserrat 2026
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
