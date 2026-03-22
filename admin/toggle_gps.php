<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings_file = DATA_PATH . '../settings.json';
$settings = [];
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true) ?? [];
}

$current = $settings['gps_override'] ?? false;
$settings['gps_override'] = !$current;

file_put_contents($settings_file, json_encode($settings));

header('Location: dashboard.php');
exit;
