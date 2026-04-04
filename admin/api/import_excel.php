<?php
// Evitar que warnings/notices corrompin el JSON
ini_set('display_errors', '0');
error_reporting(E_ERROR);
ob_start();

require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/user.php';
require_once '../../includes/crypto.php';

header('Content-Type: application/json');
if (!is_admin_logged()) { http_response_code(401); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$action = $_POST['action'] ?? 'preview'; // 'preview' o 'import'
$file   = $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Error pujant fitxer']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['csv', 'xlsx'])) {
    echo json_encode(['ok' => false, 'error' => 'Format no vàlid. Usa .xlsx o .csv (descarrega la plantilla)']);
    exit;
}

// ── Parser XLSX (OOXML) en PHP pur ──────────────────────────────────────────
const XLSX_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

function col_idx_from_ref(string $ref): int {
    preg_match('/^([A-Z]+)/', $ref, $m);
    $idx = 0;
    foreach (str_split($m[1]) as $ch) {
        $idx = $idx * 26 + (ord($ch) - ord('A') + 1);
    }
    return $idx - 1;
}

function xml_load(string $raw): SimpleXMLElement|false {
    return simplexml_load_string(
        $raw,
        'SimpleXMLElement',
        LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA
    );
}

function find_sheet_path(ZipArchive $zip): string {
    // Llegir workbook.xml.rels per trobar el path real del sheet1
    $rels_raw = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($rels_raw !== false) {
        $rels_xml = simplexml_load_string($rels_raw, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($rels_xml) {
            foreach ($rels_xml->Relationship as $rel) {
                $type   = (string)$rel['Type'];
                $target = (string)$rel['Target'];
                if (str_contains($type, '/worksheet')) {
                    // Target pot ser "worksheets/sheet1.xml" o "../worksheets/sheet1.xml"
                    $target = ltrim($target, '/');
                    if (!str_starts_with($target, 'xl/')) {
                        $target = 'xl/' . $target;
                    }
                    return $target;
                }
            }
        }
    }
    // Fallback
    return 'xl/worksheets/sheet1.xml';
}

function parse_xlsx(string $path): array|false {
    $zip = new ZipArchive();
    if ($zip->open($path) !== true) return false;

    // Shared strings
    $shared_strings = [];
    $ss_raw = $zip->getFromName('xl/sharedStrings.xml');
    if ($ss_raw !== false) {
        $ss_xml = xml_load($ss_raw);
        if ($ss_xml) {
            $ss_xml->registerXPathNamespace('s', XLSX_NS);
            foreach ($ss_xml->xpath('//s:si') as $si) {
                $si->registerXPathNamespace('s', XLSX_NS);
                $t_nodes = $si->xpath('.//s:t');
                $txt = '';
                foreach ($t_nodes as $t) {
                    $txt .= (string)$t;
                }
                $shared_strings[] = $txt;
            }
        }
    }

    // Trobar el path del primer sheet des del workbook.xml.rels
    $sheet_path = find_sheet_path($zip);
    $sheet_raw  = $zip->getFromName($sheet_path);

    // Fallbacks si no es troba
    if ($sheet_raw === false) $sheet_raw = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheet_raw === false) $sheet_raw = $zip->getFromName('xl/worksheets/Sheet1.xml');

    // Cercar qualsevol fitxer de worksheet si encara no s'ha trobat
    if ($sheet_raw === false) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#xl/worksheets/[^/]+\.xml$#i', $name)) {
                $sheet_raw = $zip->getFromIndex($i);
                break;
            }
        }
    }

    $zip->close();
    if ($sheet_raw === false) return false;

    $sheet_xml = xml_load($sheet_raw);
    if (!$sheet_xml) return false;

    $sheet_xml->registerXPathNamespace('s', XLSX_NS);
    $row_nodes = $sheet_xml->xpath('//s:sheetData/s:row');
    if ($row_nodes === false || empty($row_nodes)) {
        // Prova sense namespace (alguns editors desen sense xmlns explícit)
        $row_nodes = $sheet_xml->xpath('//sheetData/row');
        if (empty($row_nodes)) return [];
    }

    $rows = [];
    foreach ($row_nodes as $row_el) {
        $row_el->registerXPathNamespace('s', XLSX_NS);
        $row_data = [];

        $cells = $row_el->xpath('s:c');
        if (empty($cells)) $cells = $row_el->xpath('c'); // sense namespace

        foreach ($cells as $c) {
            $ref  = (string)$c['r'];
            $type = (string)$c['t'];
            $col_idx = col_idx_from_ref($ref);

            $c->registerXPathNamespace('s', XLSX_NS);
            $v_nodes = $c->xpath('s:v');
            if (empty($v_nodes)) $v_nodes = $c->xpath('v');
            $val_raw = isset($v_nodes[0]) ? (string)$v_nodes[0] : '';

            if ($type === 's') {
                // Shared string
                $val = $shared_strings[(int)$val_raw] ?? '';
            } elseif ($type === 'inlineStr' || $type === 'str') {
                // Inline string o formula string
                $is_nodes = $c->xpath('s:is/s:t');
                if (empty($is_nodes)) $is_nodes = $c->xpath('is/t');
                if (!empty($is_nodes)) {
                    $val = (string)$is_nodes[0];
                } else {
                    $val = $val_raw;
                }
            } elseif ($type === 'b') {
                $val = $val_raw === '1' ? 'true' : 'false';
            } else {
                // Numèric o data → tractar com string
                $val = $val_raw;
            }

            $row_data[$col_idx] = $val;
        }

        if (!empty($row_data)) {
            $max_col = max(array_keys($row_data));
            for ($i = 0; $i <= $max_col; $i++) {
                if (!isset($row_data[$i])) $row_data[$i] = '';
            }
            ksort($row_data);
            $rows[] = array_values($row_data);
        }
    }
    return $rows;
}

