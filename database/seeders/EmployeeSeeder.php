<?php

namespace Database\Seeders;

use App\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Generating 100 employees...');

        // Create 100 employees using the factory
        Employee::factory()
            ->count(100)
            ->create();

        $this->command->info('Successfully created 100 employees!');
    }
}
