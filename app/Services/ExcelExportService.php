<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExportService
{
    private Spreadsheet $spreadsheet;
    private $sheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    public function setTitle(string $title, string $subtitle = '', int $colSpan = 6): self
    {
        $lastCol = $this->getColumnLetter($colSpan);
        
        // Title
        $this->sheet->setCellValue('A1', $title);
        $this->sheet->mergeCells("A1:{$lastCol}1");
        $this->sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '3B82F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $this->sheet->getRowDimension(1)->setRowHeight(30);

        // Subtitle
        if ($subtitle) {
            $this->sheet->setCellValue('A2', $subtitle);
            $this->sheet->mergeCells("A2:{$lastCol}2");
            $this->sheet->getStyle('A2')->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '64748B']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        return $this;
    }

    public function setSummaryRow(array $summaries, int $startRow, int $colSpan = 6): self
    {
        $lastCol = $this->getColumnLetter($colSpan);
        $col = 1;
        
        foreach ($summaries as $label => $value) {
            $colLetter = $this->getColumnLetter($col);
            $this->sheet->setCellValue("{$colLetter}{$startRow}", "{$label}: {$value}");
            $this->sheet->getStyle("{$colLetter}{$startRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0F2FE']],
            ]);
            $col++;
        }

        return $this;
    }

    public function setHeaders(array $headers, int $row = 4): self
    {
        $col = 1;
        foreach ($headers as $header) {
            $colLetter = $this->getColumnLetter($col);
            $this->sheet->setCellValue("{$colLetter}{$row}", $header);
            $col++;
        }

        $lastCol = $this->getColumnLetter(count($headers));
        $this->sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '475569']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
        ]);
        $this->sheet->getRowDimension($row)->setRowHeight(25);

        return $this;
    }

    public function setData(array $data, int $startRow = 5, array $columnTypes = []): self
    {
        $row = $startRow;
        foreach ($data as $rowData) {
            $col = 1;
            foreach ($rowData as $key => $value) {
                $colLetter = $this->getColumnLetter($col);
                $this->sheet->setCellValue("{$colLetter}{$row}", $value);
                
                // Apply number format for amount columns
                if (isset($columnTypes[$key]) && $columnTypes[$key] === 'currency') {
                    $this->sheet->getStyle("{$colLetter}{$row}")->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
                
                $col++;
            }
            
            // Alternate row colors
            $lastCol = $this->getColumnLetter(count($rowData));
            $bgColor = ($row % 2 === 0) ? 'F8FAFC' : 'FFFFFF';
            $this->sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            
            $row++;
        }

        return $this;
    }

    public function setTotalsRow(array $totals, int $row, int $colSpan = 6): self
    {
        $lastCol = $this->getColumnLetter($colSpan);
        $col = 1;
        
        foreach ($totals as $value) {
            $colLetter = $this->getColumnLetter($col);
            $this->sheet->setCellValue("{$colLetter}{$row}", $value);
            $col++;
        }

        $this->sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '3B82F6']]],
        ]);

        return $this;
    }

    public function autoSizeColumns(int $count): self
    {
        for ($i = 1; $i <= $count; $i++) {
            $colLetter = $this->getColumnLetter($i);
            $this->sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
        return $this;
    }

    public function setColumnWidth(int $col, float $width): self
    {
        $colLetter = $this->getColumnLetter($col);
        $this->sheet->getColumnDimension($colLetter)->setWidth($width);
        return $this;
    }

    public function download(string $filename): StreamedResponse
    {
        $writer = new Xlsx($this->spreadsheet);
        
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function getColumnLetter(int $col): string
    {
        $letter = '';
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $col = (int)(($col - $mod) / 26);
        }
        return $letter;
    }

    public function getSheet()
    {
        return $this->sheet;
    }

    public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadsheet;
    }
}
