<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$files   = glob(DATA_PATH . '*.json');
$deleted = 0;
foreach ($files as $file) {
    if (unlink($file)) $deleted++;
}

echo json_encode(['ok' => true, 'deleted' => $deleted]);
