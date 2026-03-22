<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';
require_once '../../includes/crypto.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
if (!is_admin_logged_in()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$action = $_POST['action'] ?? 'preview'; // 'preview' o 'import'
$file   = $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Error pujant fitxer']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['ok' => false, 'error' => 'Format no vàlid. Usa .xlsx, .xls o .csv']);
    exit;
}

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $sheet       = $spreadsheet->getActiveSheet();
    $rows        = $sheet->toArray();
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut llegir el fitxer: ' . $e->getMessage()]);
    exit;
}

// Detectar fila de capçaleres (la que conté "nom")
$header_row = null;
$col_map    = [];
foreach ($rows as $ri => $row) {
    foreach ($row as $ci => $cell) {
        $cell_norm = strtolower(trim((string)$cell));
        if ($cell_norm === 'nom' || $cell_norm === 'nom *') {
            $header_row = $ri;
            break 2;
        }
    }
}

if ($header_row === null) {
    echo json_encode(['ok' => false, 'error' => 'No s\'ha trobat la capçalera. Usa la plantilla oficial.']);
    exit;
}

// Mapejar columnes per nom
$headers = $rows[$header_row];
foreach ($headers as $ci => $header) {
    $h = strtolower(trim((string)$header));
    $h = rtrim($h, ' *'); // eliminar asteriscs i espais
    $col_map[$h] = $ci;
}

// Validar columnes mínimes
if (!isset($col_map['nom'])) {
    echo json_encode(['ok' => false, 'error' => 'Falta la columna "nom" a la capçalera.']);
    exit;
}

$settings      = get_settings();
$rutes_valides = array_column($settings['rutes'] ?? [], 'id');
if (empty($rutes_valides)) $rutes_valides = ['llarga', 'curta'];

// Processar files de dades
$nous      = [];
$duplicats = [];
$errors    = [];
$total     = 0;

for ($ri = $header_row + 1; $ri < count($rows); $ri++) {
    $row = $rows[$ri];

    // Saltar files completament buides
    $is_empty = true;
    foreach ($row as $cell) {
        if (trim((string)$cell) !== '') { $is_empty = false; break; }
    }
    if ($is_empty) continue;

    $total++;
    $fila_num = $ri + 1;

    $nom      = trim((string)($row[$col_map['nom']] ?? ''));
    $email    = strtolower(trim((string)($row[$col_map['email']] ?? '')));
    $telefon  = trim((string)($row[$col_map['telefon']] ?? ''));
    $ruta     = strtolower(trim((string)($row[$col_map['ruta']] ?? $row[$col_map['ruta *']] ?? '')));
    $password = trim((string)($row[$col_map['contrasenya']] ?? ''));
    $motivacio = trim((string)($row[$col_map['motivacio']] ?? ''));

    // Validacions bàsiques
    if (empty($nom)) {
        $errors[] = ['fila' => $fila_num, 'motiu' => 'El camp "nom" és obligatori'];
        continue;
    }
    if (empty($email) && empty($telefon)) {
        $errors[] = ['fila' => $fila_num, 'motiu' => "«$nom»: cal email o telèfon"];
        continue;
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['fila' => $fila_num, 'motiu' => "«$nom»: email no vàlid ($email)"];
        continue;
    }
    if (!empty($ruta) && !in_array($ruta, $rutes_valides)) {
        $errors[] = ['fila' => $fila_num, 'motiu' => "«$nom»: ruta no vàlida ($ruta). Opcions: " . implode(', ', $rutes_valides)];
        continue;
    }

    // Detectar duplicats
    $identifier = $email ?: $telefon;
    $existing   = get_user_by_email($identifier);
    if ($existing) {
        $duplicats[] = [
            'nom'   => $nom,
            'motiu' => $email ? "email: $email" : "telèfon: $telefon",
        ];
        continue;
    }

    $nous[] = [
        'nom'      => $nom,
        'email'    => $email,
        'telefon'  => $telefon,
        'ruta'     => $ruta ?: ($rutes_valides[0] ?? 'llarga'),
        'password' => $password,
        'motivacio' => $motivacio,
    ];
}

if ($action === 'preview') {
    echo json_encode([
        'ok'       => true,
        'total'    => $total,
        'nous'     => count($nous),
        'duplicats' => $duplicats,
        'errors'   => $errors,
    ]);
    exit;
}

// action === 'import' — crear usuaris nous
$created = 0;
$import_errors = [];

foreach ($nous as $u) {
    // Generar contrasenya si no n'hi ha
    $password_plain = $u['password'];
    if (empty($password_plain)) {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password_plain = '';
        for ($i = 0; $i < 8; $i++) {
            $password_plain .= $chars[random_int(0, strlen($chars) - 1)];
        }
    }

    // Comprovar de nou per si s'ha creat entre preview i import
    $identifier = $u['email'] ?: $u['telefon'];
    if (get_user_by_email($identifier)) {
        $import_errors[] = $u['nom'] . ' (duplicat en importar)';
        continue;
    }

    create_user([
        'nom'      => $u['nom'],
        'email'    => $u['email'],
        'telefon'  => $u['telefon'],
        'password' => $password_plain,
        'ruta'     => $u['ruta'],
        'motivacio' => $u['motivacio'],
    ]);
    $created++;
}

echo json_encode([
    'ok'      => true,
    'created' => $created,
    'errors'  => $import_errors,
]);
exit;
