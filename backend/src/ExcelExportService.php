<?php

declare(strict_types=1);

namespace Backend;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * ExcelExportService
 * Generates professional Excel reports with headers, logos, signature placeholders, and charts
 * 
 * Each report has:
 * - Sheet 1: Company header (name + logo), data table, summary, signature placeholder
 * - Sheet 2: Charts/graphs of the report data
 */
final class ExcelExportService
{
    private Spreadsheet $spreadsheet;
    private string $companyName = 'E-Library System';
    private string $companySubtitle = '';
    private string $reportGeneratedBy;

    public function __construct(string $reportGeneratedBy = 'System Administrator')
    {
        $this->spreadsheet = new Spreadsheet();
        $this->reportGeneratedBy = $reportGeneratedBy;
    }

    // ========== PUBLIC REPORT GENERATORS ==========

    public function generateBorrowingReport(array $data, array $summary): string
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Borrowing Report');

        $row = 1;
        $row = $this->addReportHeader($sheet, $row, 'Book Borrowing Transactions Report');
        $row += 1;

        // Summary section
        $this->addSummarySection($sheet, $row, [
            'Total Borrowings' => $summary['total'] ?? 0,
            'Active Borrowings' => $summary['active'] ?? 0,
            'Overdue Borrowings' => $summary['overdue'] ?? 0,
            'Returned' => $summary['returned'] ?? 0,
            'Total Estimated Fines' => '₱' . number_format($summary['totalEstimatedFines'] ?? 0, 2),
        ]);
        $row += 7;

        // Data headers
        $headers = ['Transaction ID','Member Name','Email','Book Title','ISBN','Category','Borrow Date','Due Date','Return Date','Status','Days Overdue','Estimated Fine'];
        $this->addTableHeaders($sheet, $row, $headers);
        $row++;

        foreach ($data as $record) {
            $col = 'A';
            foreach (['TransactionID','MemberName','MemberEmail','BookTitle','ISBN','Category_Name','BorrowDate','DueDate','ReturnDate','Status','DaysOverdue','EstimatedFine'] as $key) {
                $sheet->setCellValue("{$col}{$row}", $record[$key] ?? '');
                $col++;
            }
            $this->formatDataRow($sheet, $row, 'L');
            $row++;
        }