// ── Llegir el fitxer ─────────────────────────────────────────────────────────
$rows = [];

if ($ext === 'xlsx') {
    $rows = parse_xlsx($file['tmp_name']);
    if ($rows === false) {
        echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut llegir el fitxer Excel']);
        exit;
    }
} else {
    // CSV
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        echo json_encode(['ok' => false, 'error' => 'No s\'ha pogut llegir el fitxer']);
        exit;
    }

    $first_line = fgets($handle);
    rewind($handle);
    if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
        fread($handle, 3);
        $first_line = substr($first_line, 3);
    }
    $delimiter = (substr_count($first_line, ';') >= substr_count($first_line, ',')) ? ';' : ',';

    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $rows[] = $row;
    }
    fclose($handle);
}

if (empty($rows)) {
    echo json_encode(['ok' => false, 'error' => 'El fitxer és buit o no és vàlid']);
    exit;
}

// ── Detectar fila de capçaleres (la que conté "nom") ─────────────────────────
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
    $h = rtrim($h, ' *');
    // Normalitzar accents per compatibilitat
    $h = str_replace(['è', 'é', 'ê', 'ó', 'ò', 'í', 'ï', 'à', 'â'], ['e', 'e', 'e', 'o', 'o', 'i', 'i', 'a', 'a'], $h);
    $col_map[$h] = $ci;
}

// Acceptar variants amb accent
foreach (['telefon', 'motivacio', 'contrasenya'] as $k) {
    if (!isset($col_map[$k])) {
        foreach ($col_map as $key => $ci) {
            if (strpos($key, substr($k, 0, 5)) === 0) {
                $col_map[$k] = $ci;
                break;
            }
        }
    }
}

if (!isset($col_map['nom'])) {
    ob_clean();
    echo json_encode(['ok' => false, 'error' => 'Falta la columna "nom" a la capçalera.']);
    exit;
}

// Helper segur: retorna el valor d'una columna per nom, o '' si no existeix
function col_val(array $row, array $col_map, string $key): string {
    if (!isset($col_map[$key])) return '';
    return trim((string)($row[$col_map[$key]] ?? ''));
}

$settings      = get_settings();
$rutes_valides = array_column($settings['rutes'] ?? [], 'id');
if (empty($rutes_valides)) $rutes_valides = ['llarga', 'curta'];
$ruta_default  = $rutes_valides[0] ?? 'llarga';

// ── Processar files de dades ─────────────────────────────────────────────────
$nous         = [];
$actualitzar  = [];
$sense_canvis = [];
$errors       = [];

