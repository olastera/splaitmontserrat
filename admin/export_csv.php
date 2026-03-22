<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

require_admin('index.php');



$users = get_all_users();

// Capçaleres CSV
$headers = [
    'id', 'nom', 'email', 'telefon', 'ruta',
    'data_registre', 'motivacio',
    'parades_completades', 'total_parades', 'ha_acabat',
    'hora_inici', 'hora_final',
];

// Afegir columnes dels tests
foreach ($TESTS as $pid => $test) {
    $nom_parada = '';
    foreach ($PARADES as $p) {
        if ($p['id'] === $pid) { $nom_parada = preg_replace('/[^a-z0-9]/i', '_', $p['nom']); break; }
    }
    foreach ($test as $key => $q) {
        $headers[] = 'test_' . $key . '_parada' . $pid;
    }
}

// Netejar i codificar text per CSV
function csv_val($val): string {
    $val = str_replace('"', '""', (string) $val);
    return '"' . $val . '"';
}

// Construir fitxer
$output = fopen('php://temp', 'r+');

// BOM UTF-8 per Excel
fputs($output, "\xEF\xBB\xBF");

// Capçalera
fputcsv($output, $headers, ';');

foreach ($users as $u) {
    $prog = get_user_progress($u, $PARADES);
    $cids = array_column($u['checkins'] ?? [], 'parada_id');

    // Hora inici / final
    $hora_inici = '';
    $hora_final = '';
    foreach ($u['checkins'] ?? [] as $ci) {
        if (empty($hora_inici)) $hora_inici = $ci['timestamp'] ?? '';
        $hora_final = $ci['timestamp'] ?? '';
    }

    // Mapa check-ins per test
    $tests_data = [];
    foreach ($u['checkins'] ?? [] as $ci) {
        if (!empty($ci['test'])) {
            $tests_data[$ci['parada_id']] = $ci['test'];
        }
    }

    $row = [
        $u['id'],
        $u['nom'],
        $u['email'] ?? '',
        $u['telefon'] ?? '',
        $u['ruta'] ?? '',
        $u['created_at'] ?? '',
        $u['motivacio'] ?? '',
        $prog['completades'],
        $prog['total'],
        $prog['acabat'] ? 'Sí' : 'No',
        $hora_inici,
        $hora_final,
    ];

    // Respostes test
    foreach ($TESTS as $pid => $test) {
        foreach ($test as $key => $q) {
            $row[] = $tests_data[$pid][$key] ?? '';
        }
    }

    fputcsv($output, $row, ';');
}

rewind($output);
$csv_content = stream_get_contents($output);
fclose($output);

$filename = 'participants_montserrat2026_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo $csv_content;
