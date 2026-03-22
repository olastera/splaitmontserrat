<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/vendor/fpdf/fpdf.php';

require_login('index.php');

$user = current_user();
if (!$user) {
    logout_user();
    header('Location: index.php');
    exit;
}



$ruta = $user['ruta'] ?? 'curta';
$parades_ruta = array_values(array_filter($PARADES, function($p) use ($ruta) {
    return $p['ruta'] === 'ambdues' || $p['ruta'] === $ruta;
}));

$checkin_ids = array_column($user['checkins'] ?? [], 'parada_id');
$progress    = get_user_progress($user, $PARADES);

// Mapa parades per id per accés ràpid
$parades_map = [];
foreach ($PARADES as $p) { $parades_map[$p['id']] = $p; }

// Mapa check-ins per parada_id
$checkins_map = [];
foreach ($user['checkins'] ?? [] as $ci) {
    $checkins_map[$ci['parada_id']] = $ci;
}

// ============================================================
// PDF amb FPDF
// ============================================================
class CartillaPDF extends FPDF {
    function Header() {}
    function Footer() {}

    function spaitHeader(array $user, string $ruta) {
        // Fons capçalera
        $this->SetFillColor(44, 62, 80);
        $this->Rect(0, 0, 210, 40, 'F');

        // Títol
        $this->SetY(8);
        $this->SetFont('Helvetica', 'B', 18);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(0, 8, 'Caminada a Montserrat 2026', 0, 1, 'C');

        $this->SetFont('Helvetica', '', 11);
        $this->SetTextColor(241, 196, 15);
        $this->Cell(0, 6, 'Esplai splaiT - Som d\'esplai, res no ens atura!', 0, 1, 'C');

        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(200, 200, 200);
        $this->Cell(0, 6, 'Data: ' . date('d/m/Y'), 0, 1, 'C');

        $this->SetY(45);
        $this->SetTextColor(0, 0, 0);
    }
}

$pdf = new CartillaPDF('P', 'mm', 'A4');
$pdf->SetAuthor('Esplai splaiT');
$pdf->SetTitle('Cartilla Caminada Montserrat 2026 - ' . $user['nom']);

// ============================================================
// PÀGINA 1: La Cartilla
// ============================================================
$pdf->AddPage();
$pdf->spaitHeader($user, $ruta);

// Nom pelegri
$pdf->SetFont('Helvetica', 'B', 20);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 12, iconv('UTF-8', 'latin1//TRANSLIT', $user['nom']), 0, 1, 'C');

// Ruta
$pdf->SetFont('Helvetica', 'I', 12);
$pdf->SetTextColor(100, 100, 100);
$ruta_label = $ruta === 'llarga' ? 'Ruta Llarga (Barcelona - Mundet)' : 'Ruta Curta (Terrassa - Les Fonts)';
$pdf->Cell(0, 6, iconv('UTF-8', 'latin1//TRANSLIT', $ruta_label), 0, 1, 'C');
$pdf->Ln(3);

// Motivació
if (!empty($user['motivacio'])) {
    $pdf->SetFillColor(255, 243, 205);
    $pdf->SetFont('Helvetica', 'I', 11);
    $pdf->SetTextColor(44, 62, 80);
    $pdf->SetDrawColor(241, 196, 15);
    $pdf->SetLineWidth(0.5);
    $mot = '"' . iconv('UTF-8', 'latin1//TRANSLIT', $user['motivacio']) . '"';
    $pdf->MultiCell(0, 6, $mot, 1, 'C', true);
    $pdf->Ln(4);
}

// Progrés
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetTextColor(44, 62, 80);
$pdf->Cell(0, 7, 'Progres: ' . $progress['completades'] . '/' . $progress['total'] . ' parades (' . $progress['percent'] . '%)', 0, 1, 'C');

// Barra progrés
$barW = 160;
$barX = (210 - $barW) / 2;
$barY = $pdf->GetY();
$pdf->SetFillColor(220, 220, 220);
$pdf->Rect($barX, $barY, $barW, 6, 'F');
$fillW = $barW * $progress['percent'] / 100;
$pdf->SetFillColor(39, 174, 96);
$pdf->Rect($barX, $barY, $fillW, 6, 'F');
$pdf->Ln(10);

// Línies bloc visual
$blocks = '';
$total  = $progress['total'];
for ($i = 0; $i < $total; $i++) {
    $blocks .= $i < $progress['completades'] ? chr(219) : chr(176);
}
$pdf->SetFont('Courier', '', 14);
$pdf->SetTextColor(39, 174, 96);
$pdf->Cell(0, 8, $blocks, 0, 1, 'C');
$pdf->Ln(4);

// ---- Graella segells ----
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(44, 62, 80);
$pdf->Cell(0, 7, ' LA CARTILLA DE PELEGRI', 0, 1, 'L', true);
$pdf->Ln(2);

$cols = 2;
$cellW = 87;
$cellH = 18;
$margin = 12;
$colGap = 6;

$col = 0;
$xStart = $margin;