for ($ri = $header_row + 1; $ri < count($rows); $ri++) {
    $row = $rows[$ri];

    // Saltar files completament buides
    $is_empty = true;
    foreach ($row as $cell) {
        if (trim((string)$cell) !== '') { $is_empty = false; break; }
    }
    if ($is_empty) continue;

    $fila_num  = $ri + 1;
    $id        = col_val($row, $col_map, 'id');
    $nom       = col_val($row, $col_map, 'nom');
    $email     = strtolower(col_val($row, $col_map, 'email'));
    $telefon   = col_val($row, $col_map, 'telefon');
    $ruta      = strtolower(col_val($row, $col_map, 'ruta'));
    $password  = col_val($row, $col_map, 'contrasenya');
    $motivacio = col_val($row, $col_map, 'motivacio');

    // Ignorar files d'exemple
    if (str_contains(strtolower($password), 'autom')) continue;
    if (str_contains(strtolower($id), 'buit') || str_contains(strtolower($id), 'existent')) continue;

    // Normalitzar ruta
    if (!empty($ruta) && !in_array($ruta, $rutes_valides)) $ruta = $ruta_default;
    if (empty($ruta)) $ruta = $ruta_default;

    // ── AMB ID → ACTUALITZAR ─────────────────────────────────────────────────
    if (!empty($id)) {
        $existing = get_user($id);

        if (!$existing) {
            $errors[] = [
                'fila'  => $fila_num,
                'nom'   => $nom ?: "Fila $fila_num",
                'motiu' => "ID '$id' no existeix a la plataforma",
            ];
            continue;
        }

        // Construir array de canvis (camps que realment canvien)
        $canvis = [];
        if (!empty($nom)       && $nom       !== ($existing['nom']       ?? '')) $canvis['nom']       = $nom;
        if (!empty($email)     && $email     !== ($existing['email']     ?? '')) $canvis['email']     = $email;
        if (!empty($telefon)   && $telefon   !== ($existing['telefon']   ?? '')) $canvis['telefon']   = $telefon;
        if (!empty($ruta)      && $ruta      !== ($existing['ruta']      ?? '')) $canvis['ruta']      = $ruta;
        if (!empty($motivacio) && $motivacio !== ($existing['motivacio'] ?? '')) $canvis['motivacio'] = $motivacio;
        if (!empty($password)) $canvis['password'] = $password;

        if (empty($canvis)) {
            $sense_canvis[] = ['id' => $id, 'nom' => $existing['nom']];
        } else {
            $actualitzar[] = ['id' => $id, 'nom' => $existing['nom'], 'canvis' => $canvis];
        }
        continue;
    }

    // ── SENSE ID → ALTA NOVA ─────────────────────────────────────────────────

    if (empty($nom)) {
        $errors[] = ['fila' => $fila_num, 'nom' => "Fila $fila_num", 'motiu' => 'El camp "nom" és obligatori'];
        continue;
    }
    if (empty($email) && empty($telefon)) {
        $errors[] = ['fila' => $fila_num, 'nom' => $nom, 'motiu' => "Cal email o telèfon per a '$nom'"];
        continue;
    }
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = ['fila' => $fila_num, 'nom' => $nom, 'motiu' => "Email invàlid: '$email'"];
        continue;
    }

    // Comprovar duplicats per email i telèfon
    if (!empty($email) && get_user_by_email($email)) {
        $errors[] = ['fila' => $fila_num, 'nom' => $nom, 'motiu' => "L'email '$email' ja està registrat"];
        continue;
    }
    if (!empty($telefon) && get_user_by_email($telefon)) {
        $errors[] = ['fila' => $fila_num, 'nom' => $nom, 'motiu' => "El telèfon '$telefon' ja està registrat"];
        continue;
    }

    if (empty($password)) {
        $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
    }

    $nous[] = [
        'nom'       => $nom,
        'email'     => $email,
        'telefon'   => $telefon,
        'ruta'      => $ruta,
        'password'  => $password,
        'motivacio' => $motivacio,
    ];
}

// ── SI HI HA ERRORS → BLOQUEJAR TOT ──────────────────────────────────────────
if (!empty($errors)) {
    ob_clean();
    echo json_encode([
        'ok'      => false,
        'errors'  => $errors,
        'missatge' => 'No s\'ha importat res. Corregeix els errors i torna a pujar el fitxer.',
    ]);
    exit;
}

// ── MODE PREVIEW ──────────────────────────────────────────────────────────────
if ($action === 'preview') {
    ob_clean();
    echo json_encode([
        'ok'           => true,
        'nous'         => count($nous),
        'actualitzats' => count($actualitzar),
        'sense_canvis' => count($sense_canvis),
        'errors'       => [],
        'detall' => [
            'nous'        => array_map(fn($u) => $u['nom'], $nous),
            'actualitzar' => array_map(fn($u) => ['nom' => $u['nom'], 'canvis' => array_keys($u['canvis'])], $actualitzar),
            'sense_canvis'=> array_map(fn($u) => $u['nom'], $sense_canvis),
        ],
        '_debug' => [
            'col_map'    => $col_map,
            'header_row' => $header_row,
            'total_rows' => count($rows),
            'te_id_col'  => isset($col_map['id']),
        ],
    ]);
    exit;
}

// ── MODE IMPORT → EXECUTAR ────────────────────────────────────────────────────
$created = 0;
$updated = 0;
$failed  = 0;

// Donar d'alta nous
foreach ($nous as $u) {
    $result = create_user([
        'nom'       => $u['nom'],
        'email'     => $u['email'],
        'telefon'   => $u['telefon'],
        'password'  => $u['password'],
        'ruta'      => $u['ruta'],
        'motivacio' => $u['motivacio'],
    ]);
    $result ? $created++ : $failed++;
}

// Actualitzar existents
foreach ($actualitzar as $item) {
    $user = get_user($item['id']);
    if (!$user) { $failed++; continue; }

    foreach ($item['canvis'] as $camp => $valor) {
        if ($camp === 'password') {
            $user['password_enc'] = encrypt_password($valor);
        } else {
            $user[$camp] = htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
        }
    }
    $user['updated_at'] = date('c');
    $user['updated_by'] = 'import_excel';

    save_user($user) ? $updated++ : $failed++;
}

ob_clean();
$response = [
    'ok'      => true,
    'created' => $created,
    'updated' => $updated,
    'failed'  => $failed,
];
if ($failed > 0) {
    $response['warning'] = "$failed operació(ns) han fallat. Possible problema de permisos al directori data/.";
}
echo json_encode($response);
