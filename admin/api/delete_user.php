<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }

$id = $_GET['id'] ?? null;
if (!$id) { echo json_encode(['ok' => false, 'error' => 'ID no especificat']); exit; }

// Sanititzar l'ID per evitar path traversal
$id_safe = preg_replace('/[^a-zA-Z0-9\-]/', '', $id);
$file    = DATA_PATH . $id_safe . '.json';
$ok      = file_exists($file) && unlink($file);

echo json_encode(['ok' => $ok]);
