<?php

namespace PhpOffice\PhpSpreadsheet\Writer\Pdf;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;

class Tcpdf extends Pdf
{
    public function __construct(Spreadsheet $spreadsheet)
    {
        parent::__construct($spreadsheet);
        $this->setUseInlineCss(true);
    }

    protected function createExternalWriterInstance(string $orientation, string $unit, $paperSize): \TCPDF
    {
        return new \TCPDF($orientation, $unit, $paperSize, true, 'UTF-8', false);
    }

    public function save($filename, int $flags = 0): void
    {
        $fileHandle = parent::prepareForSave($filename);

        $sheet = $this->spreadsheet->getSheet($this->getSheetIndex() ?? 0);
        $setup = $sheet->getPageSetup();
        $margins = $sheet->getPageMargins();

        // Get orientation and paper size
        $orientation = $this->getOrientation() ?? $setup->getOrientation();
        $orientation = ($orientation === PageSetup::ORIENTATION_LANDSCAPE) ? 'L' : 'P';
        $printPaperSize = $this->getPaperSize() ?? $setup->getPaperSize();
        $paperSize = self::$paperSizes[$printPaperSize] ?? PageSetup::getPaperSizeDefault();

        // Create PDF
        $pdf = $this->createExternalWriterInstance($orientation, 'pt', $paperSize);
        $pdf->setFontSubsetting(true);

        // Disable auto-page-break (to force single page)
        $pdf->SetAutoPageBreak(false);

        // Set margins (convert inches to points)
        $pdf->SetMargins(
            $margins->getLeft() * 72,
            $margins->getTop() * 72,
            $margins->getRight() * 72
        );

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();

        // Generate HTML
        $html = $this->generateHTMLAll();
        $html = $this->cleanHtml($html);
        $html = $this->addBaseCss($html);

        // Write HTML (force fit to page)
        $pdf->SetFont($this->getFont(), '', 10); // Smaller font if needed
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, false, true, 'L', true);

        // Set document properties
        $props = $this->spreadsheet->getProperties();
        $pdf->SetTitle($props->getTitle());
        $pdf->SetAuthor($props->getCreator());
        $pdf->SetSubject($props->getSubject());
        $pdf->SetKeywords($props->getKeywords());
        $pdf->SetCreator($props->getCreator());

        fwrite($fileHandle, $pdf->output('', 'S'));
        parent::restoreStateAfterSave();
    }

    protected function cleanHtml(string $html): string
    {
        // Remove empty table rows/cells
        $html = preg_replace('/<tr[^>]*>\s*<td[^>]*>\s*<\/td>\s*<\/tr>/', '', $html);
        $html = preg_replace('/<tr[^>]*>\s*<\/tr>/', '', $html);
        $html = preg_replace('/\s+/', ' ', $html);
        return trim($html);
    }

    protected function addBaseCss(string $html): string
    {
        $css = '
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                table-layout: fixed;
            }
            td, th {
                padding: 2px;
                overflow: hidden;
                word-wrap: break-word;
                font-size: 10pt; /* Adjust if needed */
            }
            .text-center { text-align: center; }
            .text-left { text-align: left; }
            .text-right { text-align: right; }
        </style>';
        return $css . $html;
    }
}