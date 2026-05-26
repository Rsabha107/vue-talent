<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTimesheetStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'title' => 'Pending',
                'color' => 'warning',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Submitted',
                'color' => 'info',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Approved',
                'color' => 'success',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Rejected',
                'color' => 'danger',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('employee_timesheet_status')->insert($statuses);
    }
}
