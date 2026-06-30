<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class BankTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'EMP001',
                'John Smith',
                'KW74NBOK0000000000001000372151',
                'Kuwait National Bank - Salmiya Branch',
                '2026-01-01',
            ],
            [
                'EMP002',
                'Jane Doe',
                'KW81CBKU0000000000001234560101',
                'Commercial Bank - Head Office',
                '2026-01-01',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'employee_number',
            'employee_name',
            'IBAN *',
            'BANK BRANCH NAME *',
            'EFFECTIVE START DATE',
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
            'A' => 18, // employee_number
            'B' => 25, // employee_name
            'C' => 34, // IBAN
            'D' => 32, // bank branch name
            'E' => 20, // effective start date
        ];
    }
}
