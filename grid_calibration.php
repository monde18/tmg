<?php
// grid_calibration.php
require __DIR__ . '/fpdf.php';

// Create a 100×200 mm page
$pdf = new FPDF('P','mm',[100,200]);
$pdf->AddPage();

// Light-gray grid every 5 mm
$pdf->SetDrawColor(200,200,200);
for ($x = 0; $x <= 100; $x += 5) {
    $pdf->Line($x, 0, $x, 200);
}
for ($y = 0; $y <= 200; $y += 5) {
    $pdf->Line(0, $y, 100, $y);
}

// Label every 10 mm for clarity
$pdf->SetFont('Arial','',6);
$pdf->SetTextColor(50,50,50);
for ($x = 0; $x <= 100; $x += 10) {
    $pdf->Text($x + 0.5, 3, "{$x} mm");
}
for ($y = 0; $y <= 200; $y += 10) {
    $pdf->Text(1, $y + 2, "{$y} mm");
}

// Darken axes at 0,0
$pdf->SetDrawColor(0,0,0);
$pdf->SetLineWidth(0.3);
$pdf->Line(0,0,100,0);
$pdf->Line(0,0,0,200);

// Output inline
$pdf->Output('I','grid_calibration_10x20cm.pdf');
