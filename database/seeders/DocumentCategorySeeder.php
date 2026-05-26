<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'title' => 'Contracts',
                'description' => 'Employment contracts, amendments, and related documents',
                'icon' => 'file-signature',
            ],
            [
                'title' => 'Identity Documents',
                'description' => 'National ID, passport, visa, work permits',
                'icon' => 'id-card',
            ],
            [
                'title' => 'Bank Documents',
                'description' => 'Bank account details, IBAN certificates',
                'icon' => 'wallet',
            ],
            [
                'title' => 'Certificates',
                'description' => 'Educational certificates, professional certifications',
                'icon' => 'award',
            ],
            [
                'title' => 'Medical Records',
                'description' => 'Medical examination reports, fitness certificates',
                'icon' => 'heart',
            ],
            [
                'title' => 'Tax Documents',
                'description' => 'Tax registration, tax clearance certificates',
                'icon' => 'receipt',
            ],
            [
                'title' => 'Insurance',
                'description' => 'Health insurance, life insurance, other insurance documents',
                'icon' => 'shield',
            ],
            [
                'title' => 'Miscellaneous',
                'description' => 'Other employee-related documents',
                'icon' => 'folder',
            ],
        ];

        foreach ($categories as $category) {
            DocumentCategory::create($category);
        }
    }
}

