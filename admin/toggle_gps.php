<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');

$settings = get_settings();
$settings['gps_override'] = !($settings['gps_override'] ?? false);
save_settings($settings);

header('Location: dashboard.php');
exit;
