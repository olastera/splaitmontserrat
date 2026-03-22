<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'No autoritzat']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body    = json_decode(file_get_contents('php://input'), true);
$section = $body['section'] ?? null;
$data    = $body['data']    ?? null;

if (!$section || $data === null) {
    echo json_encode(['ok' => false, 'error' => 'Falten dades']);
    exit;
}

$allowed_sections = ['event', 'visual', 'checkin', 'rutes', 'parades', 'gps_override'];
if (!in_array($section, $allowed_sections)) {
    echo json_encode(['ok' => false, 'error' => 'Secció no vàlida']);
    exit;
}

// Validació bàsica per a parades
if ($section === 'parades') {
    if (!is_array($data)) {
        echo json_encode(['ok' => false, 'error' => 'Format incorrecte']);
        exit;
    }
    foreach ($data as $p) {
        if (!isset($p['id'], $p['nom'], $p['lat'], $p['lng'])) {
            echo json_encode(['ok' => false, 'error' => 'Parada incompleta (falten id, nom, lat o lng)']);
            exit;
        }
    }
}

$settings            = get_settings();
$settings[$section]  = $data;
$ok                  = save_settings($settings);

echo json_encode(['ok' => $ok]);
