<?php

namespace App\Exports;

use App\Models\EmployeeBank;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SelectedBanksExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(private array $bankIds) {}

    public function collection()
    {
        return EmployeeBank::with('employee')
            ->whereIn('id', $this->bankIds)
            ->where('archived', 'N')
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Employee Name',
            'Bank Branch Name',
            'IBAN',
            'Swift Code',
            'Effective Start Date',
            'Effective End Date',
            'Status',
        ];
    }

    public function map($bank): array
    {
        $endDate = $bank->effective_end_date === '9999-12-31' ? '' : $bank->effective_end_date;
        return [
            $bank->employee?->employee_number,
            $bank->employee?->full_name,
            $bank->bank_branch_name,
            $bank->iban,
            $bank->swift_code,
            $bank->effective_start_date,
            $endDate,
            $bank->is_active ? 'Active' : 'Inactive',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '059669']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 26, 'C' => 30, 'D' => 34, 'E' => 16, 'F' => 18, 'G' => 16, 'H' => 10];
    }
}
