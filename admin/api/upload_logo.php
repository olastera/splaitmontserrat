<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!is_admin_logged()) { http_response_code(401); echo json_encode(['ok' => false]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml'];
$file = $_FILES['logo'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Error pujant fitxer']);
    exit;
}

// Validar tipus per MIME real, no per extensió
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimetype = $finfo->file($file['tmp_name']);
if (!in_array($mimetype, $allowed_types)) {
    echo json_encode(['ok' => false, 'error' => 'Tipus de fitxer no permès']);
    exit;
}

if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['ok' => false, 'error' => 'El fitxer és massa gran (màx 2MB)']);
    exit;
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'logo_' . time() . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext);
$dest_dir = __DIR__ . '/../../assets/img/';

if (!is_dir($dest_dir)) {
    mkdir($dest_dir, 0755, true);
}

$dest = $dest_dir . $filename;
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut desar el fitxer']);
    exit;
}

$url = '/assets/img/' . $filename;

// Guardar a settings
$settings = get_settings();
$settings['visual']['logo_local'] = $url;
save_settings($settings);

echo json_encode(['ok' => true, 'url' => $url]);
