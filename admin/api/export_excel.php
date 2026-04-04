<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';
require_once '../../includes/crypto.php';

if (!is_admin_logged()) { http_response_code(401); exit; }

$settings = get_settings();
$parades  = $settings['parades'] ?? [];
$users    = get_all_users();
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

// Detectar id parada final
$id_final = null;
foreach ($parades as $p) {
    if (!empty($p['es_final']) || !empty($p['final'])) {
        $id_final = $p['id'];
        break;
    }
}
if ($id_final === null) $id_final = 10;

$nom_fitxer = 'participants_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nom_fitxer . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// BOM UTF-8 per Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// ── Full 1: Participants ───────────────────────────────
fputcsv($output, ['=== PARTICIPANTS ==='], ';');
fputcsv($output, ['ID', 'Nom', 'Email', 'Telèfon', 'Ruta', 'Data registre', 'Motivació', 'Parades completades', 'Ha acabat?', 'Contrasenya'], ';');

foreach ($users as $user) {
    $checkin_ids   = array_column($user['checkins'] ?? [], 'parada_id');
    $parades_fetes = count($user['checkins'] ?? []);
    $ha_acabat     = in_array($id_final, $checkin_ids) ? 'Sí' : 'No';
    $password      = decrypt_password($user['password_enc'] ?? '');

    fputcsv($output, [
        $user['id'],
        $user['nom'],
        $user['email'] ?? '',
        $user['telefon'] ?? '',
        $user['ruta'] ?? '',
        $user['created_at'] ?? '',
        $user['motivacio'] ?? '',
        $parades_fetes,
        $ha_acabat,
        $password,
    ], ';');
}

// ── Full 2: Check-ins per parada ───────────────────────
fputcsv($output, [], ';');
fputcsv($output, ['=== CHECK-INS PER PARADA ==='], ';');

$header2 = ['Participant'];
foreach ($parades as $parada) {
    $header2[] = $parada['nom'];
}
fputcsv($output, $header2, ';');

foreach ($users as $user) {
    $row2 = [$user['nom']];
    foreach ($parades as $parada) {
        $checkin = array_filter(
            $user['checkins'] ?? [],
            fn($c) => $c['parada_id'] == $parada['id']
        );
        if (!empty($checkin)) {
            $c      = array_values($checkin)[0];
            $row2[] = date('H:i', strtotime($c['timestamp']));
        } else {
            $row2[] = '';
        }
    }
    fputcsv($output, $row2, ';');
}

fclose($output);
exit;
