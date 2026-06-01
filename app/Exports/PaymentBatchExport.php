<?php

namespace App\Exports;

use App\Models\PaymentBatch;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaymentBatchExport implements FromCollection, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    protected $batchId;

    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    public function collection()
    {
        return PaymentBatch::with([
            'items.employee',
            'items.bank',
            'items.timesheet'
        ])->findOrFail($this->batchId)
          ->items;
    }

    public function headings(): array
    {
        return [
            'Timesheet Period',
            'Agreement #',
            'Employee Name',
            'Role',
            'Start Date',
            'End Date',
            'Salary/Month',
            'Salary Basis',
            'Days Worked',
            'Total Payment',
            'IBAN',
            'Account Holder Name',
        ];
    }

    public function map($item): array
    {
        $employee = $item->employee;
        $timesheet = $item->timesheet;
        
        // Get employee event assignment dates (from timesheet's event)
        $assignment = $employee && $timesheet ? \DB::table('employee_events')
            ->where('employee_id', $employee->id)
            ->where('event_id', $timesheet->event_id)
            ->first() : null;

        // Get latest salary
        $latestSalary = $employee ? \App\Models\EmployeeSalary::where('employee_id', $employee->id)
            ->where('effective_start_date', '<=', now())
            ->orderBy('effective_start_date', 'desc')
            ->first() : null;

        // Determine salary basis
        $salaryBasis = 'N/A';
        if ($timesheet) {
            $daysInMonth = $timesheet->days_in_month ?: 30;
            $eligibleDays = $timesheet->total_days_eligible_for_payment;
            $salaryBasis = ($eligibleDays >= ($daysInMonth * 0.9)) ? 'Full Month' : 'Partial Month';
        }

        return [
            $timesheet?->timesheet_period ?? 'N/A',
            $item->agreement_number ?: 'N/A',
            $item->employee_name,
            $item->role ?: 'N/A',
            $assignment?->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d M Y') : 'N/A',
            $assignment?->released_at ? \Carbon\Carbon::parse($assignment->released_at)->format('d M Y') : 'Ongoing',
            $latestSalary?->net_salary ?? 0,
            $salaryBasis,
            $item->days_worked,
            $item->payment_amount,
            $item->account_number ?: 'N/A',
            $item->bank?->bank_account_name ?: 'N/A',
        ];
    }

    public function title(): string
    {
        $batch = PaymentBatch::find($this->batchId);
        return substr($batch->batch_number ?? 'Payment Batch', 0, 31); // Excel sheet name max 31 chars
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
