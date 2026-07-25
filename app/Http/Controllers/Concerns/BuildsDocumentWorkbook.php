<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared Excel document frame: brand letterhead, section helpers, and page footer,
 * mirroring the printed documents rendered by the `x-print-page` component.
 */
trait BuildsDocumentWorkbook
{
    private const INK = '0F172A';

    private const SLATE = '1E293B';

    private const SOFT = '64748B';

    private const MUTED = '94A3B8';

    private const RULE = 'CBD5E1';

    private const WASH = 'F8FAFC';

    /**
     * Open a workbook with the company letterhead already drawn.
     *
     * @param  array<string, int>  $columnWidths  Column letter => width, in order.
     * @return array{0: Spreadsheet, 1: Worksheet, 2: int} Spreadsheet, sheet, and the next free row.
     */
    protected function startDocumentWorkbook(string $heading, string $documentTitle, array $columnWidths): array
    {
        $companyName = Setting::get('company_name') ?: Setting::get('site_name', 'BNoor Group');
        $tagline = Setting::get('company_tagline');
        $registrationNo = Setting::get('company_registration_no');
        $logo = Setting::get('site_logo');

        $lastColumn = (string) array_key_last($columnWidths);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator($companyName)
            ->setTitle($documentTitle)
            ->setSubject($documentTitle);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($heading);
        $sheet->setShowGridlines(false);

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension((string) $column)->setWidth($width);
        }

        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.5)->setRight(0.5);

        // Heading sits in the right-hand columns that together give it room to breathe.
        $headingStart = $this->columnSpanningWidth($columnWidths, 34);
        $sheet->getRowDimension(1)->setRowHeight(50);
        $sheet->mergeCells("{$headingStart}1:{$lastColumn}2");
        $sheet->setCellValue("{$headingStart}1", mb_strtoupper($heading));
        $sheet->getStyle("{$headingStart}1")->getFont()->setBold(true)->setSize(22)->getColor()->setRGB(self::SLATE);
        $sheet->getStyle("{$headingStart}1")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($logo) {
            $logoPath = Storage::disk('public')->path($logo);

            if (is_file($logoPath)) {
                $drawing = new Drawing;
                $drawing->setPath($logoPath);
                $drawing->setHeight(44);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(2);
                $drawing->setOffsetY(4);
                $drawing->setWorksheet($sheet);
            }
        }

        if ($tagline) {
            $sheet->mergeCells('A2:B2');
            $sheet->setCellValue('A2', $tagline);
            $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB(self::MUTED);
            $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(18);
        }

        $sheet->mergeCells("A3:{$lastColumn}3");
        $sheet->setCellValue('A3', mb_strtoupper($companyName));
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('0A0A0A');
        $sheet->getRowDimension(3)->setRowHeight(22);

        $row = 4;

        if ($registrationNo) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", 'Reg. No. '.$registrationNo);
            $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB(self::MUTED);
            $row++;
        }

        $sheet->getStyle('A'.($row - 1).":{$lastColumn}".($row - 1))->getBorders()->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::SLATE);

        return [$spreadsheet, $sheet, $row + 1];
    }

    /**
     * Close the document with the optional footer note, office addresses, and brand bar.
     */
    protected function finishDocumentWorkbook(Worksheet $sheet, int $row, string $lastColumn): void
    {
        $footerNote = Setting::get('invoice_footer_note');
        $website = Setting::get('company_website');
        $siteEmail = Setting::get('site_email');
        $chinaAddress = Setting::get('china_office_address');
        $chinaContact = Setting::get('china_office_contact');
        $dhakaAddress = Setting::get('dhaka_office_address');
        $dhakaContact = Setting::get('dhaka_office_contact');

        if ($footerNote) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValue("A{$row}", $footerNote);
            $sheet->getStyle("A{$row}")->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB(self::MUTED);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($footerNote, 110));
            $row += 2;
        }

        $officeLines = array_values(array_filter([
            $chinaAddress ? 'China Office: '.$chinaAddress.($chinaContact ? ' · '.$chinaContact : '') : null,
            $dhakaAddress ? 'Dhaka Office: '.$dhakaAddress.($dhakaContact ? ' · '.$dhakaContact : '') : null,
            implode('    ', array_filter([$website, $siteEmail])) ?: null,
        ]));

        if ($officeLines !== []) {
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getBorders()->getTop()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::RULE);

            foreach ($officeLines as $line) {
                $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
                $sheet->setCellValue("A{$row}", $line);
                $sheet->getStyle("A{$row}")->getFont()->setSize(9)->getColor()->setRGB(self::SOFT);
                $sheet->getStyle("A{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight($line, 105, 12.0));
                $row++;
            }
        }

        $sheet->getRowDimension($row)->setRowHeight(7);
        $brandBar = $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill();
        $brandBar->setFillType(Fill::FILL_GRADIENT_LINEAR);
        $brandBar->setRotation(0);
        $brandBar->getStartColor()->setRGB('8DC63F');
        $brandBar->getEndColor()->setRGB('27AAE1');

        $sheet->setSelectedCell('A1');
    }

    /**
     * Small uppercase section label, matching the printed document's section headings.
     */
    protected function documentSectionLabel(Worksheet $sheet, string $cell, string $text): void
    {
        $sheet->setCellValue($cell, $text);
        $sheet->getStyle($cell)->getFont()->setBold(true)->setSize(9)->getColor()->setRGB(self::MUTED);
    }

    /**
     * Dark table header band across the given columns.
     *
     * @param  array<int, string>  $labels
     */
    protected function documentTableHeader(Worksheet $sheet, int $row, array $labels, string $firstColumn = 'A'): void
    {
        $startIndex = Coordinate::columnIndexFromString($firstColumn);
        $lastColumn = Coordinate::stringFromColumnIndex($startIndex + count($labels) - 1);

        foreach (array_values($labels) as $index => $label) {
            $sheet->setCellValue([$startIndex + $index, $row], $label);
        }

        $range = "{$firstColumn}{$row}:{$lastColumn}{$row}";
        $sheet->getStyle($range)->getFont()->setBold(true)->setSize(10)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::SLATE);
        $sheet->getStyle($range)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    /**
     * Right-aligned label/value pair used by the document meta column.
     */
    protected function documentMetaPair(Worksheet $sheet, int $row, string $labelColumn, string $valueColumn, string $label, string $value): void
    {
        $sheet->setCellValue("{$labelColumn}{$row}", $label);
        $sheet->getStyle("{$labelColumn}{$row}")->getFont()->setSize(10)->getColor()->setRGB(self::SOFT);
        $sheet->getStyle("{$labelColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->setCellValueExplicit("{$valueColumn}{$row}", $value, DataType::TYPE_STRING);
        $sheet->getStyle("{$valueColumn}{$row}")->getFont()->setBold(true)->setSize(10)->getColor()->setRGB(self::INK);
        $sheet->getStyle("{$valueColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }

    /**
     * Signing space, rule, signatory name, designation, and the "For {company}" line.
     *
     * @return int The next free row.
     */
    protected function documentSignature(Worksheet $sheet, int $row, string $firstColumn, string $lastColumn, ?string $name, ?string $designation, string $forName): int
    {
        $row += 2;
        $range = fn (int $line): string => "{$firstColumn}{$line}:{$lastColumn}{$line}";

        $sheet->getStyle($range($row))->getBorders()->getTop()
            ->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB(self::SLATE);

        $sheet->mergeCells($range($row));
        $sheet->setCellValue("{$firstColumn}{$row}", $name ?: 'Authorized Signature');
        $sheet->getStyle("{$firstColumn}{$row}")->getFont()->setBold((bool) $name)->setSize(11)->getColor()->setRGB(self::SLATE);
        $sheet->getStyle("{$firstColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        if ($name && $designation) {
            $sheet->mergeCells($range($row));
            $sheet->setCellValue("{$firstColumn}{$row}", $designation);
            $sheet->getStyle("{$firstColumn}{$row}")->getFont()->setSize(9)->getColor()->setRGB(self::SOFT);
            $sheet->getStyle("{$firstColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        $sheet->mergeCells($range($row));
        $sheet->setCellValue("{$firstColumn}{$row}", 'For '.$forName);
        $sheet->getStyle("{$firstColumn}{$row}")->getFont()->setSize(9)->getColor()->setRGB(self::MUTED);
        $sheet->getStyle("{$firstColumn}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight($this->wrappedRowHeight('For '.$forName, 38));

        return $row + 2;
    }

    /**
     * Stream the workbook to the browser as an .xlsx download.
     */
    protected function streamWorkbook(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);

        return new StreamedResponse(function () use ($writer): void {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0, must-revalidate',
        ]);
    }

    /**
     * Estimate a row height that fits wrapped text, since Excel cannot auto-fit merged cells.
     */
    protected function wrappedRowHeight(?string $text, int $charactersPerLine, float $lineHeight = 14.0, float $minimum = 16.0): float
    {
        if (! $text) {
            return $minimum;
        }

        $lines = 0;

        foreach (preg_split('/\R/', $text) ?: [] as $paragraph) {
            $lines += max(1, (int) ceil(mb_strlen($paragraph) / $charactersPerLine));
        }

        return max($minimum, $lines * $lineHeight);
    }

    /**
     * The left-most column whose span to the last column covers at least $width characters.
     *
     * @param  array<string, int>  $columnWidths
     */
    private function columnSpanningWidth(array $columnWidths, int $width): string
    {
        $columns = array_keys($columnWidths);
        $running = 0;

        foreach (array_reverse($columns) as $column) {
            $running += $columnWidths[$column];

            if ($running >= $width) {
                return (string) $column;
            }
        }

        return (string) ($columns[0] ?? 'A');
    }
}
