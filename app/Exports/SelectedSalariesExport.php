<?php

namespace App\Exports;

use App\Models\EmployeeSalary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SelectedSalariesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function __construct(private array $salaryIds) {}

    public function collection()
    {
        return EmployeeSalary::with('employee')
            ->whereIn('id', $this->salaryIds)
            ->orderBy('employee_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Employee #',
            'Employee Name',
            'Net Salary (QAR)',
            'Effective Start Date',
            'Effective End Date',
            'Status',
        ];
    }

    public function map($salary): array
    {
        $endDate = $salary->effective_end_date === '9999-12-31' ? '' : $salary->effective_end_date;
        return [
            $salary->employee?->employee_number,
            $salary->employee?->full_name,
            $salary->net_salary,
            $salary->effective_start_date,
            $endDate,
            $salary->isActive() ? 'Active' : 'Inactive',
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
        return ['A' => 14, 'B' => 26, 'C' => 20, 'D' => 20, 'E' => 18, 'F' => 10];
    }
}
