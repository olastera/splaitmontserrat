<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body      = json_decode(file_get_contents('php://input'), true);
$parada_id = $body['id'] ?? null;

if ($parada_id === null) { echo json_encode(['ok' => false, 'error' => 'ID no especificat']); exit; }

$settings             = get_settings();
$settings['parades']  = array_values(
    array_filter($settings['parades'], fn($p) => (int)$p['id'] !== (int)$parada_id)
);

echo json_encode(['ok' => save_settings($settings)]);
