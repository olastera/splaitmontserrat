<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/crypto.php';

require_admin('index.php');

$settings = get_settings();
$parades  = $settings['parades'] ?? $PARADES;

$users = get_all_users();

// ── Capçaleres ──────────────────────────────────────────────────────────────
$headers = [
    'ID', 'Nom', 'Email', 'Telèfon', 'Ruta', 'Contrasenya',
    'Motivació', 'Data registre',
    'Parades completades', 'Total parades', 'Ha acabat',
    'Hora inici', 'Hora final',
];

// Columnes de tests (des de parades del settings)
$test_cols = [];
foreach ($parades as $p) {
    foreach ($p['preguntes'] ?? [] as $q) {
        $key = 'test_' . $q['id'] . '_parada' . $p['id'];
        $label = 'P' . $p['id'] . ' ' . mb_substr($q['text'], 0, 30);
        $headers[]  = $label;
        $test_cols[] = ['pid' => $p['id'], 'qid' => $q['id']];
    }
}

// ── Construir files ──────────────────────────────────────────────────────────
$rows = [];
foreach ($users as $u) {
    $prog = get_user_progress($u, $parades);

    $hora_inici = '';
    $hora_final = '';
    foreach ($u['checkins'] ?? [] as $ci) {
        if (empty($hora_inici)) $hora_inici = $ci['timestamp'] ?? '';
        $hora_final = $ci['timestamp'] ?? '';
    }

    $tests_data = [];
    foreach ($u['checkins'] ?? [] as $ci) {
        if (!empty($ci['test'])) {
            $tests_data[$ci['parada_id']] = $ci['test'];
        }
    }

    $password_plain = '';
    if (!empty($u['password_enc'])) {
        try { $password_plain = decrypt_password($u['password_enc']); } catch (Exception $e) {}
    }

    $row = [
        $u['id'],
        $u['nom'],
        $u['email']     ?? '',
        $u['telefon']   ?? '',
        $u['ruta']      ?? '',
        $password_plain,
        $u['motivacio'] ?? '',
        $u['created_at'] ?? '',
        $prog['completades'],
        $prog['total'],
        $prog['acabat'] ? 'Sí' : 'No',
        $hora_inici,
        $hora_final,
    ];

    foreach ($test_cols as $tc) {
        $row[] = $tests_data[$tc['pid']][$tc['qid']] ?? '';
    }

    $rows[] = $row;
}

// ── Generador XLSX (OOXML) sense llibreries externes ────────────────────────

/**
 * Escapa un valor per incloure'l en una cel·la XML d'Excel.
 * Retorna ['t' => tipus, 'v' => valor_xml]
 */
function xlsx_cell(int $col, int $row_num, $val, array &$shared_strings, array &$ss_index): string {
    $col_letter = '';
    $c = $col + 1;
    while ($c > 0) {
        $col_letter = chr(65 + ($c - 1) % 26) . $col_letter;
        $c = intdiv($c - 1, 26);
    }
    $ref = $col_letter . $row_num;

    // Números
    if (is_numeric($val) && !is_string($val)) {
        return '<c r="' . $ref . '"><v>' . htmlspecialchars((string)$val, ENT_XML1) . '</v></c>';
    }

    $str = (string)$val;
    // Cadena compartida
    if (!isset($ss_index[$str])) {
        $ss_index[$str] = count($shared_strings);
        $shared_strings[] = $str;
    }
    return '<c r="' . $ref . '" t="s"><v>' . $ss_index[$str] . '</v></c>';
}

$shared_strings = [];
$ss_index       = [];
$sheet_rows_xml = '';

// Fila de capçaleres (negreta via estil s="1")
$sheet_rows_xml .= '<row r="1">';
foreach ($headers as $ci => $h) {
    $col_letter = '';
    $c = $ci + 1;
    while ($c > 0) {
        $col_letter = chr(65 + ($c - 1) % 26) . $col_letter;
        $c = intdiv($c - 1, 26);
    }
    $ref = $col_letter . '1';
    $str = (string)$h;
    if (!isset($ss_index[$str])) {
        $ss_index[$str] = count($shared_strings);
        $shared_strings[] = $str;
    }
    $sheet_rows_xml .= '<c r="' . $ref . '" t="s" s="1"><v>' . $ss_index[$str] . '</v></c>';
}
$sheet_rows_xml .= '</row>';

foreach ($rows as $ri => $row) {
    $rn = $ri + 2;
    $sheet_rows_xml .= '<row r="' . $rn . '">';
    foreach ($row as $ci => $val) {
        $sheet_rows_xml .= xlsx_cell($ci, $rn, $val, $shared_strings, $ss_index);
    }
    $sheet_rows_xml .= '</row>';
}

// XML shared strings
$ss_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared_strings) . '" uniqueCount="' . count($shared_strings) . '">';
foreach ($shared_strings as $s) {
    $ss_xml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
}
$ss_xml .= '</sst>';

// XML full del sheet
$sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetData>' . $sheet_rows_xml . '</sheetData>'
    . '</worksheet>';

// XML styles (estil 0 = normal, estil 1 = negreta per capçaleres)
$styles_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="2">'
    .   '<font><sz val="11"/><name val="Calibri"/></font>'
    .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
    . '</fonts>'
    . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="2">'
    .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
    .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'
    . '</cellXfs>'
    . '</styleSheet>';

// Workbook XML
$workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
    .           'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Participants" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

$workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$rels_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml" ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

// ── Construir el ZIP (XLSX) en memòria ──────────────────────────────────────
$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml',              $content_types);
$zip->addFromString('_rels/.rels',                      $rels_xml);
$zip->addFromString('xl/workbook.xml',                  $workbook_xml);
$zip->addFromString('xl/_rels/workbook.xml.rels',       $workbook_rels);
$zip->addFromString('xl/worksheets/sheet1.xml',         $sheet_xml);
$zip->addFromString('xl/sharedStrings.xml',             $ss_xml);
$zip->addFromString('xl/styles.xml',                    $styles_xml);
$zip->close();

$filename = 'participants_montserrat2026_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmp);
unlink($tmp);
