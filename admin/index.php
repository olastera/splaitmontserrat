<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['pass'] ?? '';
    if (login_admin($user, $pass)) {
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Usuari o contrasenya incorrectes.';
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin — Caminada Montserrat 2026</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="../assets/css/spait.css">
</head>
<body>
<div class="login-bg">
  <div class="login-card card" style="max-width:380px">
    <div class="login-header">
      <img src="https://esplaispait.com/wp-content/uploads/2024/11/cropped-cropped-cropped-logo_splait-removebg-preview-1.png"
           alt="splaiT" class="login-logo mb-2">
      <h1><i class="bi bi-shield-lock me-2"></i>Panel Admin</h1>
      <p class="slogan mb-0">Caminada Montserrat 2026</p>
    </div>
    <div class="card-body p-4">
      <?php if ($error): ?>
        <div class="alert alert-danger alert-spait">
          <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      <form method="POST">
        <div class="mb-3">
          <label for="user" class="form-label fw-semibold">Usuari admin</label>
          <input type="text" class="form-control form-control-lg" id="user" name="user" required
                 value="<?= htmlspecialchars($_POST['user'] ?? '') ?>">
        </div>
        <div class="mb-4">
          <label for="pass" class="form-label fw-semibold">Contrasenya</label>
          <input type="password" class="form-control form-control-lg" id="pass" name="pass" required>
        </div>
        <button type="submit" class="btn btn-spait w-100 btn-lg">
          <i class="bi bi-box-arrow-in-right me-2"></i>Accedir
        </button>
      </form>
    </div>
    <div class="footer-spait">
      <a href="../index.php">Tornar a la cartilla</a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
