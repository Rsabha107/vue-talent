<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Ems\Event;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Salutation;
use App\Models\Directorate;
use App\Models\FunctionalArea;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\Country;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeeEntity;
use App\Models\EmployeeContractType;
use App\Models\EmployeeType;
use App\Models\SalaryBasis;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int $processedCount = 0;
    protected int $skipCount = 0;
    protected array $errors = [];

    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['first_name']) && empty($row['last_name']) && empty($row['work_email'])) {
            $this->skipCount++;
            return null;
        }

        // Find event if event_name is provided (Mode 2: Employee + Event Assignment)
        $event = null;
        if (!empty($row['event_name'])) {
            $event = Event::where('name', $row['event_name'])->first();
            if (!$event) {
                $this->errors[] = "Row skipped: Event '{$row['event_name']}' not found";
                $this->skipCount++;
                return null;
            }
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
        
        $salutation = !empty($row['salutation']) 
            ? Salutation::firstOrCreate(
                ['title' => $row['salutation']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;
        
        // Directorate (table may not exist in all databases)
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

        $employeeType = !empty($row['employee_type']) 
            ? EmployeeType::firstOrCreate(
                ['title' => $row['employee_type']],
                ['active_flag' => 1]
            )
            : null;

        $salaryBasis = !empty($row['salary_basis']) 
            ? SalaryBasis::firstOrCreate(
                ['title' => $row['salary_basis']],
                ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
            )
            : null;

        $gender = !empty($row['gender']) 
            ? Gender::firstOrCreate(
                ['title' => $row['gender']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;

        $maritalStatus = !empty($row['marital_status']) 
            ? MaritalStatus::firstOrCreate(
                ['title' => $row['marital_status']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;

        $nationality = !empty($row['nationality']) 
            ? Nationality::where('nationality', $row['nationality'])->first() 
            : null;

        // Lookup Country for country_of_birth
        $countryOfBirth = null;
        if (!empty($row['country_of_birth'])) {
            $countryOfBirth = Country::where('country_name', $row['country_of_birth'])
                ->orWhere('name', $row['country_of_birth'])
                ->first();
        }

        // Lookup Sponsorship Type (support both old and new column names)
        $sponsorshipType = null;
        $sponsorshipValue = $row['sponsorship_type'] ?? $row['sponsorship_id'] ?? null;
        
        if (!empty($sponsorshipValue)) {
            $sponsorshipType = EmployeeSponsorship::firstOrCreate(
                ['title' => $sponsorshipValue],
                ['active_flag' => 1, 'created_by' => 1, 'updated_by' => 1]
            );
        }

        // Generate full name
        $fullName = trim(
            ($row['first_name'] ?? '') . ' ' . 
            ($row['middle_name'] ?? '') . ' ' . 
            ($row['last_name'] ?? '')
        );

        // Create employee
        $employee = new Employee([
            'first_name' => $row['first_name'] ?? null,
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'full_name' => $fullName,
            'work_email_address' => $row['work_email'] ?? null,
            'personal_email_address' => $row['personal_email'] ?? null,
            'phone_number' => $row['phone_number'] ?? null,
            'phone_area_code' => $row['phone_area_code'] ?? null,
            'alt_phone_number' => $row['alt_phone_number'] ?? null,
            'alt_area_code' => $row['alt_area_code'] ?? null,
            'employee_number' => $row['employee_number'] ?? null,
            'national_identifier_number' => $row['national_id'] ?? null,
            'salutation_id' => $salutation?->id,
            'gender_id' => $gender?->id,
            'marital_status_id' => $maritalStatus?->id,
            'nationality_id' => $nationality?->id,
            'contract_start_date' => $this->parseDate($row['contract_start_date'] ?? null),
            'contract_end_date' => $this->parseDate($row['contract_end_date'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'town_of_birth' => $row['town_of_birth'] ?? null,
            'country_of_birth' => $countryOfBirth?->id,
            'date_of_hire' => $this->parseDate($row['date_of_hire'] ?? null),
            'join_date' => $this->parseDate($row['join_date'] ?? null),
            'sponsorship_id' => $sponsorshipType?->id,
            'sponsorship_name' => $row['sponsorship_name'] ?? null,
            'passport_number' => $row['passport_number'] ?? null,
            'passport_expiry' => $this->parseDate($row['passport_expiry'] ?? null),
            'civil_id_expiry' => $this->parseDate($row['civil_id_expiry'] ?? null),
            'manager_flag' => strtoupper($row['manager_flag'] ?? 'N') === 'Y' ? 'Y' : 'N',
            'administrator_flag' => strtoupper($row['admin_flag'] ?? 'N') === 'Y' ? 'Y' : 'N',
            'archived' => 'N',
        ]);

        // Save employee first
        $employee->save();

        // If event is provided, assign employee to event with organizational details
        if ($event) {
            DB::table('employee_events')->insert([
                'employee_id' => $employee->id,
                'event_id' => $event->id,
                'department_id' => $department?->id,
                'designation_id' => $designation?->id,
                'directorate_id' => $directorate?->id,
                'functional_area_id' => $functionalArea?->id,
                'entity_id' => $entity?->id,
                'contract_type_id' => $contractType?->id,
                'employee_type' => $employeeType?->id,
                'salary_basis_id' => $salaryBasis?->id,
                'agreement_number' => $row['agreement_number'] ?? null,
                'assigned_at' => $this->parseDate($row['contract_start_date'] ?? null) ?? now(),
                'released_at' => $this->parseDate($row['contract_end_date'] ?? null),
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->processedCount++;

        // Return null because we already saved the employee
        return null;
    }

    public function rules(): array
    {
        return [
            'event_name' => 'nullable|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'work_email' => 'required|email|unique:employees_all,work_email_address',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'event_name.string' => 'Event name must be text',
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'work_email.required' => 'Work email is required',
            'work_email.email' => 'Work email must be a valid email address',
            'work_email.unique' => 'Work email already exists in the system',
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
        return $this->processedCount;
    }

    public function getFailureCount(): int
    {
        return collect($this->failures())->map(fn($f) => $f->row())->unique()->count();
    }

    public function getSkipCount(): int
    {
        return $this->skipCount;
    }

    public function getStats(): array
    {
        // Count unique failed rows (row() is a method, not a property)
        $failures = $this->failures();
        $uniqueFailedRows = collect($failures)->map(fn($f) => $f->row())->unique()->count();
        
        return [
            'success' => $this->processedCount,
            'failed' => $uniqueFailedRows,
            'skipped' => $this->skipCount,
            'total' => $this->processedCount + $uniqueFailedRows + $this->skipCount,
        ];
    }
}
