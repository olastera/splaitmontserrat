<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autenticat']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$codi = strtoupper(trim($data['codi'] ?? ''));

$settings = get_settings();
$codi_correcte = strtoupper(trim($settings['checkin']['codi_inici'] ?? ''));

// DEBUG
file_put_contents('/tmp/debug_iniciar.log', 
    "codi rebut: [" . $codi . "]\n" .
    "codi correcte: [" . $codi_correcte . "]\n" .
    "settings checkin: " . json_encode($settings['checkin'] ?? []) . "\n"
);

if (empty($codi_correcte)) {
    echo json_encode(['ok' => true, 'skip' => true]);
    exit;
}

if ($codi !== $codi_correcte) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Codi incorrecte. Torna\'ho a provar!']);
    exit;
}

require_once __DIR__ . '/includes/user.php';
$user = get_user($_SESSION['user_id']);

$settings = get_settings();
$parades = $settings['parades'] ?? [];
$ruta = $user['ruta'] ?? 'curta';

$parada_inici = null;
foreach ($parades as $p) {
    if (!empty($p['es_inici']) || (!empty($p['es_inici_ruta']) && $p['es_inici_ruta'] === $ruta)) {
        $parada_inici = $p['id'];
        break;
    }
}

$user['checkins'][] = [
    'parada_id' => -1,
    'timestamp' => date('c'),
    'inici' => true,
];

if ($parada_inici !== null) {
    $user['checkins'][] = [
        'parada_id' => $parada_inici,
        'timestamp' => date('c'),
        'tipus' => 'inici_ruta',
    ];
}

save_user($user);

echo json_encode(['ok' => true]);
