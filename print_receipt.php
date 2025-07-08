<?php
// print_receipt.php
// Generates a 100×200 mm PDF of just the overlay text—no background image—so you can print onto your hard-copy form.

require __DIR__ . '/config.php';        // DB_HOST, DB_NAME, DB_USER, DB_PASS
require __DIR__ . '/tcpdf/tcpdf.php';   // TCPDF library

// 1) Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die('BAD REQUEST');
}

// 2) Gather inputs
$citation_id      = filter_input(INPUT_POST, 'citation_id', FILTER_VALIDATE_INT);
$print_date       = $_POST['date']            ?? '';
$received_methods = $_POST['received']        ?? [];

if (!$citation_id) {
    http_response_code(400);
    die('INVALID CITATION ID');
}

// 3) Date helper
function fmtDate(string $d): string {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt ? strtoupper($dt->format('m/d/Y')) : strtoupper($d);
}

// 3.5) Number→words converter
function convertNumber(int $num): string {
    static $ones = ['ZERO','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE','TEN','ELEVEN','TWELVE','THIRTEEN','FOURTEEN','FIFTEEN','SIXTEEN','SEVENTEEN','EIGHTEEN','NINETEEN'];
    static $tens = [2=>'TWENTY',3=>'THIRTY',4=>'FORTY',5=>'FIFTY',6=>'SIXTY',7=>'SEVENTY',8=>'EIGHTY',9=>'NINETY'];
    if ($num < 20) return $ones[$num];
    if ($num < 100) {
        $t = intdiv($num,10); $r = $num % 10;
        return $tens[$t] . ($r ? ' '.$ones[$r] : '');
    }
    if ($num < 1000) {
        $h = intdiv($num,100); $r = $num % 100;
        return $ones[$h].' HUNDRED'.($r ? ' '.convertNumber($r):'');
    }
    foreach ([1000000000=>'BILLION',1000000=>'MILLION',1000=>'THOUSAND'] as $div=>$label) {
        if ($num >= $div) {
            $cnt = intdiv($num,$div);
            $rem = $num % $div;
            return convertNumber($cnt).' '.$label.($rem ? ' '.convertNumber($rem):'');
        }
    }
    return '';
}

try {
    // 4) Connect
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
    );

    // 5) Fetch Payor (driver)
    $payorStmt = $pdo->prepare("
        SELECT CONCAT(UPPER(d.first_name),' ',UPPER(d.last_name)) AS payor
        FROM citations c
        LEFT JOIN drivers d ON c.driver_id=d.driver_id
        WHERE c.citation_id=:cid
    ");
    $payorStmt->execute([':cid'=>$citation_id]);
    $payor = $payorStmt->fetchColumn() ?: '';

    // 6) Fetch violations + fines
    $violStmt = $pdo->prepare("
        SELECT UPPER(vl.violation_type) AS violation_type,
               COALESCE(
                 CASE vl.offense_count
                   WHEN 1 THEN vt.fine_amount_1
                   WHEN 2 THEN vt.fine_amount_2
                   WHEN 3 THEN vt.fine_amount_3
                   ELSE vt.fine_amount_1
                 END, vt.fine_amount_1
               ) AS fine
        FROM violations vl
        LEFT JOIN violation_types vt
          ON UPPER(vl.violation_type)=UPPER(vt.violation_type)
        WHERE vl.citation_id = :cid
    ");
    $violStmt->execute([':cid'=>$citation_id]);
    $rows = $violStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        throw new Exception("NO VIOLATIONS FOUND");
    }
} catch (Exception $e) {
    die('DATABASE ERROR: '.strtoupper($e->getMessage()));
}

// 7) Build lines & total
$violation_lines = [];
$total = 0.0;
foreach ($rows as $r) {
    $fine = (float)$r['fine'];
    $total += $fine;
    $violation_lines[] = $r['violation_type'];
}

// 7.5) Amount in words
$integer  = (int)floor($total);
$centavos = (int)round(($total - $integer)*100);
$words    = convertNumber($integer).' PESOS';
if ($centavos) $words .= ' AND '.convertNumber($centavos).' CENTAVOS';
$amount_in_words = $words.' ONLY';

// 8) Initialize TCPDF (100×200 mm)
$pdf = new TCPDF('P','mm',[100,200], true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0,0,0);
$pdf->AddPage();

// 9) **DO NOT** draw resibo.png so we print only text on your pre-printed form
 $pdf->Image(__DIR__.'/resibo.png', 0, 0, 100, 200);

// 10) Set font to DejaVu Sans BOLD, size 8
$pdf->SetFont('dejavusans','',8);

// 11) Print DATE
$pdf->SetXY(6.6, 49.4);
$pdf->Cell(30, 4, fmtDate($_POST['date']), 0, 0, 'L', false);

// 12) Print PAYOR
$pdf->SetXY(7.1, 65.0);
$pdf->Cell(85, 6, $payor, 0, 0, 'L', false);

// 13) Print violations + fines
$startX    = 5.1;   $amountX = 62.0;
$startY    = 79.2;  $rowH    = 8.0;
foreach ($violation_lines as $i => $label) {
    if ($i>=10) break;
    $y = $startY + $i*$rowH;
    $pdf->SetXY($startX, $y);
    $pdf->Cell(60, 6, $label, 0, 0, 'L', false);
    $amt = (float)$rows[$i]['fine'];
    $pdf->SetXY($amountX, $y);
    $pdf->Cell(20,6,'₱'.number_format($amt,2),0,0,'R',false);
}

// 14) Print TOTAL at exact coords
$pdf->SetXY(10.6,128.2);
$pdf->Cell(60,6,'TOTAL:',0,0,'L',false);
$pdf->SetXY(61.9,128.7);
$pdf->Cell(20,6,'₱'.number_format($total,2),0,0,'R',false);

// 15) Amount in Words
$pdf->SetXY(7.1,140.7);
$pdf->MultiCell(85,4,$amount_in_words,0,'L',false);

// 16) Mark CASH checkbox
if(in_array('Cash',$received_methods,true)){
  $pdf->SetXY(20.9,150.6);
  $pdf->Cell(4,4,'X',0,0,'L',false);
}

// 17) Output inline
$pdf->Output('receipt_overlay.pdf','I');
exit;
