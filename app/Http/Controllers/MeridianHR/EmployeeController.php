<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Directorate;
use App\Models\Employee;
use App\Models\EmployeeContractType;
use App\Models\EmployeeEntity;
use App\Models\EmployeeType;
use App\Models\FunctionalArea;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\SalaryBasis;
use App\Models\Salutation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    // ── Shared mock data ──────────────────────────────────────────────

    private function me(): array
    {
        return [
            'id'          => 'u-001',
            'name'        => 'Layla Haddad',
            'email'       => 'layla.haddad@meridian.co',
            'role'        => 'Senior Product Designer',
            'department'  => 'Design',
            'manager'     => 'Adrian Okafor',
            'joinDate'    => '2022-03-14',
            'empNumber'   => 'M-00412',
            'avatarColor' => 0,
            'initials'    => 'LH',
        ];
    }

    private function hrRole(): string
    {
        // In production: derive from auth()->user()->hr_role
        return request()->query('role', 'admin');
    }

    private function leaveBalance(): array
    {
        return [
            'annual'   => ['used' => 8,  'total' => 25, 'pending' => 2],
            'sick'     => ['used' => 3,  'total' => 10, 'pending' => 0],
            'personal' => ['used' => 1,  'total' => 5,  'pending' => 0],
            'unpaid'   => ['used' => 0,  'total' => null, 'pending' => 0],
        ];
    }

    private function leaves(): array
    {
        return [
            ['id'=>'lv-1082','type'=>'Annual',   'from'=>'2026-05-18','to'=>'2026-05-22','days'=>5, 'status'=>'pending',  'approver'=>'Adrian Okafor','note'=>'Wedding in Lisbon',      'filed'=>'2026-05-01'],
            ['id'=>'lv-1090','type'=>'Personal', 'from'=>'2026-06-08','to'=>'2026-06-08','days'=>1, 'status'=>'pending',  'approver'=>'Adrian Okafor','note'=>'Apartment viewing',      'filed'=>'2026-05-04'],
            ['id'=>'lv-1067','type'=>'Sick',     'from'=>'2026-04-29','to'=>'2026-04-29','days'=>1, 'status'=>'approved', 'approver'=>'Adrian Okafor','note'=>'Migraine',               'filed'=>'2026-04-29'],
            ['id'=>'lv-1041','type'=>'Annual',   'from'=>'2026-04-13','to'=>'2026-04-17','days'=>5, 'status'=>'approved', 'approver'=>'Adrian Okafor','note'=>'Family trip to coast',   'filed'=>'2026-03-02'],
            ['id'=>'lv-1004','type'=>'Annual',   'from'=>'2026-01-02','to'=>'2026-01-03','days'=>2, 'status'=>'approved', 'approver'=>'Adrian Okafor','note'=>'New year',               'filed'=>'2025-12-12'],
            ['id'=>'lv-0989','type'=>'Sick',     'from'=>'2025-12-19','to'=>'2025-12-22','days'=>2, 'status'=>'approved', 'approver'=>'Adrian Okafor','note'=>'Flu',                    'filed'=>'2025-12-19'],
            ['id'=>'lv-0922','type'=>'Annual',   'from'=>'2025-11-03','to'=>'2025-11-05','days'=>3, 'status'=>'rejected', 'approver'=>'Adrian Okafor','note'=>'Conflict with launch',   'filed'=>'2025-10-21'],
        ];
    }

    private function pendingLeaves(): array
    {
        return [
            ['id'=>'lv-1082','emp'=>'Layla Haddad',    'empId'=>'M-00412','c'=>0,'type'=>'Annual',   'from'=>'2026-05-18','to'=>'2026-05-22','days'=>5, 'filed'=>'2026-05-01','note'=>'Wedding in Lisbon. Will hand off onboarding spec to Priya.','hasOverlap'=>false],
            ['id'=>'lv-1090','emp'=>'Layla Haddad',    'empId'=>'M-00412','c'=>0,'type'=>'Personal', 'from'=>'2026-06-08','to'=>'2026-06-08','days'=>1, 'filed'=>'2026-05-04','note'=>'Apartment viewing.',                                              'hasOverlap'=>false],
            ['id'=>'lv-1095','emp'=>'Marcus Chen',     'empId'=>'M-00387','c'=>3,'type'=>'Annual',   'from'=>'2026-05-18','to'=>'2026-05-20','days'=>3, 'filed'=>'2026-05-02','note'=>'Visiting parents.',                                               'hasOverlap'=>true],
            ['id'=>'lv-1097','emp'=>'Sofía Vargas',    'empId'=>'M-00401','c'=>4,'type'=>'Sick',     'from'=>'2026-05-04','to'=>'2026-05-04','days'=>1, 'filed'=>'2026-05-04','note'=>"Flu, doctor's note attached.",                                    'hasOverlap'=>false],
            ['id'=>'lv-1102','emp'=>'Aiko Tanaka',     'empId'=>'M-00444','c'=>6,'type'=>'Annual',   'from'=>'2026-06-22','to'=>'2026-07-03','days'=>10,'filed'=>'2026-05-03','note'=>'Honeymoon.',                                                       'hasOverlap'=>false],
            ['id'=>'lv-1104','emp'=>'Tomás Bergström', 'empId'=>'M-00420','c'=>5,'type'=>'Personal', 'from'=>'2026-05-15','to'=>'2026-05-15','days'=>1, 'filed'=>'2026-05-04','note'=>'Court appearance.',                                               'hasOverlap'=>false],
        ];
    }

    private function pendingTimesheets(): array
    {
        return [
            ['id'=>'ts-26-04-PR','emp'=>'Priya Ramaswamy','empId'=>'M-00204','c'=>2,'period'=>'April 2026','worked'=>168,'leave'=>8, 'unpaid'=>0,'projects'=>4,'submitted'=>'2026-05-01','note'=>'Logged extra hours on Atlas migration.'],
            ['id'=>'ts-26-04-MC','emp'=>'Marcus Chen',    'empId'=>'M-00387','c'=>3,'period'=>'April 2026','worked'=>152,'leave'=>16,'unpaid'=>0,'projects'=>3,'submitted'=>'2026-05-02','note'=>''],
            ['id'=>'ts-26-04-SV','emp'=>'Sofía Vargas',  'empId'=>'M-00401','c'=>4,'period'=>'April 2026','worked'=>160,'leave'=>8, 'unpaid'=>8,'projects'=>5,'submitted'=>'2026-05-02','note'=>'Took unpaid day for family matter.'],
            ['id'=>'ts-26-04-AT','emp'=>'Aiko Tanaka',   'empId'=>'M-00444','c'=>6,'period'=>'April 2026','worked'=>144,'leave'=>24,'unpaid'=>0,'projects'=>2,'submitted'=>'2026-05-01','note'=>''],
            ['id'=>'ts-26-04-TB','emp'=>'Tomás Bergström','empId'=>'M-00420','c'=>5,'period'=>'April 2026','worked'=>160,'leave'=>8, 'unpaid'=>8,'projects'=>3,'submitted'=>'2026-05-03','note'=>''],
        ];
    }

    private function timesheets(): array
    {
        return [
            '2026-05' => [
                'label'     => 'May 2026',
                'submitted' => false,
                'days'      => [
                    ['d'=>1, 'type'=>'W','hours'=>8],['d'=>2, 'type'=>'0','hours'=>0],['d'=>3, 'type'=>'0','hours'=>0],
                    ['d'=>4, 'type'=>'W','hours'=>8],['d'=>5, 'type'=>' ','hours'=>0],['d'=>6, 'type'=>' ','hours'=>0],
                    ['d'=>7, 'type'=>' ','hours'=>0],['d'=>8, 'type'=>' ','hours'=>0],['d'=>9, 'type'=>'0','hours'=>0],
                    ['d'=>10,'type'=>'0','hours'=>0],['d'=>11,'type'=>' ','hours'=>0],['d'=>12,'type'=>' ','hours'=>0],
                    ['d'=>13,'type'=>' ','hours'=>0],['d'=>14,'type'=>' ','hours'=>0],['d'=>15,'type'=>' ','hours'=>0],
                    ['d'=>16,'type'=>'0','hours'=>0],['d'=>17,'type'=>'0','hours'=>0],['d'=>18,'type'=>'L','hours'=>0],
                    ['d'=>19,'type'=>'L','hours'=>0],['d'=>20,'type'=>'L','hours'=>0],['d'=>21,'type'=>'L','hours'=>0],
                    ['d'=>22,'type'=>'L','hours'=>0],['d'=>23,'type'=>'0','hours'=>0],['d'=>24,'type'=>'0','hours'=>0],
                    ['d'=>25,'type'=>' ','hours'=>0],['d'=>26,'type'=>' ','hours'=>0],['d'=>27,'type'=>' ','hours'=>0],
                    ['d'=>28,'type'=>' ','hours'=>0],['d'=>29,'type'=>' ','hours'=>0],['d'=>30,'type'=>'0','hours'=>0],
                    ['d'=>31,'type'=>'0','hours'=>0],
                ],
            ],
            '2026-04' => [
                'label'     => 'April 2026',
                'submitted' => true,
                'days'      => [
                    ['d'=>1, 'type'=>'W','hours'=>8],  ['d'=>2, 'type'=>'W','hours'=>8],  ['d'=>3, 'type'=>'W','hours'=>8],
                    ['d'=>4, 'type'=>'0','hours'=>0],  ['d'=>5, 'type'=>'0','hours'=>0],  ['d'=>6, 'type'=>'W','hours'=>8],
                    ['d'=>7, 'type'=>'W','hours'=>9],  ['d'=>8, 'type'=>'W','hours'=>8],  ['d'=>9, 'type'=>'W','hours'=>7.5],
                    ['d'=>10,'type'=>'W','hours'=>8],  ['d'=>11,'type'=>'0','hours'=>0],  ['d'=>12,'type'=>'0','hours'=>0],
                    ['d'=>13,'type'=>'L','hours'=>0],  ['d'=>14,'type'=>'L','hours'=>0],  ['d'=>15,'type'=>'L','hours'=>0],
                    ['d'=>16,'type'=>'L','hours'=>0],  ['d'=>17,'type'=>'L','hours'=>0],  ['d'=>18,'type'=>'0','hours'=>0],
                    ['d'=>19,'type'=>'0','hours'=>0],  ['d'=>20,'type'=>'W','hours'=>8],  ['d'=>21,'type'=>'W','hours'=>8],
                    ['d'=>22,'type'=>'W','hours'=>8.5],['d'=>23,'type'=>'W','hours'=>8],  ['d'=>24,'type'=>'W','hours'=>8],
                    ['d'=>25,'type'=>'0','hours'=>0],  ['d'=>26,'type'=>'0','hours'=>0],  ['d'=>27,'type'=>'W','hours'=>8],
                    ['d'=>28,'type'=>'W','hours'=>8],  ['d'=>29,'type'=>'L','hours'=>0],  ['d'=>30,'type'=>'W','hours'=>9],
                ],
            ],
        ];
    }

    private function documentCategories(): array
    {
        return [
            ['id'=>'contracts','label'=>'Contracts','items'=>[
                ['id'=>'doc-1','name'=>'Employment Agreement — 2024','type'=>'PDF','size'=>'1.2 MB','date'=>'2024-01-15','status'=>'signed',  'pages'=>14],
                ['id'=>'doc-2','name'=>'Confidentiality Addendum',  'type'=>'PDF','size'=>'412 KB','date'=>'2024-01-15','status'=>'signed',  'pages'=>4],
                ['id'=>'doc-3','name'=>'Offer Letter (Promotion)',   'type'=>'PDF','size'=>'388 KB','date'=>'2025-09-22','status'=>'signed',  'pages'=>3],
            ]],
            ['id'=>'ids','label'=>'Identity & Tax','items'=>[
                ['id'=>'doc-4','name'=>'Passport scan',            'type'=>'PDF','size'=>'2.1 MB','date'=>'2023-08-04','status'=>'verified','pages'=>2],
                ['id'=>'doc-5','name'=>'National ID front + back', 'type'=>'JPG','size'=>'1.4 MB','date'=>'2023-08-04','status'=>'verified','pages'=>1],
                ['id'=>'doc-6','name'=>'Tax form W-8BEN',          'type'=>'PDF','size'=>'320 KB','date'=>'2026-01-12','status'=>'verified','pages'=>2],
            ]],
            ['id'=>'certificates','label'=>'Certificates','items'=>[
                ['id'=>'doc-7','name'=>'NN/g UX Certification',   'type'=>'PDF','size'=>'780 KB','date'=>'2023-04-19','status'=>'issued',  'pages'=>1],
                ['id'=>'doc-8','name'=>'AWS Cloud Practitioner',  'type'=>'PDF','size'=>'612 KB','date'=>'2024-11-02','status'=>'issued',  'pages'=>1],
            ]],
            ['id'=>'policies','label'=>'Policies & handbooks','items'=>[
                ['id'=>'doc-9', 'name'=>'Employee Handbook 2026',  'type'=>'PDF','size'=>'3.4 MB','date'=>'2026-01-04','status'=>'current', 'pages'=>48],
                ['id'=>'doc-10','name'=>'Remote Work Policy v2',   'type'=>'PDF','size'=>'212 KB','date'=>'2025-06-30','status'=>'current', 'pages'=>6],
                ['id'=>'doc-11','name'=>'Leave Policy',            'type'=>'PDF','size'=>'188 KB','date'=>'2026-01-04','status'=>'current', 'pages'=>4],
            ]],
            ['id'=>'payslips','label'=>'Pay & benefits','items'=>[
                ['id'=>'doc-12','name'=>'Payslip — April 2026',    'type'=>'PDF','size'=>'92 KB', 'date'=>'2026-04-30','status'=>'issued',  'pages'=>1],
                ['id'=>'doc-13','name'=>'Payslip — March 2026',    'type'=>'PDF','size'=>'94 KB', 'date'=>'2026-03-31','status'=>'issued',  'pages'=>1],
                ['id'=>'doc-14','name'=>'Benefits Enrolment 2026', 'type'=>'PDF','size'=>'1.0 MB','date'=>'2026-01-10','status'=>'current', 'pages'=>12],
            ]],
        ];
    }

    private function payslipData(): array
    {
        return [
            ['id'=>'ps-2026-04','period'=>'April 2026',    'issued'=>'2026-04-30','gross'=>9750.00,  'net'=>7312.50,'tax'=>1950.00,'deductions'=>487.50,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
            ['id'=>'ps-2026-03','period'=>'March 2026',    'issued'=>'2026-03-31','gross'=>9750.00,  'net'=>7312.50,'tax'=>1950.00,'deductions'=>487.50,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
            ['id'=>'ps-2026-02','period'=>'February 2026', 'issued'=>'2026-02-28','gross'=>9750.00,  'net'=>7312.50,'tax'=>1950.00,'deductions'=>487.50,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
            ['id'=>'ps-2026-01','period'=>'January 2026',  'issued'=>'2026-01-31','gross'=>9750.00,  'net'=>7440.00,'tax'=>1823.00,'deductions'=>487.00,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
            ['id'=>'ps-2025-12','period'=>'December 2025', 'issued'=>'2025-12-31','gross'=>13250.00, 'net'=>9812.50,'tax'=>2950.00,'deductions'=>487.50,'status'=>'Paid','method'=>'ACH ••3421','note'=>'Includes annual bonus'],
            ['id'=>'ps-2025-11','period'=>'November 2025', 'issued'=>'2025-11-30','gross'=>9500.00,  'net'=>7125.00,'tax'=>1900.00,'deductions'=>475.00,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
            ['id'=>'ps-2025-10','period'=>'October 2025',  'issued'=>'2025-10-31','gross'=>9500.00,  'net'=>7125.00,'tax'=>1900.00,'deductions'=>475.00,'status'=>'Paid','method'=>'ACH ••3421','note'=>null],
        ];
    }

    private function employeeDirectory(): array
    {
        return [
            ['id'=>'d-1', 'name'=>'Layla Haddad',    'role'=>'Senior Product Designer','dept'=>'Design',      'empNumber'=>'M-00412','email'=>'layla.h@meridian.co',   'join'=>'2022-03-14','c'=>0,'initials'=>'LH'],
            ['id'=>'t-1', 'name'=>'Adrian Okafor',   'role'=>'Design Director',        'dept'=>'Design',      'empNumber'=>'M-00098','email'=>'adrian.o@meridian.co',  'join'=>'2019-06-01','c'=>1,'initials'=>'AO'],
            ['id'=>'t-2', 'name'=>'Priya Ramaswamy', 'role'=>'Senior Designer',        'dept'=>'Design',      'empNumber'=>'M-00204','email'=>'priya.r@meridian.co',   'join'=>'2021-09-12','c'=>2,'initials'=>'PR'],
            ['id'=>'t-3', 'name'=>'Marcus Chen',     'role'=>'Product Designer',       'dept'=>'Design',      'empNumber'=>'M-00387','email'=>'marcus.c@meridian.co',  'join'=>'2023-01-20','c'=>3,'initials'=>'MC'],
            ['id'=>'t-4', 'name'=>'Sofía Vargas',    'role'=>'Design Engineer',        'dept'=>'Design',      'empNumber'=>'M-00401','email'=>'sofia.v@meridian.co',   'join'=>'2022-11-04','c'=>4,'initials'=>'SV'],
            ['id'=>'t-5', 'name'=>'Tomás Bergström', 'role'=>'UX Researcher',          'dept'=>'Design',      'empNumber'=>'M-00420','email'=>'tomas.b@meridian.co',   'join'=>'2024-02-18','c'=>5,'initials'=>'TB'],
            ['id'=>'t-6', 'name'=>'Aiko Tanaka',     'role'=>'Brand Designer',         'dept'=>'Design',      'empNumber'=>'M-00444','email'=>'aiko.t@meridian.co',    'join'=>'2024-07-08','c'=>6,'initials'=>'AT'],
            ['id'=>'d-2', 'name'=>'Jonas Wright',    'role'=>'Engineering Lead',       'dept'=>'Engineering', 'empNumber'=>'M-00112','email'=>'jonas.w@meridian.co',   'join'=>'2018-04-22','c'=>2,'initials'=>'JW'],
            ['id'=>'d-3', 'name'=>'Hana Park',       'role'=>'Senior Engineer',        'dept'=>'Engineering', 'empNumber'=>'M-00255','email'=>'hana.p@meridian.co',    'join'=>'2022-08-15','c'=>3,'initials'=>'HP'],
            ['id'=>'d-4', 'name'=>'Reuben Eze',      'role'=>'Backend Engineer',       'dept'=>'Engineering', 'empNumber'=>'M-00301','email'=>'reuben.e@meridian.co',  'join'=>'2023-05-02','c'=>1,'initials'=>'RE'],
            ['id'=>'d-5', 'name'=>'Mira Lindqvist',  'role'=>'Product Manager',        'dept'=>'Product',     'empNumber'=>'M-00187','email'=>'mira.l@meridian.co',    'join'=>'2021-02-09','c'=>4,'initials'=>'ML'],
            ['id'=>'d-6', 'name'=>'Carlos Mendoza',  'role'=>'Senior PM',              'dept'=>'Product',     'empNumber'=>'M-00134','email'=>'carlos.m@meridian.co',  'join'=>'2020-10-26','c'=>5,'initials'=>'CM'],
            ['id'=>'d-7', 'name'=>'Naledi Dlamini',  'role'=>'People Operations',      'dept'=>'People',      'empNumber'=>'M-00050','email'=>'naledi.d@meridian.co',  'join'=>'2017-09-03','c'=>6,'initials'=>'ND'],
            ['id'=>'d-8', 'name'=>'Ines Costa',      'role'=>'Talent Partner',         'dept'=>'People',      'empNumber'=>'M-00310','email'=>'ines.c@meridian.co',    'join'=>'2023-06-19','c'=>0,'initials'=>'IC'],
            ['id'=>'d-9', 'name'=>'Kenji Watanabe',  'role'=>'Finance Manager',        'dept'=>'Finance',     'empNumber'=>'M-00088','email'=>'kenji.w@meridian.co',   'join'=>'2018-11-11','c'=>1,'initials'=>'KW'],
            ['id'=>'d-10','name'=>'Yara El-Sayed',   'role'=>'Accountant',             'dept'=>'Finance',     'empNumber'=>'M-00277','email'=>'yara.e@meridian.co',    'join'=>'2022-12-01','c'=>2,'initials'=>'YE'],
        ];
    }

    private function activity(): array
    {
        return [
            ['id'=>'a-1','who'=>'Adrian Okafor',  'c'=>1,   'action'=>'approved your leave request',          'target'=>'Apr 13 – 17 (Annual)', 'when'=>'2 hours ago'],
            ['id'=>'a-2','who'=>'Payroll',         'c'=>null,'action'=>'issued your payslip for',               'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-3','who'=>'Marcus Chen',     'c'=>3,   'action'=>'submitted a timesheet for your review', 'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-4','who'=>'Adrian Okafor',   'c'=>1,   'action'=>"approved Sofía's timesheet for",        'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-5','who'=>'Naledi Dlamini',  'c'=>6,   'action'=>'uploaded a new policy document',        'target'=>'Remote Work Policy v2','when'=>'Apr 28'],
        ];
    }

    // ── Shared Inertia props ──────────────────────────────────────────

    private function sharedProps(): array
    {
        return [
            'hrRole' => $this->hrRole(),
            'me'     => $this->me(),
        ];
    }

    // ── Page handlers ─────────────────────────────────────────────────

    public function dashboard()
    {
        return Inertia::render('MeridianHR/Dashboard', array_merge($this->sharedProps(), [
            'hrPage'           => 'dashboard',
            'stats'            => ['headcount' => 264, 'onLeaveToday' => 9, 'pendingRequests' => 14, 'nextPayDate' => 'Friday, May 29', 'nextPayFormatted' => '$7,312'],
            'activity'         => $this->activity(),
            'pendingLeaves'    => $this->pendingLeaves(),
            'pendingTimesheets'=> $this->pendingTimesheets(),
            'leaveBalance'     => $this->leaveBalance(),
        ]));
    }

    public function leave()
    {
        return Inertia::render('MeridianHR/Leave', array_merge($this->sharedProps(), [
            'hrPage'       => 'leave',
            'leaveBalance' => $this->leaveBalance(),
            'leaves'       => $this->leaves(),
        ]));
    }

    public function storeLeave(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
            'note' => 'nullable|string|max:500',
        ]);

        // In production: persist to hr_leave_requests
        return back()->with('success', 'Leave request submitted.');
    }

    public function timesheet()
    {
        return Inertia::render('MeridianHR/Timesheet', array_merge($this->sharedProps(), [
            'hrPage'     => 'timesheet',
            'timesheets' => $this->timesheets(),
        ]));
    }

    public function submitTimesheet(Request $request)
    {
        $request->validate(['month' => 'required|string']);
        // In production: mark timesheet as submitted
        return back()->with('success', 'Timesheet submitted.');
    }

    public function documents()
    {
        return Inertia::render('MeridianHR/Documents', array_merge($this->sharedProps(), [
            'hrPage'     => 'documents',
            'categories' => $this->documentCategories(),
        ]));
    }

    public function payslips()
    {
        return Inertia::render('MeridianHR/Payslips', array_merge($this->sharedProps(), [
            'hrPage'   => 'payslips',
            'payslips' => $this->payslipData(),
        ]));
    }

    public function employee()
    {
        $employees = Employee::active()
            ->with(['department', 'designation', 'directorate', 'functionalArea', 'salutation', 'maritalStatus', 'nationality', 'gender', 'entity', 'contractType'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($emp) {
                return [
                    'id'                => $emp->id,
                    'name'              => $emp->full_name,
                    
                    // Basic Information
                    'firstName'         => $emp->first_name,
                    'middleName'        => $emp->middle_name,
                    'lastName'          => $emp->last_name,
                    'salutation_id'     => $emp->salutation_id,
                    'salutation'        => $emp->salutation->title ?? null,
                    'empNumber'         => $emp->employee_number,
                    'agreementNumber'   => $emp->agreement_number,
                    
                    // Contact Information
                    'email'             => $emp->work_email_address,
                    'personalEmail'     => $emp->personal_email_address,
                    'phone_area_code'   => $emp->phone_area_code,
                    'phone'             => $emp->phone_number,
                    'alt_area_code'     => $emp->alt_area_code,
                    'altPhone'          => $emp->alt_phone_number,
                    
                    // Employment Details
                    'designation_id'    => $emp->designation_id,
                    'role'              => $emp->designation->name ?? 'N/A',
                    'department_id'     => $emp->department_id,
                    'dept'              => $emp->department->name ?? 'N/A',
                    'directorate_id'    => $emp->directorate_id,
                    'directorate'       => $emp->directorate->title ?? null,
                    'functional_area_id'=> $emp->functional_area_id,
                    'functionalArea'    => $emp->functionalArea->title ?? null,
                    'salary_basis_id'   => $emp->salary_basis_id,
                    'employee_type'     => $emp->employee_type,
                    'entity'            => $emp->entity->title ?? null,
                    'entityId'          => $emp->entity_id,
                    'contractType'      => $emp->contractType->title ?? null,
                    'contractTypeId'    => $emp->contract_type_id,
                    'reporting_to_id'   => $emp->reporting_to_id,
                    
                    // Contract & Dates
                    'contractStart'     => $emp->contract_start_date?->format('Y-m-d'),
                    'contractEnd'       => $emp->contract_end_date?->format('Y-m-d'),
                    'dateOfHire'        => $emp->date_of_hire?->format('Y-m-d'),
                    'joinDate'          => $emp->join_date?->format('Y-m-d'),
                    
                    // Personal Information
                    'gender_id'         => $emp->gender_id,
                    'gender'            => $emp->gender->title ?? null,
                    'marital_status_id' => $emp->marital_status_id,
                    'maritalStatus'     => $emp->maritalStatus->title ?? null,
                    'dateOfBirth'       => $emp->date_of_birth?->format('Y-m-d'),
                    'town_of_birth'     => $emp->town_of_birth,
                    'country_of_birth'  => $emp->country_of_birth,
                    'nationality_id'    => $emp->nationality_id,
                    'nationalityName'   => $emp->nationality->nationality ?? null,
                    'nationalityCode'   => $emp->nationality->alpha_2_code ?? null,
                    'language_id'       => $emp->language_id,
                    
                    // Identification
                    'nationalId'        => $emp->national_identifier_number,
                    'passportNumber'    => $emp->passport_number,
                    'passportExpiry'    => $emp->passport_expiry?->format('Y-m-d'),
                    'civilIdExpiry'     => $emp->civil_id_expiry?->format('Y-m-d'),
                    
                    // Sponsorship
                    'sponsorship_id'    => $emp->sponsorship_id,
                    'sponsorshipName'   => $emp->sponsorship_name,
                    
                    // Flags
                    'managerFlag'       => $emp->manager_flag,
                    'adminFlag'         => $emp->administrator_flag,
                    
                    // UI
                    'c'                 => $emp->avatar_color,
                    'initials'          => $emp->initials,
                ];
            });

        $salutations = Salutation::orderBy('title')->get()->map(function ($sal) {
            return ['id' => $sal->id, 'title' => $sal->title];
        });

        $designations = Designation::orderBy('name')->get()->map(function ($d) {
            return ['id' => $d->id, 'name' => $d->name];
        });

        $departments = Department::orderBy('name')->get()->map(function ($d) {
            return ['id' => $d->id, 'name' => $d->name];
        });

        $directorates = Directorate::orderBy('title')->get()->map(function ($d) {
            return ['id' => $d->id, 'title' => $d->title];
        });

        $functionalAreas = FunctionalArea::orderBy('title')->get()->map(function ($f) {
            return ['id' => $f->id, 'title' => $f->title];
        });

        $entities = EmployeeEntity::orderBy('title')->get()->map(function ($e) {
            return ['id' => $e->id, 'title' => $e->title];
        });

        $employeeTypes = EmployeeType::orderBy('title')->get()->map(function ($e) {
            return ['id' => $e->id, 'title' => $e->title];
        });

        $contractTypes = EmployeeContractType::orderBy('title')->get()->map(function ($c) {
            return ['id' => $c->id, 'title' => $c->title];
        });

        $salaryBases = SalaryBasis::orderBy('title')->get()->map(function ($s) {
            return ['id' => $s->id, 'title' => $s->title];
        });

        $genders = Gender::orderBy('title')->get()->map(function ($g) {
            return ['id' => $g->id, 'title' => $g->title];
        });

        $maritalStatuses = MaritalStatus::orderBy('title')->get()->map(function ($m) {
            return ['id' => $m->id, 'title' => $m->title];
        });

        $nationalities = Nationality::orderBy('nationality')->get()->map(function ($n) {
            return ['id' => $n->id, 'nationality' => $n->nationality];
        });

        $countries = Country::orderBy('country_name')->get()->map(function ($c) {
            return ['id' => $c->id, 'name' => $c->country_name];
        });

        $reportingToOptions = Employee::active()
            ->where('manager_flag', 'Y')
            ->orderBy('full_name')
            ->get()
            ->map(function ($e) {
                return ['id' => $e->id, 'name' => $e->full_name];
            });

        return Inertia::render('MeridianHR/Employee', array_merge($this->sharedProps(), [
            'hrPage'            => 'employee',
            'employees'         => $employees,
            'salutations'       => $salutations,
            'designations'      => $designations,
            'departments'       => $departments,
            'directorates'      => $directorates,
            'functionalAreas'   => $functionalAreas,
            'entities'          => $entities,
            'employeeTypes'     => $employeeTypes,
            'contractTypes'     => $contractTypes,
            'salaryBases'       => $salaryBases,
            'genders'           => $genders,
            'maritalStatuses'   => $maritalStatuses,
            'nationalities'     => $nationalities,
            'countries'         => $countries,
            'reportingToOptions'=> $reportingToOptions,
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic Information
            'first_name'                => 'required|string|max:50',
            'middle_name'               => 'nullable|string|max:100',
            'last_name'                 => 'required|string|max:50',
            'employee_number'           => 'required|string|max:15|unique:employees_all,employee_number',
            'agreement_number'          => 'nullable|string|max:45',
            'salutation_id'             => 'nullable|integer',
            
            // Contact Information
            'work_email_address'        => 'required|email|max:250|unique:employees_all,work_email_address',
            'personal_email_address'    => 'nullable|email|max:240',
            'phone_number'              => 'nullable|string|max:50',
            'alt_phone_number'          => 'nullable|string|max:50',
            'phone_area_code'           => 'nullable|string|max:10',
            'alt_area_code'             => 'nullable|string|max:10',
            
            // Employment Details
            'designation_id'            => 'nullable|integer|exists:designations,id',
            'department_id'             => 'nullable|integer|exists:departments,id',
            'directorate_id'            => 'nullable|integer',
            'functional_area_id'        => 'nullable|integer',
            'salary_basis_id'           => 'nullable|integer',
            'employee_type'             => 'nullable|integer',
            'entity_id'                 => 'nullable|integer',
            'contract_type_id'          => 'nullable|integer',
            'reporting_to_id'           => 'nullable|integer|exists:employees_all,id',
            
            // Contract & Dates
            'contract_start_date'       => 'nullable|date',
            'contract_end_date'         => 'nullable|date',
            'date_of_hire'              => 'nullable|date',
            'join_date'                 => 'nullable|date',
            
            // Personal Information
            'gender_id'                 => 'nullable|string|max:11',
            'marital_status_id'         => 'nullable|integer',
            'date_of_birth'             => 'nullable|date',
            'town_of_birth'             => 'nullable|string|max:100',
            'country_of_birth'          => 'nullable|string|max:11',
            'nationality_id'            => 'nullable|integer',
            'language_id'               => 'nullable|integer',
            
            // Identification
            'national_identifier_number'=> 'nullable|string|max:100',
            'passport_number'           => 'nullable|string|max:50',
            'passport_expiry'           => 'nullable|date',
            'civil_id_expiry'           => 'nullable|date',
            
            // Sponsorship
            'sponsorship_id'            => 'nullable|string|max:150',
            'sponsorship_name'          => 'nullable|string|max:100',
            
            // Flags
            'manager_flag'              => 'nullable|string|max:5',
            'administrator_flag'        => 'nullable|string|max:5',
        ]);

        // Generate full name
        $validated['full_name'] = trim(
            ($validated['first_name'] ?? '') . ' ' . 
            ($validated['middle_name'] ?? '') . ' ' . 
            ($validated['last_name'] ?? '')
        );

        // Set default archived flag
        $validated['archived'] = 'N';

        $employee = Employee::create($validated);

        return redirect()->route('hr.employee')->with('success', 'Employee added successfully.');
    }

    public function edit($id)
    {
        $employee = Employee::with(['department', 'designation', 'directorate', 'functionalArea'])
            ->findOrFail($id);

        return Inertia::render('MeridianHR/EmployeeEdit', array_merge($this->sharedProps(), [
            'hrPage'   => 'employee',
            'employee' => $employee,
        ]));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            // Basic Information
            'first_name'                    => 'required|string|max:50',
            'middle_name'                   => 'nullable|string|max:100',
            'last_name'                     => 'required|string|max:50',
            'salutation_id'                 => 'nullable|integer',
            'employee_number'               => 'required|string|max:15|unique:employees_all,employee_number,' . $id,
            'agreement_number'              => 'nullable|string|max:100',
            
            // Contact Information
            'work_email_address'            => 'required|email|max:250|unique:employees_all,work_email_address,' . $id,
            'personal_email_address'        => 'nullable|email|max:240',
            'phone_area_code'               => 'nullable|string|max:10',
            'phone_number'                  => 'nullable|string|max:50',
            'alt_area_code'                 => 'nullable|string|max:10',
            'alt_phone_number'              => 'nullable|string|max:50',
            
            // Employment Details
            'designation_id'                => 'nullable|integer|exists:designations,id',
            'department_id'                 => 'nullable|integer|exists:departments,id',
            'directorate_id'                => 'nullable|integer',
            'functional_area_id'            => 'nullable|integer',
            'salary_basis_id'               => 'nullable|integer',
            'employee_type'                 => 'nullable|integer',
            'entity_id'                     => 'nullable|integer',
            'contract_type_id'              => 'nullable|integer',
            'reporting_to_id'               => 'nullable|integer|exists:employees_all,id',
            
            // Contract & Dates
            'contract_start_date'           => 'nullable|date',
            'contract_end_date'             => 'nullable|date',
            'date_of_hire'                  => 'nullable|date',
            'join_date'                     => 'nullable|date',
            
            // Personal Information
            'gender_id'                     => 'nullable|string|max:11',
            'marital_status_id'             => 'nullable|integer',
            'date_of_birth'                 => 'nullable|date',
            'town_of_birth'                 => 'nullable|string|max:100',
            'country_of_birth'              => 'nullable|integer',
            'nationality_id'                => 'nullable|integer',
            'language_id'                   => 'nullable|string|max:50',
            
            // Identification
            'national_identifier_number'    => 'nullable|string|max:50',
            'passport_number'               => 'nullable|string|max:50',
            'passport_expiry'               => 'nullable|date',
            'civil_id_expiry'               => 'nullable|date',
            
            // Sponsorship
            'sponsorship_id'                => 'nullable|string|max:50',
            'sponsorship_name'              => 'nullable|string|max:100',
            
            // Flags
            'manager_flag'                  => 'nullable|string|max:5',
            'administrator_flag'            => 'nullable|string|max:5',
        ]);

        // Update full name
        $validated['full_name'] = trim(
            ($validated['first_name'] ?? '') . ' ' . 
            ($validated['middle_name'] ?? '') . ' ' . 
            ($validated['last_name'] ?? '')
        );

        $employee->update($validated);

        return redirect()->route('hr.employee')->with('success', 'Employee updated successfully.');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        
        // Soft delete by marking as archived
        $employee->update(['archived' => 'Y']);

        return redirect()->route('hr.employee')->with('success', 'Employee archived successfully.');
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EmployeesTemplateExport(),
            'employee_import_template.xlsx'
        );
    }

    public function import(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\EmployeesImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $stats = $import->getStats();
            $failures = $import->failures();
            
            $errorMessages = [];
            $failedRows = [];
            
            if (count($failures) > 0) {
                foreach ($failures as $failure) {
                    $errorMessages[] = "Row {$failure->row()}: " . implode(', ', $failure->errors());
                    $failedRows[] = [
                        'row' => $failure->row(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values(),
                    ];
                }
                
                // Store failed rows in session for export
                session(['failed_import_rows' => $failedRows]);
            }

            // Return stats as JSON
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'errors' => $errorMessages,
                'hasFailures' => count($failures) > 0,
                'message' => $stats['success'] > 0 
                    ? "{$stats['success']} employee(s) imported successfully" 
                    : "Import completed",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function exportFailedRows()
    {
        $failedRows = session('failed_import_rows', []);
        
        if (empty($failedRows)) {
            return back()->with('error', 'No failed rows to export.');
        }

        // Clear the session after export
        session()->forget('failed_import_rows');

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FailedEmployeesExport($failedRows),
            'failed_employees_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    public function profile()
    {
        return Inertia::render('MeridianHR/Profile', array_merge($this->sharedProps(), [
            'hrPage'  => 'profile',
            'profile' => [
                'phone'          => '+1 (347) 555-0182',
                'location'       => 'Brooklyn HQ · 4F',
                'dob'            => '14 April 1992',
                'nationality'    => 'Lebanese / American',
                'address'        => '148 Berry St, Brooklyn NY 11211',
                'emergencyName'  => 'Karim Haddad (brother)',
                'emergencyPhone' => '+1 (347) 555-0144',
                'bank'           => 'Capital One Bank',
            ],
        ]));
    }

    public function approvalsLeave()
    {
        return Inertia::render('MeridianHR/Approvals', array_merge($this->sharedProps(), [
            'hrPage' => 'approve-leave',
            'kind'   => 'leave',
            'items'  => $this->pendingLeaves(),
        ]));
    }

    public function approvalsTime()
    {
        return Inertia::render('MeridianHR/Approvals', array_merge($this->sharedProps(), [
            'hrPage' => 'approve-time',
            'kind'   => 'time',
            'items'  => $this->pendingTimesheets(),
        ]));
    }
}
