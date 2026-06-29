<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SalaryImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int $processedCount = 0;
    protected int $skippedCount = 0;
    protected int $updatedCount = 0;
    protected bool $treatDuplicatesAsError;
    protected array $customErrors = [];

    public function __construct(bool $treatDuplicatesAsError = false)
    {
        $this->treatDuplicatesAsError = $treatDuplicatesAsError;
    }

    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['employee_number']) && empty($row['net_salary'])) {
            $this->skippedCount++;
            return null;
        }

        // Find employee by employee number
        $employee = Employee::where('employee_number', $row['employee_number'])->first();
        
        if (!$employee) {
            // Track error and skip row (don't throw exception)
            $this->customErrors[] = [
                'row_data' => $row,
                'errors' => ["Employee with number {$row['employee_number']} not found"],
            ];
            // Don't increment skippedCount - this is an error, not a skip
            return null;
        }

        // Parse dates (support both Y-m-d and m-d-Y formats)
        $effectiveStartDate = !empty($row['effective_start_date']) 
            ? $this->parseDate($row['effective_start_date'])
            : Carbon::now()->format('Y-m-d');

        $effectiveEndDate = !empty($row['effective_end_date']) 
            ? $this->parseDate($row['effective_end_date'])
            : '9999-12-31';

        // Check for existing active salary records
        $existingActiveSalary = EmployeeSalary::where('employee_id', $employee->id)
            ->where('archived', 'N')
            ->where('effective_end_date', '9999-12-31')
            ->first();

        if ($existingActiveSalary && $this->treatDuplicatesAsError) {
            // Track error and skip row (don't throw exception)
            $this->customErrors[] = [
                'row_data' => $row,
                'errors' => ["Employee {$row['employee_number']} already has an active salary record. Current salary: {$existingActiveSalary->net_salary}, effective from " . Carbon::parse($existingActiveSalary->effective_start_date)->format('Y-m-d')],
            ];
            // Don't increment skippedCount - this is an error, not a skip
            return null;
        }

        // Auto-close existing open-ended salary records if flag is disabled (default behavior)
        if ($existingActiveSalary && !$this->treatDuplicatesAsError) {
            $closingDate = Carbon::parse($effectiveStartDate)->subDay()->format('Y-m-d');
            $existingActiveSalary->update(['effective_end_date' => $closingDate]);
        }

        $this->processedCount++;

        // Create new salary record
        return new EmployeeSalary([
            'employee_id' => $employee->id,
            'creator_id' => Auth::id(),
            'net_salary' => $row['net_salary'],
            'payroll_cycle_id' => null, // Optional field
            'effective_start_date' => $effectiveStartDate,
            'effective_end_date' => $effectiveEndDate,
            'archived' => 'N',
            'active_flag' => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_number' => 'required',
            'net_salary' => 'required|numeric|min:0',
            'effective_start_date' => 'nullable|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'employee_number.required' => 'Employee Number is required',
            'net_salary.required' => 'Net Salary is required',
            'net_salary.numeric' => 'Net Salary must be a number',
            'net_salary.min' => 'Net Salary must be at least 0',
            'effective_end_date.after_or_equal' => 'Effective End Date must be after or equal to Effective Start Date',
        ];
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getCustomErrors(): array
    {
        return $this->customErrors;
    }

    /**
     * Parse date string supporting multiple formats
     */
    protected function parseDate($dateString): string
    {
        if (empty($dateString)) {
            return '';
        }

        // Try Y-m-d format first (template format)
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateString)) {
            return Carbon::parse($dateString)->format('Y-m-d');
        }

        // Try m-d-Y format
        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $dateString)) {
            return Carbon::createFromFormat('m-d-Y', $dateString)->format('Y-m-d');
        }

        // Fall back to Carbon's parser
        return Carbon::parse($dateString)->format('Y-m-d');
    }
}
