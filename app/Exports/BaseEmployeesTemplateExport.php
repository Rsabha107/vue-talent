<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mode 1: Base Employee Template (No Event Assignment)
 * Template for importing employee personal data without organizational attributes
 */
class BaseEmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'John',
                'Michael',
                'Doe',
                'Mr.',
                'E-001',
                'john.doe@company.com',
                'john.personal@email.com',
                '+1234567890',
                '+0987654321',
                '+1',
                '+1',
                '2024-01-15',
                '2024-01-15',
                '1985-03-20',
                'Male',
                'Married',
                'American',
                'New York',
                'United States',
                '123456789',
                'P123456789',
                '2030-12-31',
                '2028-06-30',
                'Company Sponsored',
                'Company Sponsor',
                'Y',
                'N',
            ],
            [
                'Jane',
                'Marie',
                'Smith',
                'Ms.',
                'E-002',
                'jane.smith@company.com',
                'jane.smith@email.com',
                '+1234567891',
                '',
                '+1',
                '',
                '2024-02-01',
                '2024-02-01',
                '1990-07-15',
                'Female',
                'Single',
                'British',
                'London',
                'United Kingdom',
                '987654321',
                'GB987654321',
                '2029-08-15',
                '2027-12-31',
                'Self Sponsored',
                'Self Sponsored',
                'N',
                'N',
            ],
            [
                'Ahmed',
                'Ali',
                'Hassan',
                'Mr.',
                'E-003',
                'ahmed.hassan@company.com',
                '',
                '+1234567892',
                '+1234567893',
                '+971',
                '+971',
                '2024-03-01',
                '2024-03-01',
                '1988-11-30',
                'Male',
                'Married',
                'Egyptian',
                'Cairo',
                'Egypt',
                '456789123',
                'EG456789123',
                '2031-05-20',
                '2029-10-15',
                'Company Sponsored',
                'Internal Transfer',
                'N',
                'N',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Middle Name',
            'Last Name',
            'Salutation',
            'Employee Number',
            'Work Email',
            'Personal Email',
            'Phone Number',
            'Alt Phone Number',
            'Phone Area Code',
            'Alt Phone Area Code',
            'Date of Hire',
            'Join Date',
            'Date of Birth',
            'Gender',
            'Marital Status',
            'Nationality',
            'Town of Birth',
            'Country of Birth',
            'National ID',
            'Passport Number',
            'Passport Expiry',
            'Civil ID Expiry',
            'Sponsorship Type',
            'Sponsorship Name',
            'Manager Flag',
            'Admin Flag',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // First Name
            'B' => 15, // Middle Name
            'C' => 15, // Last Name
            'D' => 12, // Salutation
            'E' => 18, // Employee Number
            'F' => 30, // Work Email
            'G' => 30, // Personal Email
            'H' => 18, // Phone Number
            'I' => 18, // Alt Phone Number
            'J' => 15, // Phone Area Code
            'K' => 15, // Alt Phone Area Code
            'L' => 18, // Date of Hire
            'M' => 18, // Join Date
            'N' => 18, // Date of Birth
            'O' => 12, // Gender
            'P' => 15, // Marital Status
            'Q' => 15, // Nationality
            'R' => 15, // Town of Birth
            'S' => 20, // Country of Birth
            'T' => 18, // National ID
            'U' => 18, // Passport Number
            'V' => 18, // Passport Expiry
            'W' => 18, // Civil ID Expiry
            'X' => 15, // Sponsorship ID
            'Y' => 20, // Sponsorship Name
            'Z' => 12, // Manager Flag
            'AA' => 12, // Admin Flag
        ];
    }
}
