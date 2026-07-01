<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Designation;
use App\Models\Directorate;
use App\Models\FunctionalArea;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SelectedEmployeesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected array $employeeIds;

    public function __construct(array $employeeIds)
    {
        $this->employeeIds = $employeeIds;
    }

    public function collection()
    {
        return Employee::with([
            'user',
            'salutation',
            'gender',
            'maritalStatus',
            'entity',
            'contractType',
            'nationality',
            'reportingTo.user',
            'activeEvents',
        ])
        ->whereIn('id', $this->employeeIds)
        ->get()
        ->each(function ($employee) {
            // Load pivot relationships after fetching
            $employee->activeEvents->loadMissing([
                'pivot.designation',
                'pivot.directorate',
                'pivot.functionalArea',
                'pivot.entity',
                'pivot.contractType',
                'pivot.reportingTo.user',
                'pivot.salaryBasis',
                'pivot.employeeType',
                'pivot.jobLevel',
            ]);
        });
    }

    public function map($employee): array
    {
        // Get data from first active event assignment (if exists)
        $activeEvent = $employee->activeEvents->first();
        $agreementNumber = $employee->agreement_number;
        $designation = null;
        $directorate = null;
        $functionalArea = null;
        $entity = $employee->entity; // Fallback to employee's entity if no event
        $contractType = $employee->contractType; // Fallback to employee's contract type
        $reportingTo = $employee->reportingTo; // Fallback to employee's reporting_to
        $salaryBasis = null;
        $jobLevel = null;
        $employeeType = null;
        
        if ($activeEvent && $activeEvent->pivot) {
            // Use agreement number from event if available
            if ($activeEvent->pivot->agreement_number) {
                $agreementNumber = $activeEvent->pivot->agreement_number;
            }
            
            // Get organizational data from pivot relationships
            $designation = $activeEvent->pivot->designation;
            $directorate = $activeEvent->pivot->directorate;
            $functionalArea = $activeEvent->pivot->functionalArea;
            $entity = $activeEvent->pivot->entity ?: $employee->entity;
            $contractType = $activeEvent->pivot->contractType ?: $employee->contractType;
            $reportingTo = $activeEvent->pivot->reportingTo;
            $salaryBasis = $activeEvent->pivot->salaryBasis;
            $jobLevel = $activeEvent->pivot->jobLevel;
            $employeeType = $activeEvent->pivot->employeeType;
        }
        
        return [
            $employee->user->name ?? '',
            $employee->user->email ?? '',
            $employee->phone_number ?? '',
            $employee->alt_phone_number ?? '',
            $employee->employee_number ?? '',
            $agreementNumber ?? '',
            $employee->national_identifier_number ?? '',
            $employee->salutation->title ?? '',
            $employee->gender->title ?? '',
            $employee->maritalStatus->title ?? '',
            $designation?->name ?? '',
            $directorate?->title ?? '',
            $functionalArea?->title ?? '',
            $entity?->title ?? '',
            $contractType?->title ?? '',
            $salaryBasis?->title ?? '',
            $jobLevel?->title ?? '',
            $employeeType?->title ?? '',
            $reportingTo?->user?->name ?? '',
            $employee->contract_start_date?->format('Y-m-d') ?? '',
            $employee->contract_end_date?->format('Y-m-d') ?? '',
            $employee->date_of_birth?->format('Y-m-d') ?? '',
            $employee->date_of_hire?->format('Y-m-d') ?? '',
            $employee->join_date?->format('Y-m-d') ?? '',
            $employee->nationality->nationality ?? '',
            $employee->passport_number ?? '',
            $employee->passport_expiry?->format('Y-m-d') ?? '',
            $employee->civil_id_expiry?->format('Y-m-d') ?? '',
            $employee->manager_flag ? 'Yes' : 'No',
            $employee->admin_flag ? 'Yes' : 'No',
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Alt Phone',
            'Employee #',
            'Agreement #',
            'National ID',
            'Salutation',
            'Gender',
            'Marital Status',
            'Designation',
            'Directorate',
            'Functional Area',
            'Entity',
            'Contract Type',
            'Salary Basis',
            'Job Level',
            'Employee Type',
            'Reporting To',
            'Contract Start',
            'Contract End',
            'Date of Birth',
            'Date of Hire',
            'Join Date',
            'Nationality',
            'Passport #',
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
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'], // Meridian green
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,  // Name
            'B' => 30,  // Email
            'C' => 15,  // Phone
            'D' => 15,  // Alt Phone
            'E' => 15,  // Employee #
            'F' => 15,  // Agreement #
            'G' => 15,  // National ID
            'H' => 12,  // Salutation
            'I' => 12,  // Gender
            'J' => 15,  // Marital Status
            'K' => 25,  // Designation
            'L' => 20,  // Directorate
            'M' => 20,  // Functional Area
            'N' => 20,  // Entity
            'O' => 15,  // Contract Type
            'P' => 15,  // Salary Basis
            'Q' => 15,  // Job Level
            'R' => 15,  // Employee Type
            'S' => 25,  // Reporting To
            'T' => 15,  // Contract Start
            'U' => 15,  // Contract End
            'V' => 15,  // Date of Birth
            'W' => 15,  // Date of Hire
            'X' => 15,  // Join Date
            'Y' => 15,  // Nationality
            'Z' => 15,  // Passport #
            'AA' => 15, // Passport Expiry
            'AB' => 15, // Civil ID Expiry
            'AC' => 12, // Manager Flag
            'AD' => 12, // Admin Flag
        ];
    }
}
