<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();
        $fullName = $firstName . ' ' . $lastName;
        $joinDate = fake()->dateTimeBetween('-5 years', '-1 month')->format('Y-m-d');
        $contractStartDate = fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d');
        $dateOfBirth = fake()->dateTimeBetween('-60 years', '-22 years')->format('Y-m-d');
        
        // Optional dates using passthrough
        $contractEndDate = fake()->optional(0.3)->passthrough(
            fake()->dateTimeBetween($contractStartDate, '+2 years')->format('Y-m-d')
        );
        $passportExpiry = fake()->optional(0.8)->passthrough(
            fake()->dateTimeBetween('now', '+10 years')->format('Y-m-d')
        );
        $civilIdExpiry = fake()->optional(0.8)->passthrough(
            fake()->dateTimeBetween('now', '+5 years')->format('Y-m-d')
        );
        
        return [
            'archived' => 'N',
            'employee_number' => 'EMP-' . fake()->unique()->numerify('#####'),
            'national_identifier_number' => fake()->numerify('###########'),
            'salutation_id' => fake()->numberBetween(1, 4),
            'first_name' => $firstName,
            'middle_name' => fake()->optional(0.3)->firstName(),
            'last_name' => $lastName,
            'full_name' => $fullName,
            'gender_id' => (string) fake()->numberBetween(1, 2),
            'marital_status_id' => fake()->numberBetween(1, 4),
            'employee_type' => fake()->numberBetween(1, 4),
            'contract_type_id' => fake()->numberBetween(1, 3),
            'contract_start_date' => $contractStartDate,
            'contract_end_date' => $contractEndDate,
            'date_of_birth' => $dateOfBirth,
            'date_of_hire' => $joinDate,
            'join_date' => $joinDate,
            'town_of_birth' => fake()->city(),
            'country_of_birth' => (string) fake()->numberBetween(1, 246),
            'personal_email_address' => fake()->safeEmail(),
            'work_email_address' => strtolower($firstName . '.' . $lastName . '@company.com'),
            'phone_number' => fake()->numerify('+965########'),
            'alt_phone_number' => fake()->optional(0.5)->numerify('+965########'),
            'nationality_id' => fake()->numberBetween(1, 246),
            'language_id' => fake()->numberBetween(1, 10),
            'reporting_to_id' => fake()->optional(0.9)->numberBetween(1, 10),
            'department_id' => fake()->numberBetween(1, 20),
            'designation_id' => fake()->numberBetween(1, 15),
            'job_level_id' => fake()->numberBetween(1, 10),
            'entity_id' => fake()->numberBetween(1, 5),
            'directorate_id' => fake()->optional(0.6)->numberBetween(1, 10),
            'functional_area_id' => fake()->optional(0.6)->numberBetween(1, 10),
            'manager_flag' => fake()->optional(0.2)->randomElement(['Y', 'N']),
            'administrator_flag' => 'N',
            'passport_number' => fake()->optional(0.8)->bothify('??#######'),
            'passport_expiry' => $passportExpiry,
            'civil_id_expiry' => $civilIdExpiry,
        ];
    }
}
