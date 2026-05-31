<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Mode 3: Event Assignment Template (Existing Employees)
 * Template for bulk-assigning existing employees to events
 */
class EventAssignmentTemplateExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    public function array(): array
    {
        return [
            [
                'E-001',
                'john.doe@company.com',
                'World Cup 2026',
                'Senior Product Manager',
                'Engineering',
                'Technology',
                'Innovation',
                'Company A',
                'Fixed',
                'Permanent',
                'Monthly',
                'E-050',
                'manager@company.com',
                'AGR-2024-001',
                '2024-01-15',
                '2026-12-31',
            ],
            [
                'E-002',
                'jane.smith@company.com',
                'World Cup 2026',
                'UX Designer',
                'Design',
                'Product',
                'User Experience',
                'Company B',
                'Contract',
                'Contractor',
                'Daily',
                'E-001',
                '',
                'AGR-2024-002',
                '2024-02-01',
                '2027-01-31',
            ],
            [
                'E-003',
                'ahmed.hassan@company.com',
                'Olympics 2028',
                'Software Engineer',
                'Engineering',
                'Technology',
                'Development',
                'Company A',
                'Fixed',
                'Permanent',
                'Monthly',
                'E-001',
                'john.doe@company.com',
                'AGR-2024-003',
                '2024-03-01',
                '2028-02-28',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Employee Number',
            'Work Email',
            'Event Name',
            'Designation',
            'Department',
            'Directorate',
            'Functional Area',
            'Entity',
            'Contract Type',
            'Employee Type',
            'Salary Basis',
            'Manager Employee Number',
            'Manager Email',
            'Agreement Number',
            'Assigned At',
            'Released At',
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
            'A' => 18, // Employee Number
            'B' => 30, // Work Email
            'C' => 25, // Event Name
            'D' => 25, // Designation
            'E' => 20, // Department
            'F' => 20, // Directorate
            'G' => 20, // Functional Area
            'H' => 18, // Entity
            'I' => 15, // Contract Type
            'J' => 15, // Salary Basis
            'K' => 22, // Manager Employee Number
            'L' => 30, // Manager Email
            'M' => 20, // Agreement Number
            'N' => 18, // Assigned At
            'O' => 18, // Released At
        ];
    }
}
