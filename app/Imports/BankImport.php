<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\EmployeeBank;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class BankImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int   $processedCount = 0;
    protected int   $skippedCount   = 0;
    protected bool  $treatDuplicatesAsError;
    protected array $customErrors   = [];

    public function __construct(bool $treatDuplicatesAsError = false)
    {
        $this->treatDuplicatesAsError = $treatDuplicatesAsError;
    }

    public function model(array $row)
    {
        // Skip fully empty rows
        if (empty($row['employee_number']) && empty($row['iban'])) {
            $this->skippedCount++;
            return null;
        }

        // Resolve employee
        $employee = Employee::where('employee_number', $row['employee_number'])->first();
        if (!$employee) {
            $this->customErrors[] = [
                'row_data' => $row,
                'errors'   => ["Employee '{$row['employee_number']}' not found"],
            ];
            return null;
        }

        // Default missing effective_start_date to 2026-01-01
        $effectiveStartDate = !empty($row['effective_start_date'])
            ? $this->parseDate($row['effective_start_date'])
            : '2026-01-01';

        // Check IBAN uniqueness (non-archived)
        $ibanExists = EmployeeBank::where('iban', $row['iban'])
            ->where('archived', 'N')
            ->exists();

        if ($ibanExists) {
            $this->customErrors[] = [
                'row_data' => $row,
                'errors'   => ["IBAN '{$row['iban']}' is already assigned to another active bank record"],
            ];
            return null;
        }

        // Handle existing active (open-ended) bank records
        $existingActive = EmployeeBank::where('employee_id', $employee->id)
            ->where('archived', 'N')
            ->where('effective_end_date', '9999-12-31')
            ->first();

        if ($existingActive && $this->treatDuplicatesAsError) {
            $this->customErrors[] = [
                'row_data' => $row,
                'errors'   => [
                    "Employee {$row['employee_number']} already has an active bank record "
                    . "(IBAN: {$existingActive->iban}, from "
                    . Carbon::parse($existingActive->effective_start_date)->format('Y-m-d') . ')'
                ],
            ];
            return null;
        }

        // Auto date-track: close the existing open-ended record
        if ($existingActive) {
            $closingDate = Carbon::parse($effectiveStartDate)->subDay()->format('Y-m-d');
            $existingActive->update(['effective_end_date' => $closingDate]);
        }

        $this->processedCount++;

        return new EmployeeBank([
            'employee_id'          => $employee->id,
            'user_id'              => Auth::id(),
            'bank_branch_name'     => $row['bank_branch_name'],
            'iban'                 => $row['iban'],
            'swift_code'           => $row['swift_code'] ?? '',
            'effective_start_date' => $effectiveStartDate,
            'effective_end_date'   => '9999-12-31',
            'archived'             => 'N',
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_number'      => 'required',
            'iban'                 => 'required|max:34',
            'bank_branch_name'     => 'required',
            'effective_start_date' => 'nullable|date',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'employee_number.required'  => 'Employee Number is required',
            'iban.required'             => 'IBAN is required',
            'iban.max'                  => 'IBAN must not exceed 34 characters',
            'bank_branch_name.required' => 'Bank Branch Name is required',
        ];
    }

    public function getProcessedCount(): int  { return $this->processedCount; }
    public function getSkippedCount(): int    { return $this->skippedCount; }
    public function getCustomErrors(): array  { return $this->customErrors; }

    protected function parseDate(string $dateString): string
    {
        if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $dateString)) {
            return Carbon::parse($dateString)->format('Y-m-d');
        }
        if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $dateString)) {
            return Carbon::createFromFormat('m-d-Y', $dateString)->format('Y-m-d');
        }
        return Carbon::parse($dateString)->format('Y-m-d');
    }
}
