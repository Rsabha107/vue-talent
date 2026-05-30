<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Salutation;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\Country;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;

/**
 * Mode 1: Base Employee Import (No Event Assignment)
 * Imports employee personal data only without organizational attributes
 */
class BaseEmployeesImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    protected int $successCount = 0;
    protected int $skipCount = 0;
    protected array $errors = [];

    public function model(array $row)
    {
        // Skip empty rows
        if (empty($row['first_name']) && empty($row['last_name']) && empty($row['work_email'])) {
            $this->skipCount++;
            return null;
        }

        // Find or create salutation
        $salutation = !empty($row['salutation']) 
            ? Salutation::firstOrCreate(
                ['title' => $row['salutation']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;

        // Find or create gender
        $gender = !empty($row['gender']) 
            ? Gender::firstOrCreate(
                ['title' => $row['gender']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;

        // Find or create marital status
        $maritalStatus = !empty($row['marital_status']) 
            ? MaritalStatus::firstOrCreate(
                ['title' => $row['marital_status']],
                ['created_by' => 1, 'updated_by' => 1]
            )
            : null;

        // Find nationality by name
        $nationality = !empty($row['nationality']) 
            ? Nationality::where('nationality', $row['nationality'])->first() 
            : null;

        // Find country of birth by name
        $countryOfBirth = !empty($row['country_of_birth']) 
            ? Country::where('name', $row['country_of_birth'])->first() 
            : null;

        // Generate full name
        $fullName = trim(
            ($row['first_name'] ?? '') . ' ' . 
            ($row['middle_name'] ?? '') . ' ' . 
            ($row['last_name'] ?? '')
        );

        $this->successCount++;

        return new Employee([
            // Personal Information
            'first_name' => $row['first_name'] ?? null,
            'middle_name' => $row['middle_name'] ?? null,
            'last_name' => $row['last_name'] ?? null,
            'full_name' => $fullName,
            'salutation_id' => $salutation?->id,
            'employee_number' => $row['employee_number'] ?? null,
            
            // Contact Information
            'work_email_address' => $row['work_email'] ?? null,
            'personal_email_address' => $row['personal_email'] ?? null,
            'phone_number' => $row['phone_number'] ?? null,
            'alt_phone_number' => $row['alt_phone_number'] ?? null,
            'phone_area_code' => $row['phone_area_code'] ?? null,
            'alt_phone_area_code' => $row['alt_phone_area_code'] ?? null,
            
            // Personal Dates
            'date_of_hire' => $this->parseDate($row['date_of_hire'] ?? null),
            'join_date' => $this->parseDate($row['join_date'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            
            // Personal Demographics
            'gender_id' => $gender?->id,
            'marital_status_id' => $maritalStatus?->id,
            'nationality_id' => $nationality?->id,
            'town_of_birth' => $row['town_of_birth'] ?? null,
            'country_of_birth' => $countryOfBirth?->id,
            
            // Identification Documents
            'national_identifier_number' => $row['national_id'] ?? null,
            'passport_number' => $row['passport_number'] ?? null,
            'passport_expiry' => $this->parseDate($row['passport_expiry'] ?? null),
            'civil_id_expiry' => $this->parseDate($row['civil_id_expiry'] ?? null),
            
            // Sponsorship
            'sponsorship_id' => $row['sponsorship_id'] ?? null,
            'sponsorship_name' => $row['sponsorship_name'] ?? null,
            
            // Flags
            'manager_flag' => strtoupper($row['manager_flag'] ?? 'N') === 'Y' ? 'Y' : 'N',
            'administrator_flag' => strtoupper($row['admin_flag'] ?? 'N') === 'Y' ? 'Y' : 'N',
            'archived' => 'N',
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'work_email' => 'required|email|unique:employees_all,work_email_address',
            'employee_number' => 'nullable|string|max:50|unique:employees_all,employee_number',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'first_name.required' => 'First name is required',
            'last_name.required' => 'Last name is required',
            'work_email.required' => 'Work email is required',
            'work_email.email' => 'Work email must be a valid email address',
            'work_email.unique' => 'Work email already exists in the system',
            'employee_number.unique' => 'Employee number already exists in the system',
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
            'failed' => count($this->failures()),
            'skipped' => $this->skipCount,
            'total' => $this->successCount + count($this->failures()) + $this->skipCount,
        ];
    }
}
