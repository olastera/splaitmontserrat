<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!is_admin_logged()) { http_response_code(401); exit; }

$settings   = get_settings();
$nom_event  = $settings['event']['nom'] ?? 'Caminada';
$rutes      = $settings['rutes'] ?? [];
$noms_rutes = implode(' / ', array_column($rutes, 'id'));
if (empty($noms_rutes)) $noms_rutes = 'llarga / curta';

$ruta_exemple = $rutes[0]['id'] ?? 'llarga';

$nom_fitxer = 'plantilla_participants_' . date('Y') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $nom_fitxer . '"');
header('Cache-Control: max-age=0');
header('Pragma: no-cache');

// BOM UTF-8 per Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Fila informativa
fputcsv($output, ["Plantilla d'importació — $nom_event — Camps obligatoris: nom + (email O telefon). Ruta: $noms_rutes"], ';');

// Capçaleres
fputcsv($output, ['nom *', 'email', 'telefon', 'ruta *', 'contrasenya', 'motivacio'], ';');

// Fila d'exemple
fputcsv($output, [
    'Maria Garcia',
    'maria@example.com',
    '612345678',
    $ruta_exemple,
    '(deixar buit = es genera automàtica)',
    'Ho faig per tradició familiar',
], ';');

fclose($output);
exit;
