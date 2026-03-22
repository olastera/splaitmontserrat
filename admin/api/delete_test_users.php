<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$settings        = get_settings();
$data_event      = $settings['event']['data_esdeveniment'] ?? date('Y-m-d');
$event_timestamp = strtotime($data_event);

$users   = get_all_users();
$deleted = 0;

foreach ($users as $user) {
    $created = strtotime($user['created_at'] ?? '2000-01-01');
    if ($created < $event_timestamp) {
        $file = DATA_PATH . $user['id'] . '.json';
        if (file_exists($file) && unlink($file)) $deleted++;
    }
}

echo json_encode(['ok' => true, 'deleted' => $deleted]);