foreach ($parades_ruta as $idx => $p) {
    $completat  = in_array($p['id'], $checkin_ids);
    $ci         = $checkins_map[$p['id']] ?? null;
    $hora       = $ci ? date('H:i', strtotime($ci['timestamp'])) : '';
    $esFinal    = !empty($p['final']);

    $x = $xStart + $col * ($cellW + $colGap);
    $y = $pdf->GetY();

    if ($esFinal) {
        // Final: amplada completa
        $x = $xStart;
    }

    // Fons
    if ($completat) {
        $pdf->SetFillColor(212, 237, 218);
        $pdf->SetDrawColor(39, 174, 96);
    } else {
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetDrawColor(200, 200, 200);
    }
    $pdf->SetLineWidth(0.5);

    $w = $esFinal ? ($cellW * 2 + $colGap) : $cellW;
    $pdf->Rect($x, $y, $w, $cellH, 'FD');

    // Icona
    $icon = $completat ? ($esFinal ? 'TROFEU!' : 'OK') : 'O';
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->SetTextColor($completat ? 21 : 100, $completat ? 87 : 100, $completat ? 36 : 100);
    $pdf->SetXY($x + 2, $y + 2);
    $pdf->Cell($w - 4, 5, iconv('UTF-8', 'latin1//TRANSLIT', $p['nom']), 0, 0, 'L');

    if ($completat && $hora) {
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetXY($x + 2, $y + 8);
        $pdf->Cell($w - 4, 5, 'Pas: ' . $hora, 0, 0, 'L');
    } elseif ($completat) {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetXY($x + 2, $y + 8);
        $pdf->Cell($w - 4, 5, 'Completada', 0, 0, 'L');
    } else {
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetXY($x + 2, $y + 8);
        $pdf->SetTextColor(180, 180, 180);
        $pdf->Cell($w - 4, 5, 'Pendent', 0, 0, 'L');
    }

    if ($esFinal) {
        $pdf->SetY($y + $cellH + 3);
        $col = 0;
    } else {
        $col++;
        if ($col >= $cols) {
            $col = 0;
            $pdf->SetY($y + $cellH + 3);
        } else {
            $pdf->SetY($y);
        }
    }
}

// Peu de pàgina
$pdf->SetY(270);
$pdf->SetFillColor(44, 62, 80);
$pdf->Rect(0, 270, 210, 30, 'F');
$pdf->SetFont('Helvetica', 'B', 11);
$pdf->SetTextColor(241, 196, 15);
$pdf->Cell(0, 8, 'Som d\'esplai, res no ens atura!', 0, 1, 'C');
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(200, 200, 200);
$pdf->Cell(0, 6, 'esplaispait.com - Caminada Montserrat 2026', 0, 1, 'C');

// ============================================================
// PÀGINA 2: El Camí Interior (si hi ha tests)
// ============================================================
$checkins_amb_test = array_filter($user['checkins'] ?? [], fn($c) => !empty($c['test']));

if (!empty($checkins_amb_test)) {
    $pdf->AddPage();

    // Capçalera p2
    $pdf->SetFillColor(44, 62, 80);
    $pdf->Rect(0, 0, 210, 20, 'F');
    $pdf->SetY(5);
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 10, 'El teu Cami Interior', 0, 1, 'C');
    $pdf->SetY(25);

    $pdf->SetFont('Helvetica', 'I', 11);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(0, 7, iconv('UTF-8', 'latin1//TRANSLIT', $user['nom']) . ' - Caminada Montserrat 2026', 0, 1, 'C');
    $pdf->Ln(3);

    foreach ($checkins_amb_test as $ci) {
        $pid = $ci['parada_id'];
        $parada = $parades_map[$pid] ?? null;
        if (!$parada) continue;
        $hora = date('H:i', strtotime($ci['timestamp']));

        // Capçalera parada
        $pdf->SetFillColor(192, 57, 43);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 7, iconv('UTF-8', 'latin1//TRANSLIT', $parada['nom']) . ' - ' . $hora, 0, 1, 'L', true);
        $pdf->Ln(1);

        $test_def = $TESTS[$pid] ?? [];
        foreach ($ci['test'] as $key => $val) {
            if (empty($val)) continue;
            $pregunta = $test_def[$key]['pregunta'] ?? $key;
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetTextColor(44, 62, 80);
            $pdf->Cell(0, 5, iconv('UTF-8', 'latin1//TRANSLIT', $pregunta), 0, 1, 'L');
            $pdf->SetFont('Helvetica', 'I', 9);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->MultiCell(0, 5, '  ' . iconv('UTF-8', 'latin1//TRANSLIT', $val), 0, 'L');
            $pdf->Ln(1);
        }
        $pdf->Ln(3);
    }

    // Missatge final si ha arribat
    if (!empty($checkins_map[10]['test']['p3'])) {
        $msg_final = $checkins_map[10]['test']['p3'];
        $pdf->Ln(5);
        $pdf->SetFillColor(241, 196, 15);
        $pdf->SetDrawColor(192, 57, 43);
        $pdf->SetLineWidth(1);
        $pdf->SetFont('Helvetica', 'B', 12);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->MultiCell(0, 7, iconv('UTF-8', 'latin1//TRANSLIT', '"' . $msg_final . '"'), 'TB', 'C', true);
    }

    // Peu pàgina 2
    $pdf->SetY(270);
    $pdf->SetFillColor(44, 62, 80);
    $pdf->Rect(0, 270, 210, 30, 'F');
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->SetTextColor(241, 196, 15);
    $pdf->Cell(0, 8, 'HO HAS ACONSEGUIT! Som d\'esplai, res no ens atura! 🏔️', 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->Cell(0, 6, 'esplaispait.com', 0, 1, 'C');
}

// Nom fitxer
$nom_fitxer = 'cartilla_' . preg_replace('/[^a-z0-9]/', '_', strtolower($user['nom'])) . '_montserrat2026.pdf';

$pdf->Output('D', $nom_fitxer);
