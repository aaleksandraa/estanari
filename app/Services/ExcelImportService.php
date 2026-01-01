<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelImportService
{
    public static function readFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = [];
        
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $rowData[] = trim((string) $cell->getValue());
            }
            
            // Skip completely empty rows
            if (array_filter($rowData)) {
                $data[] = $rowData;
            }
        }
        
        return $data;
    }

    public static function generateSupplierTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dobavljači');

        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1D4ED8']]],
        ];

        // Headers
        $headers = ['Naziv dobavljača *', 'Email', 'Telefon', 'Adresa', 'Poslovnica 1', 'Poslovnica 2', 'Poslovnica 3', 'Poslovnica 4', 'Poslovnica 5'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Example data
        $exampleData = [
            ['Primjer Dobavljač d.o.o.', 'info@primjer.ba', '+387 33 123 456', 'Ulica 1, Sarajevo', 'Poslovnica Centar', 'Poslovnica Ilidža', '', '', ''],
            ['Drugi Dobavljač', 'kontakt@drugi.ba', '', 'Banja Luka', 'Glavna poslovnica', '', '', '', ''],
        ];

        $row = 2;
        foreach ($exampleData as $data) {
            $col = 'A';
            foreach ($data as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Style example rows
        $sheet->getStyle('A2:I3')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'font' => ['italic' => true, 'color' => ['rgb' => '92400E']],
        ]);

        // Instructions
        $sheet->setCellValue('A5', 'UPUTE:');
        $sheet->setCellValue('A6', '• Polje "Naziv dobavljača" je obavezno (označeno sa *)');
        $sheet->setCellValue('A7', '• Možete dodati do 5 poslovnica po dobavljaču');
        $sheet->setCellValue('A8', '• Obrišite primjere prije importa');
        $sheet->setCellValue('A9', '• Žuti redovi su primjeri - obrišite ih');
        
        $sheet->getStyle('A5')->getFont()->setBold(true);
        $sheet->getStyle('A5:A9')->getFont()->setSize(10);

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'sablon_dobavljaci.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public static function parseSupplierData(array $rows): array
    {
        $suppliers = [];
        $errors = [];
        
        // Skip header row
        $dataRows = array_slice($rows, 1);
        
        foreach ($dataRows as $index => $row) {
            $rowNum = $index + 2; // Excel row number (1-indexed + header)
            
            // Get supplier name (first column)
            $name = $row[0] ?? '';
            
            if (empty($name)) {
                continue; // Skip empty rows
            }
            
            // Skip example/instruction rows
            if (str_contains(strtolower($name), 'upute') || str_contains(strtolower($name), 'primjer')) {
                continue;
            }
            
            $supplier = [
                'name' => $name,
                'email' => $row[1] ?? null,
                'phone' => $row[2] ?? null,
                'address' => $row[3] ?? null,
                'branches' => [],
            ];
            
            // Validate email if provided
            if (!empty($supplier['email']) && !filter_var($supplier['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Red {$rowNum}: Neispravan email format '{$supplier['email']}'";
                $supplier['email'] = null;
            }
            
            // Get branches (columns 5-9, index 4-8)
            for ($i = 4; $i <= 8; $i++) {
                $branchName = $row[$i] ?? '';
                if (!empty($branchName)) {
                    $supplier['branches'][] = ['name' => $branchName];
                }
            }
            
            $suppliers[] = $supplier;
        }
        
        return [
            'suppliers' => $suppliers,
            'errors' => $errors,
        ];
    }
}
