<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$body  = json_decode(file_get_contents('php://input'), true);
$order = $body['order'] ?? [];

$settings = get_settings();
$parades  = $settings['parades'];

// Indexar per id
$indexed = [];
foreach ($parades as $p) $indexed[(string)$p['id']] = $p;

// Reordenar
$reordered = [];
foreach ($order as $id) {
    if (isset($indexed[(string)$id])) $reordered[] = $indexed[(string)$id];
}

// Afegir les que no eren a l'ordre (per si de cas)
foreach ($parades as $p) {
    if (!in_array((string)$p['id'], array_map('strval', $order))) {
        $reordered[] = $p;
    }
}

$settings['parades'] = $reordered;
echo json_encode(['ok' => save_settings($settings)]);