        $row += 2;
        $this->addSignaturePlaceholder($sheet, $row);

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Sheet 2: Charts
        $this->createBorrowingChartSheet($summary, $data);

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->saveAndGetPath('borrowing_report_' . date('Y-m-d_H-i-s'));
    }

    public function generateReturnReport(array $data, array $summary): string
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Return Report');

        $row = 1;
        $row = $this->addReportHeader($sheet, $row, 'Book Return Transactions Report');
        $row += 1;

        $this->addSummarySection($sheet, $row, [
            'Total Returns' => $summary['total'] ?? 0,
            'Good Condition' => $summary['good'] ?? 0,
            'Minor Damage' => $summary['minorDamage'] ?? 0,
            'Major Damage' => $summary['majorDamage'] ?? 0,
            'Lost Books' => $summary['lost'] ?? 0,
            'Total Late Fees' => '₱' . number_format($summary['totalLateFees'] ?? 0, 2),
        ]);
        $row += 8;

        $headers = ['Return ID','Member Name','Email','Book Title','ISBN','Return Date','Condition','Days Overdue','Late Fee','Processed By','Notes'];
        $this->addTableHeaders($sheet, $row, $headers);
        $row++;

        foreach ($data as $record) {
            $col = 'A';
            foreach (['ReturnTransactionID','MemberName','MemberEmail','BookTitle','ISBN','ReturnDate','BookCondition','DaysOverdue','LateFee','LibrarianName','Notes'] as $key) {
                $sheet->setCellValue("{$col}{$row}", $record[$key] ?? '');
                $col++;
            }
            $this->formatDataRow($sheet, $row, 'K');
            $row++;
        }

        $row += 2;
        $this->addSignaturePlaceholder($sheet, $row);

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->createReturnChartSheet($summary);

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->saveAndGetPath('return_report_' . date('Y-m-d_H-i-s'));
    }

    public function generateInventoryReport(array $header, array $data, array $summary): string
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Inventory Report');

        $row = 1;
        $row = $this->addReportHeader($sheet, $row, 'Physical Inventory Count Report');
        $row += 1;

        // Count metadata
        $sheet->setCellValue("A{$row}", 'Count Date:');
        $sheet->setCellValue("B{$row}", $header['CountDate'] ?? '');
        $sheet->setCellValue("D{$row}", 'Status:');
        $sheet->setCellValue("E{$row}", $header['CountStatus'] ?? '');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", 'Conducted By:');
        $sheet->setCellValue("B{$row}", $header['ConductedByName'] ?? '');
        $sheet->setCellValue("D{$row}", 'Verified By:');
        $sheet->setCellValue("E{$row}", $header['VerifiedByName'] ?? 'Not verified');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $row += 2;

        $this->addSummarySection($sheet, $row, [
            'Total Books Expected' => $header['TotalBooksExpected'] ?? 0,
            'Total Books Found' => $header['TotalBooksFound'] ?? 0,
            'Discrepancies' => $header['Discrepancies'] ?? 0,
            'Perfect Matches' => $summary['perfectMatch'] ?? 0,
            'Items with Variance' => $summary['discrepancies'] ?? 0,
        ]);
        $row += 7;

        $headers = ['Book ID','Book Title','ISBN','Author','Category','Expected Qty','Physical Qty','Variance','Notes'];
        $this->addTableHeaders($sheet, $row, $headers);
        $row++;

        foreach ($data as $record) {
            $variance = ($record['PhysicalQuantity'] ?? 0) - ($record['ExpectedQuantity'] ?? 0);
            $sheet->setCellValue("A{$row}", $record['BookID'] ?? '');
            $sheet->setCellValue("B{$row}", $record['BookTitle'] ?? '');
            $sheet->setCellValue("C{$row}", $record['ISBN'] ?? '');
            $sheet->setCellValue("D{$row}", $record['Author'] ?? '');
            $sheet->setCellValue("E{$row}", $record['Category_Name'] ?? '');
            $sheet->setCellValue("F{$row}", $record['ExpectedQuantity'] ?? 0);
            $sheet->setCellValue("G{$row}", $record['PhysicalQuantity'] ?? 0);
            $sheet->setCellValue("H{$row}", $variance);
            $sheet->setCellValue("I{$row}", $record['Notes'] ?? '');

            if ($variance != 0) {
                $sheet->getStyle("H{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFFF00');
            }
            $this->formatDataRow($sheet, $row, 'I');
            $row++;
        }

        $row += 2;
        $this->addSignaturePlaceholder($sheet, $row);

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $this->createInventoryChartSheet($summary);

        $this->spreadsheet->setActiveSheetIndex(0);
        return $this->saveAndGetPath('inventory_report_' . date('Y-m-d_H-i-s'));
    }

    // ========== HEADER / FOOTER HELPERS ==========

    private function addReportHeader(&$sheet, int $row, string $title): int
    {
        // Try to add logo
        $logoPath = __DIR__ . '/../../assets/logo.png';
        if (file_exists($logoPath)) {
            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(50);
            $drawing->setCoordinates("A{$row}");
            $drawing->setWorksheet($sheet);
            $sheet->getRowDimension($row)->setRowHeight(50);
        }

        // Company name
        $sheet->setCellValue("B{$row}", $this->companyName);
        $sheet->getStyle("B{$row}")->getFont()->setSize(18)->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->mergeCells("B{$row}:L{$row}");
        $row++;

        // Subtitle
        $sheet->setCellValue("A{$row}", $this->companySubtitle);
        $sheet->getStyle("A{$row}")->getFont()->setSize(11)->setItalic(true)->setColor(new Color('FFFFFF'));
        $sheet->getStyle("A{$row}:L{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1D4ED8');
        $sheet->mergeCells("A{$row}:L{$row}");
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Report title
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getStyle("A{$row}")->getFont()->setSize(14)->setBold(true);
        $sheet->mergeCells("A{$row}:L{$row}");
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        // Generated info
        $sheet->setCellValue("A{$row}", 'Generated: ' . date('Y-m-d H:i:s') . ' | By: ' . $this->reportGeneratedBy);
        $sheet->getStyle("A{$row}")->getFont()->setSize(10)->setItalic(true);
        $sheet->mergeCells("A{$row}:L{$row}");
        $row++;

        return $row;
    }

    private function addSummarySection(&$sheet, int $row, array $items): void
    {
        $sheet->setCellValue("A{$row}", 'SUMMARY');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);
        $sheet->mergeCells("A{$row}:D{$row}");
        $row++;

        foreach ($items as $label => $value) {
            $sheet->setCellValue("A{$row}", $label . ':');
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $sheet->getStyle("A{$row}:B{$row}")->getFont()->setSize(11);
            $row++;
        }
    }

    private function addTableHeaders(&$sheet, int $row, array $headers): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
            $sheet->getStyle("{$col}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $col++;
        }
    }

    private function formatDataRow(&$sheet, int $row, string $lastCol = 'L'): void
    {
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    }

    private function addSignaturePlaceholder(&$sheet, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Prepared By:');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", '');
        $row++;
        $sheet->setCellValue("A{$row}", '______________________________');
        $row++;
        $sheet->setCellValue("A{$row}", $this->reportGeneratedBy);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $verifyRow = $row - 3;
        $sheet->setCellValue("D{$verifyRow}", 'Verified By:');
        $sheet->getStyle("D{$verifyRow}")->getFont()->setBold(true);
        $verifyRow += 2;
        $sheet->setCellValue("D{$verifyRow}", '______________________________');
        $verifyRow++;
        $sheet->setCellValue("D{$verifyRow}", 'Librarian Name / Signature');

        $dateRow = $row - 3;
        $sheet->setCellValue("G{$dateRow}", 'Date:');
        $sheet->getStyle("G{$dateRow}")->getFont()->setBold(true);
        $sheet->setCellValue("H{$dateRow}", date('F j, Y'));
    }

    // ========== CHART SHEET BUILDERS (Sheet 2) ==========

    private function createBorrowingChartSheet(array $summary, array $data): void
    {
        $chartSheet = $this->spreadsheet->createSheet();
        $chartSheet->setTitle('Borrowing Analysis');

        // Status distribution data
        $chartSheet->setCellValue('A1', 'Status');
        $chartSheet->setCellValue('B1', 'Count');
        $chartSheet->setCellValue('A2', 'Active');
        $chartSheet->setCellValue('B2', $summary['active'] ?? 0);
        $chartSheet->setCellValue('A3', 'Overdue');
        $chartSheet->setCellValue('B3', $summary['overdue'] ?? 0);
        $chartSheet->setCellValue('A4', 'Returned');
        $chartSheet->setCellValue('B4', $summary['returned'] ?? 0);

        // Style headers
        $chartSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $chartSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $chartSheet->getStyle('A1:B1')->getFont()->setColor(new Color('FFFFFF'));

        // Build pie chart
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Borrowing Analysis'!\$A\$2:\$A\$4", null, 3)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Borrowing Analysis'!\$B\$2:\$B\$4", null, 3)];

        $series = new DataSeries(DataSeries::TYPE_PIECHART, null, range(0, 0), [], $categories, $values);
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title('Borrowing Status Distribution');

        $chart = new Chart('statusChart', $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition('A6');
        $chart->setBottomRightPosition('H22');
        $chartSheet->addChart($chart);

        // Category analysis data
        $categoryData = [];
        foreach ($data as $record) {
            $cat = $record['Category_Name'] ?? 'Unknown';
            $categoryData[$cat] = ($categoryData[$cat] ?? 0) + 1;
        }
        arsort($categoryData);
        $categoryData = array_slice($categoryData, 0, 10, true);

        $r = 25;
        $chartSheet->setCellValue("A{$r}", 'Category');
        $chartSheet->setCellValue("B{$r}", 'Borrows');
        $chartSheet->getStyle("A{$r}:B{$r}")->getFont()->setBold(true);
        $r++;
        $startRow = $r;
        foreach ($categoryData as $cat => $count) {
            $chartSheet->setCellValue("A{$r}", $cat);
            $chartSheet->setCellValue("B{$r}", $count);
            $r++;
        }
        $endRow = $r - 1;

        if ($endRow >= $startRow) {
            $catLabels = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Borrowing Analysis'!\$A\${$startRow}:\$A\${$endRow}", null, $endRow - $startRow + 1)];
            $catValues = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Borrowing Analysis'!\$B\${$startRow}:\$B\${$endRow}", null, $endRow - $startRow + 1)];

            $barSeries = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, range(0, 0), [], $catLabels, $catValues);
            $barPlot = new PlotArea(null, [$barSeries]);
            $barLegend = new Legend(Legend::POSITION_BOTTOM, null, false);
            $barTitle = new Title('Top Categories by Borrowing');

            $barChart = new Chart('categoryChart', $barTitle, $barLegend, $barPlot);
            $barChart->setTopLeftPosition('A' . ($endRow + 2));
            $barChart->setBottomRightPosition('H' . ($endRow + 18));
            $chartSheet->addChart($barChart);
        }
    }

    private function createReturnChartSheet(array $summary): void
    {
        $chartSheet = $this->spreadsheet->createSheet();
        $chartSheet->setTitle('Return Analysis');

        $chartSheet->setCellValue('A1', 'Condition');
        $chartSheet->setCellValue('B1', 'Count');
        $chartSheet->setCellValue('A2', 'Good');
        $chartSheet->setCellValue('B2', $summary['good'] ?? 0);
        $chartSheet->setCellValue('A3', 'Minor Damage');
        $chartSheet->setCellValue('B3', $summary['minorDamage'] ?? 0);
        $chartSheet->setCellValue('A4', 'Major Damage');
        $chartSheet->setCellValue('B4', $summary['majorDamage'] ?? 0);
        $chartSheet->setCellValue('A5', 'Lost');
        $chartSheet->setCellValue('B5', $summary['lost'] ?? 0);

        $chartSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $chartSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $chartSheet->getStyle('A1:B1')->getFont()->setColor(new Color('FFFFFF'));

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Return Analysis'!\$A\$2:\$A\$5", null, 4)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Return Analysis'!\$B\$2:\$B\$5", null, 4)];

        $series = new DataSeries(DataSeries::TYPE_PIECHART, null, range(0, 0), [], $categories, $values);
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title('Book Condition Distribution');

        $chart = new Chart('conditionChart', $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition('A7');
        $chart->setBottomRightPosition('H22');
        $chartSheet->addChart($chart);
    }

    private function createInventoryChartSheet(array $summary): void
    {
        $chartSheet = $this->spreadsheet->createSheet();
        $chartSheet->setTitle('Inventory Analysis');

        $chartSheet->setCellValue('A1', 'Result');
        $chartSheet->setCellValue('B1', 'Count');
        $chartSheet->setCellValue('A2', 'Perfect Match');
        $chartSheet->setCellValue('B2', $summary['perfectMatch'] ?? 0);
        $chartSheet->setCellValue('A3', 'Discrepancy');
        $chartSheet->setCellValue('B3', $summary['discrepancies'] ?? 0);

        $chartSheet->getStyle('A1:B1')->getFont()->setBold(true);
        $chartSheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $chartSheet->getStyle('A1:B1')->getFont()->setColor(new Color('FFFFFF'));

        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Inventory Analysis'!\$A\$2:\$A\$3", null, 2)];
        $values = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Inventory Analysis'!\$B\$2:\$B\$3", null, 2)];

        $series = new DataSeries(DataSeries::TYPE_PIECHART, null, range(0, 0), [], $categories, $values);
        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chartTitle = new Title('Inventory Count Accuracy');

        $chart = new Chart('inventoryChart', $chartTitle, $legend, $plotArea);
        $chart->setTopLeftPosition('A5');
        $chart->setBottomRightPosition('H20');
        $chartSheet->addChart($chart);
    }

    // ========== FILE OUTPUT ==========

    private function saveAndGetPath(string $filename): string
    {
        $reportsDir = __DIR__ . '/../reports';
        @mkdir($reportsDir, 0755, true);

        $filepath = $reportsDir . '/' . $filename . '.xlsx';

        $writer = new Xlsx($this->spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($filepath);

        return $filepath;
    }
}
