<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['ok' => false, 'error' => 'No autenticat']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$lat      = isset($body['lat'])      ? (float)$body['lat']      : null;
$lng      = isset($body['lng'])      ? (float)$body['lng']      : null;
$accuracy = isset($body['accuracy']) ? (float)$body['accuracy'] : 0.0;

if ($lat === null || $lng === null) {
    echo json_encode(['ok' => false, 'error' => 'Coordenades no vàlides']);
    exit;
}

$current_user = current_user();

// Gravar sempre (seguretat per menors), independentment del toggle de l'usuari
$ok = update_user_position($current_user['id'], $lat, $lng, $accuracy);
echo json_encode(['ok' => $ok]);
