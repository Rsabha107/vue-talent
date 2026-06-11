<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class FailedEmployeesExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
            'Event Name',
            'First Name',
            'Middle Name',
            'Last Name',
            'Work Email',
            'Personal Email',
            'Phone Number',
            'Phone Area Code',
            'Alt Phone Number',
            'Alt Area Code',
            'Employee Number',
            'Agreement Number',
            'National ID',
            'Salutation',
            'Gender',
            'Marital Status',
            'Designation',
            'Department',
            'Directorate',
            'Functional Area',
            'Job Level',
            'Entity',
            'Contract Type',
            'Employee Type',
            'Salary Basis',
            'Contract Start Date',
            'Contract End Date',
            'Date of Birth',
            'Town of Birth',
            'Country of Birth',
            'Date of Hire',
            'Join Date',
            'Sponsorship Type',
            'Sponsorship Name',
            'Nationality',
            'Passport Number',
            'Passport Expiry',
            'Civil ID Expiry',
            'Manager Flag',
            'Admin Flag',
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
                'font' => ['color' => ['rgb' => 'DC2626']],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50, // Error column wider
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 25,
            'F' => 25,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 18,
            'K' => 15,
            'L' => 12,
            'M' => 12,
            'N' => 15,
            'O' => 20,
            'P' => 15,
            'Q' => 15,
            'R' => 18,
            'S' => 18,
            'T' => 18,
            'U' => 15,
            'V' => 15,
            'W' => 15,
            'X' => 18,
            'Y' => 15,
            'Z' => 18,
            'AA' => 15,
            'AB' => 15,
            'AC' => 12,
            'AD' => 12,
        ];
    }
}
