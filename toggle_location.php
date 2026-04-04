<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

$log = __DIR__ . '/toggle_debug.log';
$logLine = date('Y-m-d H:i:s') . ' | ';
file_put_contents($log, $logLine . "CALLED\n", FILE_APPEND);

if (!is_logged_in()) {
    file_put_contents($log, $logLine . "NOT_AUTH\n", FILE_APPEND);
    echo json_encode(['ok' => false, 'error' => 'No autenticat']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$share = !empty($body['share']);

$current_user = current_user();
$ok = set_share_location($current_user['id'], $share);

file_put_contents($log, $logLine . "user=" . ($current_user['id'] ?? 'null') . " share=" . ($share?'true':'false') . " ok=" . ($ok?'true':'false') . "\n", FILE_APPEND);

echo json_encode([
    'ok'   => $ok,
    'share' => $share,
    'note' => $share
        ? 'Posició en temps real activada'
        : 'Actualitzacions pausades. L\'última posició és visible pels organitzadors per seguretat.',
]);
