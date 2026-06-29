<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SalaryTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        // Return sample data
        return [
            [
                'EMP001',
                'John Smith',
                '5000.00',
                date('Y-m-d'),
                '9999-12-31',
            ],
            [
                'EMP002',
                'Jane Doe',
                '7500.00',
                date('Y-m-d'),
                '9999-12-31',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'employee_number',
            'employee_name',
            'net_salary',
            'effective_start_date',
            'effective_end_date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // employee_number
            'B' => 25, // employee_name
            'C' => 15, // net_salary
            'D' => 20, // effective_start_date
            'E' => 20, // effective_end_date
        ];
    }
}
