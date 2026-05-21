<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GenderSeeder extends Seeder
{
    public function run(): void
    {
        $genders = [
            ['title' => 'Male', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Female', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Non-Binary', 'created_by' => null, 'updated_by' => null],
            ['title' => 'Prefer not to say', 'created_by' => null, 'updated_by' => null],
        ];

        foreach ($genders as $gender) {
            DB::table('genders')->updateOrInsert(
                ['title' => $gender['title']],
                $gender
            );
        }

        $this->command->info('Genders seeded successfully.');
    }
}
