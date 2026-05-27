<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeLeaveStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'title' => 'Pending',
                'color' => '#f59e0b',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Approved',
                'color' => '#10b981',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Rejected',
                'color' => '#ef4444',
                'active_flag' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('employee_leave_status')->insert($statuses);
    }
}
