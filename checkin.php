<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'Sessió no vàlida.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Mètode no permès.']);
    exit;
}

$accio     = $_POST['accio'] ?? '';
$parada_id = intval($_POST['parada_id'] ?? -1);
$user      = current_user();

if (!$user) {
    echo json_encode(['ok' => false, 'error' => 'Usuari no trobat.']);
    exit;
}

// Buscar la parada des de settings dinàmics
$settings = get_settings();
$parades  = $settings['parades'] ?? [];

$parada = null;
foreach ($parades as $p) {
    if ($p['id'] === $parada_id) {
        $parada = $p;
        break;
    }
}

if (!$parada) {
    echo json_encode(['ok' => false, 'error' => 'Parada no trobada.']);
    exit;
}

// Validar que la parada pertany a la ruta de l'usuari
$ruta = $user['ruta'] ?? 'curta';
$parada_rutes = $parada['rutes'] ?? [];
// Compatibilitat format antic
if (empty($parada_rutes) && isset($parada['ruta'])) {
    $parada_rutes = $parada['ruta'] === 'ambdues' ? ['llarga', 'curta'] : [$parada['ruta']];
}
if (!in_array($ruta, $parada_rutes)) {
    echo json_encode(['ok' => false, 'error' => 'Aquesta parada no és de la teva ruta.']);
    exit;
}

// Comprovar si ja té check-in
if (has_checkin($user['id'], $parada_id)) {
    echo json_encode(['ok' => false, 'error' => 'Ja has fet check-in en aquesta parada.']);
    exit;
}

if ($accio === 'validar_codi') {
    // Parada sense codi (inici) → OK directament
    if ($parada['codi'] === null) {
        echo json_encode(['ok' => true]);
        exit;
    }

    $codi        = strtoupper(trim($_POST['codi'] ?? ''));
    $codi_mestre = strtoupper(trim($settings['checkin']['codi_mestre'] ?? ''));

    // Acceptar codi mestre si està configurat
    if ($codi_mestre && $codi === $codi_mestre) {
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($codi === strtoupper($parada['codi'])) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Codi incorrecte. Pregunta al responsable de la parada!']);
    }
    exit;
}

if ($accio === 'checkin') {
    $test_raw = $_POST['test'] ?? '{}';
    $test = json_decode($test_raw, true);
    if (!is_array($test)) $test = [];

    // Sanititzar respostes
    $test_clean = [];
    foreach ($test as $k => $v) {
        $k_clean = preg_replace('/[^a-z0-9_]/', '', $k);
        $test_clean[$k_clean] = htmlspecialchars(substr(trim($v), 0, 500));
    }

    $ok = add_checkin($user['id'], $parada_id, $test_clean);
    if ($ok) {
        echo json_encode(['ok' => true, 'parada_nom' => $parada['nom']]);
    } else {
        echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut guardar el check-in. Torna-ho a provar.']);
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acció no reconeguda.']);
