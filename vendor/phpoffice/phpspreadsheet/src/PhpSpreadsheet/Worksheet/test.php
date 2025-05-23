<?php
require 'vendor/autoload.php';

use setasign\Fpdi\Tcpdf\Fpdi;

// Create a new FPDI instance
$pdf = new Fpdi();

// Set equal left and right margins (in millimeters)
$leftMargin = 15; // Adjust as needed
$rightMargin = 15; // Adjust as needed
$topMargin = 15; // Adjust as needed
$bottomMargin = 15; // Adjust as needed

$pdf->SetMargins($leftMargin, $topMargin, $rightMargin);
$pdf->SetAutoPageBreak(true, $bottomMargin);

// Add a page
$pdf->AddPage();

// Set the source PDF file
$pdf->setSourceFile('path/to/your/template.pdf');

// Import the first page of the template
$templateId = $pdf->importPage(1);
$pdf->useTemplate($templateId);

// Set font and add text
$pdf->SetFont('helvetica', '', 12);
$pdf->SetXY($leftMargin, 50); // Set position (X = left margin, Y = 50mm)
$pdf->Write(0, 'Hello, World!');

// Output the PDF
$pdf->Output('output.pdf', 'I');
?>