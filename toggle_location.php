<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'No autenticat']);
    exit;
}

$body  = json_decode(file_get_contents('php://input'), true);
$share = !empty($body['share']);

$current_user = current_user();
$ok = set_share_location($current_user['id'], $share);

echo json_encode([
    'ok'   => $ok,
    'share' => $share,
    'note' => $share
        ? 'Posició en temps real activada'
        : 'Actualitzacions pausades. L\'última posició és visible pels organitzadors per seguretat.',
]);
