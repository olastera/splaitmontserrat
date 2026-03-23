<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if (!is_admin_logged_in()) { http_response_code(401); exit; }

$settings   = get_settings();
$nom_event  = $settings['event']['nom'] ?? 'Caminada';
$rutes      = $settings['rutes'] ?? [];
$noms_rutes = implode(' / ', array_column($rutes, 'id'));

$spreadsheet = new Spreadsheet();
$sheet       = $spreadsheet->getActiveSheet();
$sheet->setTitle('Participants');

// ── Capçalera informativa ──────────────────────────────
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', "Plantilla d'importació — $nom_event");
$sheet->getStyle('A1')->applyFromArray([
    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
]);

$sheet->mergeCells('A2:F2');
$sheet->setCellValue('A2', 'Camps obligatoris: nom + (email O telefon). Ruta: ' . $noms_rutes);
$sheet->getStyle('A2')->applyFromArray([
    'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3CD']],
]);

// ── Capçaleres de columnes ─────────────────────────────
$columnes = [
    'A' => ['nom' => 'nom *',       'exemple' => 'Maria Garcia'],
    'B' => ['nom' => 'email',       'exemple' => 'maria@example.com'],
    'C' => ['nom' => 'telefon',     'exemple' => '612345678'],
    'D' => ['nom' => 'ruta *',      'exemple' => $rutes[0]['id'] ?? 'llarga'],
    'E' => ['nom' => 'contrasenya', 'exemple' => '(deixar buit = es genera automàtica)'],
    'F' => ['nom' => 'motivacio',   'exemple' => 'Ho faig per tradició familiar'],
];

foreach ($columnes as $col => $info) {
    $sheet->setCellValue($col . '3', $info['nom']);
    $sheet->getStyle($col . '3')->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2C3E50']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    $sheet->setCellValue($col . '4', $info['exemple']);
    $sheet->getStyle($col . '4')->applyFromArray([
        'font' => ['italic' => true, 'color' => ['rgb' => '999999']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
    ]);
}

$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(40);

$sheet->getRowDimension(1)->setRowHeight(30);
$sheet->getRowDimension(3)->setRowHeight(20);

$sheet->freezePane('A5');

// ── Descarregar ────────────────────────────────────────
$nom_fitxer = 'plantilla_participants_' . date('Y') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nom_fitxer . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
