<?php
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

$user['checkins'][] = [
    'parada_id' => -1,
    'timestamp' => date('c'),
    'inici' => true,
];

save_user($user);

echo json_encode(['ok' => true]);
