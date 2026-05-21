<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaritalStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['title' => 'Single', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Married', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Divorced', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Widowed', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Separated', 'created_by' => null, 'updated_by' => null],
        ];

        foreach ($statuses as $status) {
            DB::table('marital_statuses')->updateOrInsert(
                ['title' => $status['title']],
                $status
            );
        }

        $this->command->info('Marital statuses seeded successfully.');
    }
}
