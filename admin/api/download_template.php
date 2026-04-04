<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

if (!is_admin_logged()) { http_response_code(401); exit; }

$settings     = get_settings();
$nom_event    = $settings['event']['nom'] ?? 'Caminada';
$rutes        = $settings['rutes'] ?? [];
$noms_rutes   = implode(' / ', array_column($rutes, 'id'));
if (empty($noms_rutes)) $noms_rutes = 'llarga / curta';
$ruta_exemple = $rutes[0]['id'] ?? 'llarga';
$parades      = $settings['parades'] ?? [];

// ── Capçaleres (idèntiques a l'export) ──────────────────────────────────────
$headers = [
    'id', 'nom *', 'email', 'telèfon', 'ruta *', 'contrasenya', 'motivació',
    'data_registre', 'parades_completades', 'total_parades', 'ha_acabat',
    'hora_inici', 'hora_final',
];

// Afegir columnes de tests (igual que export_csv.php)
foreach ($parades as $p) {
    foreach ($p['preguntes'] ?? [] as $q) {
        $headers[] = 'P' . $p['id'] . ' ' . mb_substr($q['text'], 0, 30);
    }
}

$num_cols = count($headers);

// ── Fila d'exemple ───────────────────────────────────────────────────────────
$example = [
    '(buit per a nous / ID existent per actualitzar)',
    'Maria Garcia',
    'maria@example.com',
    '612345678',
    $ruta_exemple,
    '(buit = es genera automàtica / buit en actualització = no canvia)',
    'Ho faig per tradició familiar',
    '(auto)',  // data_registre
    '',        // parades_completades
    '',        // total_parades
    '',        // ha_acabat
    '',        // hora_inici
    '',        // hora_final
];
// Omplir columnes de tests amb buit
for ($i = count($example); $i < $num_cols; $i++) {
    $example[] = '';
}

// ── Nota informativa ─────────────────────────────────────────────────────────
$info_text = "Plantilla importació — $nom_event — "
    . "Columna 'id': buit=participant nou, ID existent=actualitzar. "
    . "Obligatoris (nous): nom + email o telèfon. Rutes: $noms_rutes. "
    . "Columnes grises (des de data_registre) s'ignoren en importar.";

// ── Constructor XLSX mínim ───────────────────────────────────────────────────
$shared_strings = [];
$ss_index       = [];

function ss_add(string $s, array &$ss, array &$idx): int {
    if (!isset($idx[$s])) {
        $idx[$s] = count($ss);
        $ss[]    = $s;
    }
    return $idx[$s];
}

function col_letter(int $col): string {
    $letter = '';
    $c = $col + 1;
    while ($c > 0) {
        $letter = chr(65 + ($c - 1) % 26) . $letter;
        $c = intdiv($c - 1, 26);
    }
    return $letter;
}

function cell_s(int $col, int $row, string $s, int $style, array &$ss, array &$idx): string {
    $ref = col_letter($col) . $row;
    $si  = ss_add($s, $ss, $idx);
    return '<c r="' . $ref . '" t="s" s="' . $style . '"><v>' . $si . '</v></c>';
}

// Fila 1: text informatiu (fons groc, estil 2)
$last_col_letter = col_letter($num_cols - 1);
$rows_xml = '<row r="1">' . cell_s(0, 1, $info_text, 2, $shared_strings, $ss_index) . '</row>';

// Fila 2: capçaleres — importables (estil 1=negreta), no importables (estil 3=gris)
$rows_xml .= '<row r="2">';
$importable_cols = 7; // id, nom, email, telèfon, ruta, contrasenya, motivació
foreach ($headers as $ci => $h) {
    $style = ($ci < $importable_cols) ? 1 : 3; // negreta vs gris
    $rows_xml .= cell_s($ci, 2, $h, $style, $shared_strings, $ss_index);
}
$rows_xml .= '</row>';

// Fila 3: exemple (estil 0 = normal, estil 4 = gris clar per no importables)
$rows_xml .= '<row r="3">';
foreach ($example as $ci => $v) {
    $style = ($ci < $importable_cols) ? 0 : 4;
    $rows_xml .= cell_s($ci, 3, $v, $style, $shared_strings, $ss_index);
}
$rows_xml .= '</row>';

// Shared strings XML
$ss_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($shared_strings) . '" uniqueCount="' . count($shared_strings) . '">';
foreach ($shared_strings as $s) {
    $ss_xml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></si>';
}
$ss_xml .= '</sst>';

// Sheet XML (merge A1:última columna per al text informatiu)
$sheet_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetData>' . $rows_xml . '</sheetData>'
    . '<mergeCells count="1"><mergeCell ref="A1:' . $last_col_letter . '1"/></mergeCells>'
    . '</worksheet>';

// Styles:
// 0=normal, 1=negreta (importables), 2=negreta+fons groc (info), 3=negreta+fons gris (no importables), 4=fons gris clar
$styles_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts count="2">'
    .   '<font><sz val="11"/><name val="Calibri"/></font>'
    .   '<font><b/><sz val="11"/><name val="Calibri"/></font>'
    . '</fonts>'
    . '<fills count="5">'
    .   '<fill><patternFill patternType="none"/></fill>'
    .   '<fill><patternFill patternType="gray125"/></fill>'
    .   '<fill><patternFill patternType="solid"><fgColor rgb="FFFFF2CC"/></patternFill></fill>'
    .   '<fill><patternFill patternType="solid"><fgColor rgb="FFD9D9D9"/></patternFill></fill>'
    .   '<fill><patternFill patternType="solid"><fgColor rgb="FFF2F2F2"/></patternFill></fill>'
    . '</fills>'
    . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs count="5">'
    .   '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'                              // 0 normal
    .   '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/>'                              // 1 negreta
    .   '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1"/>'                // 2 negreta+groc
    .   '<xf numFmtId="0" fontId="1" fillId="3" borderId="0" xfId="0" applyFill="1"/>'                // 3 negreta+gris
    .   '<xf numFmtId="0" fontId="0" fillId="4" borderId="0" xfId="0" applyFill="1"/>'                // 4 gris clar
    . '</cellXfs>'
    . '</styleSheet>';

$workbook_xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
    .           'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="Plantilla" sheetId="1" r:id="rId1"/></sheets>'
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

$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
$zip = new ZipArchive();
$zip->open($tmp, ZipArchive::OVERWRITE);
$zip->addFromString('[Content_Types].xml',        $content_types);
$zip->addFromString('_rels/.rels',                $rels_xml);
$zip->addFromString('xl/workbook.xml',            $workbook_xml);
$zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);
$zip->addFromString('xl/worksheets/sheet1.xml',   $sheet_xml);
$zip->addFromString('xl/sharedStrings.xml',       $ss_xml);
$zip->addFromString('xl/styles.xml',              $styles_xml);
$zip->close();

$nom_fitxer = 'plantilla_participants_' . date('Y') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $nom_fitxer . '"');
header('Content-Length: ' . filesize($tmp));
header('Pragma: no-cache');
header('Expires: 0');

readfile($tmp);
unlink($tmp);
