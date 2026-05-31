<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Directorate;
use App\Models\FunctionalArea;
use App\Models\EmployeeEntity;
use App\Models\EmployeeContractType;
use App\Models\EmployeeType;
use App\Models\SalaryBasis;
use App\Models\Ems\Event;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Mode 3: Event Assignment Import (Existing Employees)
 * Bulk assigns existing employees to events with organizational attributes
 */
class EventAssignmentImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int $successCount = 0;
    protected int $skipCount = 0;
    protected int $updateCount = 0;
    protected array $errors = [];

    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['employee_number']) && empty($row['work_email'])) {
            $this->skipCount++;
            return null;
        }

        try {
            DB::beginTransaction();

            // Find employee by employee_number or work_email
            $employee = null;
            if (!empty($row['employee_number'])) {
                $employee = Employee::where('employee_number', $row['employee_number'])->first();
            }
            if (!$employee && !empty($row['work_email'])) {
                $employee = Employee::where('work_email_address', $row['work_email'])->first();
            }

            if (!$employee) {
                $this->errors[] = "Row: Employee not found (Number: {$row['employee_number']}, Email: {$row['work_email']})";
                $this->skipCount++;
                DB::rollBack();
                return null;
            }

            // Find event
            $event = Event::where('name', $row['event_name'])->first();
            if (!$event) {
                $this->errors[] = "Row: Event '{$row['event_name']}' not found for employee {$employee->employee_number}";
                $this->skipCount++;
                DB::rollBack();
                return null;
            }

            // Find or create related models
            $department = !empty($row['department']) 
                ? Department::firstOrCreate(
                    ['name' => $row['department']],
                    ['active_flag' => 1, 'creator_id' => 1]
                )
                : null;
            
            $designation = !empty($row['designation']) 
                ? Designation::firstOrCreate(
                    ['name' => $row['designation']],
                    ['active_flag' => 1, 'creator_id' => 1]
                )
                : null;

            $directorate = null;
            if (!empty($row['directorate'])) {
                try {
                    $directorate = Directorate::firstOrCreate(
                        ['title' => $row['directorate']],
                        ['created_by' => 1, 'updated_by' => 1]
                    );
                } catch (\Exception $e) {
                    // Skip if table doesn't exist
                }
            }

            $functionalArea = !empty($row['functional_area']) 
                ? FunctionalArea::firstOrCreate(
                    ['title' => $row['functional_area']],
                    ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
                )
                : null;

            $entity = !empty($row['entity']) 
                ? EmployeeEntity::firstOrCreate(
                    ['title' => $row['entity']],
                    ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
                )
                : null;

            $contractType = !empty($row['contract_type']) 
                ? EmployeeContractType::firstOrCreate(
                    ['title' => $row['contract_type']],
                    ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
                )
                : null;

            $salaryBasis = !empty($row['salary_basis']) 
                ? SalaryBasis::firstOrCreate(
                    ['title' => $row['salary_basis']],
                    ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
                )
                : null;

            $employeeType = !empty($row['employee_type']) 
                ? EmployeeType::firstOrCreate(
                    ['title' => $row['employee_type']],
                    ['active_flag' => 1]
                )
                : null;

            // Find manager/reporting_to by employee number or email
            $reportingTo = null;
            if (!empty($row['manager_employee_number'])) {
                $reportingTo = Employee::where('employee_number', $row['manager_employee_number'])->first()?->id;
            } elseif (!empty($row['manager_email'])) {
                $reportingTo = Employee::where('work_email_address', $row['manager_email'])->first()?->id;
            }

            // Prepare pivot data
            $pivotData = [
                'department_id' => $department?->id,
                'designation_id' => $designation?->id,
                'directorate_id' => $directorate?->id,
                'functional_area_id' => $functionalArea?->id,
                'entity_id' => $entity?->id,
                'contract_type_id' => $contractType?->id,
                'salary_basis_id' => $salaryBasis?->id,
                'reporting_to_id' => $reportingTo,
                'agreement_number' => $row['agreement_number'] ?? null,
                'employee_type' => $employeeType?->id,
                'assigned_at' => $this->parseDate($row['assigned_at'] ?? null),
                'released_at' => $this->parseDate($row['released_at'] ?? null),
                'is_active' => !empty($row['released_at']) && Carbon::parse($row['released_at'])->isPast() ? 0 : 1,
            ];

            // Check if assignment already exists
            $existingAssignment = $employee->events()->where('events.id', $event->id)->exists();

            if ($existingAssignment) {
                // Update existing assignment
                $employee->events()->updateExistingPivot($event->id, $pivotData);
                $this->updateCount++;
            } else {
                // Create new assignment
                $employee->events()->attach($event->id, $pivotData);
                $this->successCount++;
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->errors[] = "Row: {$e->getMessage()}";
            $this->skipCount++;
        }

        return null; // We're not creating new models, just updating relationships
    }

    public function rules(): array
    {
        return [
            'event_name' => 'required|string|max:255',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'event_name.required' => 'Event name is required',
        ];
    }

    protected function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Handle Excel serial date
            if (is_numeric($date)) {
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')->addDays($date);
            }
            
            // Try to parse as date string
            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getUpdateCount(): int
    {
        return $this->updateCount;
    }

    public function getFailureCount(): int
    {
        return count($this->failures());
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getStats(): array
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updateCount,
            'failed' => count($this->failures()),
            'skipped' => $this->skipCount,
            'total' => $this->successCount + $this->updateCount + count($this->failures()) + $this->skipCount,
        ];
    }
}
