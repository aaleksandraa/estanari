<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Supplier;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Reports');
    }

    public function daily(Request $request): StreamedResponse
    {
        $date = $request->get('date', today()->toDateString());
        $formattedDate = date('d.m.Y', strtotime($date));
        
        $payments = Payment::with(['supplier:id,name', 'branch:id,name'])
            ->whereDate('planned_date', $date)
            ->orderBy('supplier_id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dnevni izvještaj');

        // Title
        $sheet->setCellValue('A1', "DNEVNI IZVJEŠTAJ - {$formattedDate}");
        $sheet->mergeCells('A1:G1');
        $this->applyTitleStyle($sheet, 'A1:G1');

        // Summary row
        $totalKM = $payments->where('currency', 'KM')->sum('amount');
        $totalEUR = $payments->where('currency', 'EUR')->sum('amount');
        $totalUSD = $payments->where('currency', 'USD')->sum('amount');
        
        $sheet->setCellValue('A2', "Ukupno KM: " . number_format($totalKM, 2, ',', '.'));
        $sheet->setCellValue('C2', "Ukupno EUR: " . number_format($totalEUR, 2, ',', '.'));
        $sheet->setCellValue('E2', "Ukupno USD: " . number_format($totalUSD, 2, ',', '.'));
        $sheet->setCellValue('G2', "Broj plaćanja: " . $payments->count());
        $this->applySummaryStyle($sheet, 'A2:G2');

        // Headers
        $headers = ['Br. fakture', 'Dobavljač', 'Poslovnica', 'Iznos', 'Valuta', 'Status', 'Opis'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A4:G4');

        // Data
        $row = 5;
        foreach ($payments as $payment) {
            $sheet->setCellValue('A' . $row, $payment->invoice_number ?? '-');
            $sheet->setCellValue('B' . $row, $payment->supplier->name ?? '-');
            $sheet->setCellValue('C' . $row, $payment->branch->name ?? '-');
            $sheet->setCellValue('D' . $row, $payment->amount);
            $sheet->setCellValue('E' . $row, $payment->currency);
            $sheet->setCellValue('F' . $row, $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno');
            $sheet->setCellValue('G' . $row, $payment->description ?? '');
            
            $this->applyDataRowStyle($sheet, "A{$row}:G{$row}", $row);
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'G');

        return $this->downloadExcel($spreadsheet, "dnevni_izvjestaj_{$formattedDate}.xlsx");
    }

    public function monthly(Request $request): StreamedResponse
    {
        $month = $request->get('month', now()->format('Y-m'));
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        $formattedMonth = date('m/Y', strtotime($startDate));
        
        $payments = Payment::with(['supplier:id,name'])
            ->whereBetween('planned_date', [$startDate, $endDate])
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mjesečni izvještaj');

        // Title
        $sheet->setCellValue('A1', "MJESEČNI IZVJEŠTAJ - {$formattedMonth}");
        $sheet->mergeCells('A1:G1');
        $this->applyTitleStyle($sheet, 'A1:G1');

        // Summary
        $totalKM = $payments->where('currency', 'KM')->sum('amount');
        $totalEUR = $payments->where('currency', 'EUR')->sum('amount');
        $totalUSD = $payments->where('currency', 'USD')->sum('amount');
        
        $sheet->setCellValue('A2', "Ukupno KM: " . number_format($totalKM, 2, ',', '.'));
        $sheet->setCellValue('C2', "Ukupno EUR: " . number_format($totalEUR, 2, ',', '.'));
        $sheet->setCellValue('E2', "Ukupno USD: " . number_format($totalUSD, 2, ',', '.'));
        $sheet->setCellValue('G2', "Ukupno: " . $payments->count() . " plaćanja");
        $this->applySummaryStyle($sheet, 'A2:G2');

        // Headers
        $headers = ['Datum', 'Broj plaćanja', 'Ukupno KM', 'Ukupno EUR', 'Ukupno USD', 'Plaćeno', 'Neplaceno'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A4:G4');

        // Data by date
        $byDate = $payments->groupBy(fn($p) => $p->planned_date->format('Y-m-d'));
        $row = 5;
        
        foreach ($byDate->sortKeys() as $date => $dayPayments) {
            $sheet->setCellValue('A' . $row, date('d.m.Y', strtotime($date)));
            $sheet->setCellValue('B' . $row, $dayPayments->count());
            $sheet->setCellValue('C' . $row, $dayPayments->where('currency', 'KM')->sum('amount'));
            $sheet->setCellValue('D' . $row, $dayPayments->where('currency', 'EUR')->sum('amount'));
            $sheet->setCellValue('E' . $row, $dayPayments->where('currency', 'USD')->sum('amount'));
            $sheet->setCellValue('F' . $row, $dayPayments->where('status', 'PAID')->count());
            $sheet->setCellValue('G' . $row, $dayPayments->where('status', 'PLANNED')->count());
            
            $this->applyDataRowStyle($sheet, "A{$row}:G{$row}", $row);
            $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Totals row
        $sheet->setCellValue('A' . $row, 'UKUPNO');
        $sheet->setCellValue('B' . $row, $payments->count());
        $sheet->setCellValue('C' . $row, $totalKM);
        $sheet->setCellValue('D' . $row, $totalEUR);
        $sheet->setCellValue('E' . $row, $totalUSD);
        $sheet->setCellValue('F' . $row, $payments->where('status', 'PAID')->count());
        $sheet->setCellValue('G' . $row, $payments->where('status', 'PLANNED')->count());
        $this->applyTotalsStyle($sheet, "A{$row}:G{$row}");

        $this->autoSizeColumns($sheet, 'A', 'G');

        return $this->downloadExcel($spreadsheet, "mjesecni_izvjestaj_{$month}.xlsx");
    }

    public function bySupplier(): StreamedResponse
    {
        $suppliers = Supplier::with(['payments'])->orderBy('name')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Po dobavljačima');

        // Title
        $sheet->setCellValue('A1', "IZVJEŠTAJ PO DOBAVLJAČIMA - " . date('d.m.Y'));
        $sheet->mergeCells('A1:I1');
        $this->applyTitleStyle($sheet, 'A1:I1');

        // Headers
        $headers = ['Dobavljač', 'Ukupno plaćanja', 'Ukupno KM', 'Ukupno EUR', 'Ukupno USD', 'Plaćeno KM', 'Plaćeno EUR', 'Planirano KM', 'Planirano EUR'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A3:I3');

        // Data
        $row = 4;
        $grandTotalKM = 0;
        $grandTotalEUR = 0;
        $grandTotalUSD = 0;
        
        foreach ($suppliers as $supplier) {
            $payments = $supplier->payments;
            $kmTotal = $payments->where('currency', 'KM')->sum('amount');
            $eurTotal = $payments->where('currency', 'EUR')->sum('amount');
            $usdTotal = $payments->where('currency', 'USD')->sum('amount');
            
            $grandTotalKM += $kmTotal;
            $grandTotalEUR += $eurTotal;
            $grandTotalUSD += $usdTotal;
            
            $sheet->setCellValue('A' . $row, $supplier->name);
            $sheet->setCellValue('B' . $row, $payments->count());
            $sheet->setCellValue('C' . $row, $kmTotal);
            $sheet->setCellValue('D' . $row, $eurTotal);
            $sheet->setCellValue('E' . $row, $usdTotal);
            $sheet->setCellValue('F' . $row, $payments->where('currency', 'KM')->where('status', 'PAID')->sum('amount'));
            $sheet->setCellValue('G' . $row, $payments->where('currency', 'EUR')->where('status', 'PAID')->sum('amount'));
            $sheet->setCellValue('H' . $row, $payments->where('currency', 'KM')->where('status', 'PLANNED')->sum('amount'));
            $sheet->setCellValue('I' . $row, $payments->where('currency', 'EUR')->where('status', 'PLANNED')->sum('amount'));
            
            $this->applyDataRowStyle($sheet, "A{$row}:I{$row}", $row);
            $sheet->getStyle("C{$row}:I{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        // Totals
        $sheet->setCellValue('A' . $row, 'UKUPNO');
        $sheet->setCellValue('C' . $row, $grandTotalKM);
        $sheet->setCellValue('D' . $row, $grandTotalEUR);
        $sheet->setCellValue('E' . $row, $grandTotalUSD);
        $this->applyTotalsStyle($sheet, "A{$row}:I{$row}");

        $this->autoSizeColumns($sheet, 'A', 'I');

        return $this->downloadExcel($spreadsheet, "izvjestaj_po_dobavljacima_" . date('d-m-Y') . ".xlsx");
    }

    public function byCurrency(): StreamedResponse
    {
        $payments = Payment::with(['supplier:id,name'])->orderBy('planned_date')->get();

        $spreadsheet = new Spreadsheet();
        
        // KM Sheet
        $kmSheet = $spreadsheet->getActiveSheet();
        $kmSheet->setTitle('KM');
        $kmPayments = $payments->where('currency', 'KM');
        $this->createCurrencySheet($kmSheet, $kmPayments, 'KONVERTIBILNA MARKA (KM)', '3B82F6');

        // EUR Sheet
        $eurSheet = $spreadsheet->createSheet();
        $eurSheet->setTitle('EUR');
        $eurPayments = $payments->where('currency', 'EUR');
        $this->createCurrencySheet($eurSheet, $eurPayments, 'EURO (EUR)', '10B981');

        // USD Sheet
        $usdSheet = $spreadsheet->createSheet();
        $usdSheet->setTitle('USD');
        $usdPayments = $payments->where('currency', 'USD');
        $this->createCurrencySheet($usdSheet, $usdPayments, 'AMERIČKI DOLAR (USD)', '9333EA');

        // Summary Sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Pregled');
        $this->createSummarySheet($summarySheet, $payments);

        $spreadsheet->setActiveSheetIndex(3); // Set Summary as active

        return $this->downloadExcel($spreadsheet, "izvjestaj_po_valutama_" . date('d-m-Y') . ".xlsx");
    }

    private function createCurrencySheet($sheet, $payments, $title, $color): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $total = $payments->sum('amount');
        $paidTotal = $payments->where('status', 'PAID')->sum('amount');
        $plannedTotal = $payments->where('status', 'PLANNED')->sum('amount');
        
        $sheet->setCellValue('A2', "Ukupno: " . number_format($total, 2, ',', '.'));
        $sheet->setCellValue('C2', "Plaćeno: " . number_format($paidTotal, 2, ',', '.'));
        $sheet->setCellValue('E2', "Planirano: " . number_format($plannedTotal, 2, ',', '.'));
        $this->applySummaryStyle($sheet, 'A2:E2');

        $headers = ['Br. fakture', 'Dobavljač', 'Iznos', 'Status', 'Datum'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A4:E4');

        $row = 5;
        foreach ($payments as $payment) {
            $sheet->setCellValue('A' . $row, $payment->invoice_number ?? '-');
            $sheet->setCellValue('B' . $row, $payment->supplier->name ?? '-');
            $sheet->setCellValue('C' . $row, $payment->amount);
            $sheet->setCellValue('D' . $row, $payment->status === 'PAID' ? 'Plaćeno' : 'Neplaceno');
            $sheet->setCellValue('E' . $row, $payment->planned_date->format('d.m.Y'));
            
            $this->applyDataRowStyle($sheet, "A{$row}:E{$row}", $row);
            $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'E');
    }

    private function createSummarySheet($sheet, $payments): void
    {
        $sheet->setCellValue('A1', 'PREGLED PO VALUTAMA - ' . date('d.m.Y'));
        $sheet->mergeCells('A1:D1');
        $this->applyTitleStyle($sheet, 'A1:D1');

        $headers = ['Valuta', 'Ukupno', 'Plaćeno', 'Neplaceno'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '3', $header);
            $col++;
        }
        $this->applyHeaderStyle($sheet, 'A3:D3');

        $currencies = ['KM' => '3B82F6', 'EUR' => '10B981', 'USD' => '9333EA'];
        $row = 4;
        $grandTotal = 0;
        
        foreach ($currencies as $currency => $color) {
            $currencyPayments = $payments->where('currency', $currency);
            $total = $currencyPayments->sum('amount');
            $grandTotal += $total;
            
            $sheet->setCellValue('A' . $row, $currency);
            $sheet->setCellValue('B' . $row, $total);
            $sheet->setCellValue('C' . $row, $currencyPayments->where('status', 'PAID')->sum('amount'));
            $sheet->setCellValue('D' . $row, $currencyPayments->where('status', 'PLANNED')->sum('amount'));
            
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
            $this->applyDataRowStyle($sheet, "A{$row}:D{$row}", $row);
            $sheet->getStyle("B{$row}:D{$row}")->getNumberFormat()->setFormatCode('#,##0.00');
            $row++;
        }

        $this->autoSizeColumns($sheet, 'A', 'D');
    }

    // ========== HELPER METHODS ==========

    private function applyTitleStyle($sheet, $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(35);
    }

    private function applySummaryStyle($sheet, $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(2)->setRowHeight(25);
    }

    private function applyHeaderStyle($sheet, $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ]);
        $row = (int) preg_replace('/[^0-9]/', '', explode(':', $range)[0]);
        $sheet->getRowDimension($row)->setRowHeight(25);
    }

    private function applyDataRowStyle($sheet, $range, $row): void
    {
        $bgColor = ($row % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
        $sheet->getStyle($range)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function applyTotalsStyle($sheet, $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B82F6']]],
        ]);
        $sheet->getStyle($range)->getNumberFormat()->setFormatCode('#,##0.00');
    }

    private function autoSizeColumns($sheet, $startCol, $endCol): void
    {
        foreach (range($startCol, $endCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function downloadExcel(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
