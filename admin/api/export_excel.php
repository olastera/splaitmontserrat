<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';
require_once '../../includes/crypto.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!is_admin_logged_in()) { http_response_code(401); exit; }

$settings = get_settings();
$parades  = $settings['parades'] ?? [];
$users    = get_all_users();
usort($users, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

$spreadsheet = new Spreadsheet();

// ── Full 1: Participants ───────────────────────────────
$sheet1 = $spreadsheet->getActiveSheet();
$sheet1->setTitle('Participants');

$headers = [
    'A' => 'ID',
    'B' => 'Nom',
    'C' => 'Email',
    'D' => 'Telèfon',
    'E' => 'Ruta',
    'F' => 'Data registre',
    'G' => 'Motivació',
    'H' => 'Parades completades',
    'I' => 'Ha acabat?',
    'J' => 'Contrasenya',
];

foreach ($headers as $col => $header) {
    $sheet1->setCellValue($col . '1', $header);
    $sheet1->getStyle($col . '1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
}

// Detectar id parada final
$id_final = null;
foreach ($parades as $p) {
    if (!empty($p['es_final']) || !empty($p['final'])) {
        $id_final = $p['id'];
        break;
    }
}
if ($id_final === null) $id_final = 10;

$row = 2;
foreach ($users as $user) {
    $checkin_ids   = array_column($user['checkins'] ?? [], 'parada_id');
    $parades_fetes = count($user['checkins'] ?? []);
    $ha_acabat     = in_array($id_final, $checkin_ids) ? 'Sí' : 'No';
    $password      = decrypt_password($user['password_enc'] ?? '');

    $sheet1->setCellValue('A' . $row, $user['id']);
    $sheet1->setCellValue('B' . $row, $user['nom']);
    $sheet1->setCellValue('C' . $row, $user['email'] ?? '');
    $sheet1->setCellValue('D' . $row, $user['telefon'] ?? '');
    $sheet1->setCellValue('E' . $row, $user['ruta'] ?? '');
    $sheet1->setCellValue('F' . $row, $user['created_at'] ?? '');
    $sheet1->setCellValue('G' . $row, $user['motivacio'] ?? '');
    $sheet1->setCellValue('H' . $row, $parades_fetes);
    $sheet1->setCellValue('I' . $row, $ha_acabat);
    $sheet1->setCellValue('J' . $row, $password);

    if ($row % 2 === 0) {
        $sheet1->getStyle('A' . $row . ':J' . $row)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
        ]);
    }

    $row++;
}

foreach (range('A', 'J') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}
$sheet1->freezePane('A2');

// ── Full 2: Check-ins per parada ───────────────────────
$sheet2 = $spreadsheet->createSheet();
$sheet2->setTitle('Check-ins per parada');

$sheet2->setCellValue('A1', 'Participant');
$sheet2->getStyle('A1')->applyFromArray([
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
]);

foreach ($parades as $i => $parada) {
    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
    $sheet2->setCellValue($col . '1', $parada['nom']);
    $sheet2->getStyle($col . '1')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '27AE60']],
    ]);
}

$row = 2;
foreach ($users as $user) {
    $sheet2->setCellValue('A' . $row, $user['nom']);
    foreach ($parades as $i => $parada) {
        $col    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 2);
        $checkin = array_filter(
            $user['checkins'] ?? [],
            fn($c) => $c['parada_id'] == $parada['id']
        );
        if (!empty($checkin)) {
            $c = array_values($checkin)[0];
            $sheet2->setCellValue($col . $row,
                date('H:i', strtotime($c['timestamp']))
            );
            $sheet2->getStyle($col . $row)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
            ]);
        } else {
            $sheet2->setCellValue($col . $row, '—');
            $sheet2->getStyle($col . $row)->getFont()->getColor()->setRGB('CCCCCC');
        }
    }
    $row++;
}

$sheet2->getColumnDimension('A')->setAutoSize(true);
$sheet2->freezePane('B2');

// ── Descarregar ────────────────────────────────────────
$spreadsheet->setActiveSheetIndex(0);
$nom_fitxer = 'participants_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nom_fitxer . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
