<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FailedSalariesExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected array $failedRows;

    public function __construct(array $failedRows)
    {
        $this->failedRows = $failedRows;
    }

    public function array(): array
    {
        $data = [];
        
        foreach ($this->failedRows as $failure) {
            $row = $failure['values'];
            $errors = implode(' | ', $failure['errors']);
            
            // Add error column at the beginning
            $dataRow = array_merge(['❌ ' . $errors], array_values($row));
            $data[] = $dataRow;
        }
        
        return $data;
    }

    public function headings(): array
    {
        return [
            'ERRORS (Fix these issues)',
            'Employee Number',
            'Employee Name',
            'Net Salary',
            'Effective Start Date',
            'Effective End Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row - red
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DC2626'],
                ],
            ],
            // Error column - light red background
            'A' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'],
                ],
                'font' => ['bold' => true, 'color' => ['rgb' => 'DC2626']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 60, // Error column - wide
            'B' => 18, // Employee Number
            'C' => 25, // Employee Name
            'D' => 15, // Net Salary
            'E' => 20, // Effective Start Date
            'F' => 20, // Effective End Date
        ];
    }
}
