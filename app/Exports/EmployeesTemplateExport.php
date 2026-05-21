<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'John',
                'Michael',
                'Doe',
                'john.doe@company.com',
                'john.personal@email.com',
                '+1234567890',
                '+0987654321',
                'E-001',
                'AGR-2024-001',
                '123456789',
                'Mr.',
                'Male',
                'Married',
                'Senior Product Manager',
                'Engineering',
                'Technology',
                'Innovation',
                '2024-01-15',
                '2026-12-31',
                '1985-03-20',
                '2024-01-15',
                '2024-01-15',
                'Company Sponsor',
                'American',
                'P123456789',
                '2030-12-31',
                '2028-06-30',
                'Y',
                'N',
            ],
            [
                'Jane',
                'Marie',
                'Smith',
                'jane.smith@company.com',
                'jane.smith@email.com',
                '+1234567891',
                '',
                'E-002',
                'AGR-2024-002',
                '987654321',
                'Ms.',
                'Female',
                'Single',
                'UX Designer',
                'Design',
                'Product',
                'User Experience',
                '2024-02-01',
                '2027-01-31',
                '1990-07-15',
                '2024-02-01',
                '2024-02-01',
                'Self Sponsored',
                'British',
                'GB987654321',
                '2029-08-15',
                '2027-12-31',
                'N',
                'N',
            ],
            [
                'Ahmed',
                'Ali',
                'Hassan',
                'ahmed.hassan@company.com',
                '',
                '+1234567892',
                '+1234567893',
                'E-003',
                'AGR-2024-003',
                '456789123',
                'Mr.',
                'Male',
                'Married',
                'Software Engineer',
                'Engineering',
                'Technology',
                'Development',
                '2024-03-01',
                '2027-02-28',
                '1988-11-30',
                '2024-03-01',
                '2024-03-01',
                'Company Sponsor',
                'Kuwaiti',
                'KW456789123',
                '2031-05-20',
                '2029-03-15',
                'N',
                'Y',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Middle Name',
            'Last Name',
            'Work Email',
            'Personal Email',
            'Phone Number',
            'Alt Phone Number',
            'Employee Number',
            'Agreement Number',
            'National ID',
            'Salutation',
            'Gender',
            'Marital Status',
            'Designation',
            'Department',
            'Directorate',
            'Functional Area',
            'Contract Start Date',
            'Contract End Date',
            'Date of Birth',
            'Date of Hire',
            'Join Date',
            'Sponsorship Name',
            'Nationality',
            'Passport Number',
            'Passport Expiry',
            'Civil ID Expiry',
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
            'A' => 15,
            'B' => 15,
            'C' => 15,
            'D' => 25,
            'E' => 25,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 18,
            'J' => 15,
            'K' => 12,
            'L' => 12,
            'M' => 15,
            'N' => 20,
            'O' => 15,
            'P' => 15,
            'Q' => 18,
            'R' => 18,
            'S' => 18,
            'T' => 15,
            'U' => 15,
            'V' => 15,
            'W' => 18,
            'X' => 15,
            'Y' => 18,
            'Z' => 15,
            'AA' => 15,
            'AB' => 12,
            'AC' => 12,
        ];
    }
}
