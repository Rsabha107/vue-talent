<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Country;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Directorate;
use App\Models\Employee;
use App\Models\User;
use App\Models\EmployeeContractType;
use App\Models\EmployeeEntity;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetEntry;
use App\Models\EmployeeType;
use App\Models\FunctionalArea;
use App\Models\Gender;
use App\Models\MaritalStatus;
use App\Models\Nationality;
use App\Models\SalaryBasis;
use App\Models\Salutation;
use App\Services\LeaveBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmployeeController extends BaseHRController
{
    // ── Shared mock data ──────────────────────────────────────────────
    // Note: me() and getHRRole() are inherited from BaseHRController

    /**
     * Create a user account for an employee if one doesn't exist
     * and assign the default 'employee-basic' role.
     */
    protected function createUserForEmployee(Employee $employee): void
    {
        // Check if employee already has a user account
        if ($employee->user_id) {
            return;
        }

        // Check if a user with this email already exists
        $existingUser = User::where('email', $employee->work_email_address)->first();
        
        if ($existingUser) {
            // Link existing user to employee
            $employee->user_id = $existingUser->id;
            $employee->save();
            
            // Ensure user has employee-basic role if they don't have any roles
            if ($existingUser->roles->isEmpty()) {
                $existingUser->assignRole('employee-basic');
            }
        } else {
            // Create new user with default password
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->work_email_address,
                'password' => Hash::make('Welcome@' . date('Y')), // Default password
                'email_verified_at' => now(), // Auto-verify
            ]);
            
            // Assign default employee-basic role
            $user->assignRole('employee-basic');
            
            // Link user to employee
            $employee->user_id = $user->id;
            $employee->save();
        }
    }

    protected function leaveBalance(): array
    {
        $result = [
            'annual'   => ['used' => 0, 'total' => 0, 'pending' => 0],
            'sick'     => ['used' => 0, 'total' => 0, 'pending' => 0],
            'personal' => ['used' => 0, 'total' => 0, 'pending' => 0],
        ];

        $employee = Employee::where('user_id', auth()->id())->first();
        if (!$employee) {
            return $result;
        }

        $eventId = $this->getSelectedEventId();
        $balances = LeaveBalanceService::getEmployeeBalanceSummary($employee->id, $eventId);

        foreach ($balances as $balance) {
            $title = strtolower($balance->leaveType->title ?? '');
            if (str_contains($title, 'annual')) {
                $key = 'annual';
            } elseif (str_contains($title, 'sick')) {
                $key = 'sick';
            } elseif (str_contains($title, 'personal')) {
                $key = 'personal';
            } else {
                continue;
            }
            $result[$key] = [
                'used'    => (int) ($balance->used_days ?? 0),
                'total'   => (int) ($balance->allocated_days ?? 0),
                'pending' => (int) ($balance->pending_days ?? 0),
            ];
        }

        return $result;
    }

    protected function upcomingLeaves(): array
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (!$employee) {
            return [];
        }

        $eventId  = $this->getSelectedEventId();
        $today    = Carbon::today();
        $cutoff   = Carbon::today()->addDays(14);

        return EmployeeLeaveRequest::with(['leaveType', 'status'])
            ->where('employee_id', $employee->id)
            ->where('archived', 'N')
            ->where('date_from', '>=', $today)
            ->where('date_from', '<=', $cutoff)
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->orderBy('date_from')
            ->get()
            ->map(fn ($r) => [
                'id'     => $r->id,
                'type'   => $r->leaveType->title ?? 'Leave',
                'from'   => $r->date_from?->format('Y-m-d'),
                'to'     => $r->date_to?->format('Y-m-d'),
                'days'   => $r->number_of_days ?? 1,
                'status' => $r->status->title ?? 'Pending',
            ])
            ->toArray();
    }

    protected function employeeActivity(): array
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        if (!$employee) {
            return [];
        }

        $eventId = $this->getSelectedEventId();

        return EmployeeLeaveRequest::with(['leaveType', 'status'])
            ->where('employee_id', $employee->id)
            ->where('archived', 'N')
            ->when($eventId, fn ($q) => $q->where('event_id', $eventId))
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'id'     => 'lr-' . $r->id,
                'who'    => $r->status->title ?? 'System',
                'c'      => null,
                'action' => strtolower($r->status->title ?? 'updated') . ' your ' . ($r->leaveType->title ?? 'leave') . ' request',
                'target' => trim(
                    ($r->date_from ? $r->date_from->format('d M') : '') .
                    ($r->date_from && $r->date_to && !$r->date_from->eq($r->date_to)
                        ? ' – ' . $r->date_to->format('d M')
                        : '')
                ),
                'when'   => $r->updated_at?->diffForHumans() ?? '',
            ])
            ->toArray();
    }

    protected function leaves(): array
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

    protected function pendingLeaves(): array
    {
        $effectiveEventIds = $this->getEffectiveEventIds();
        
        // If empty array (manager with no events), return empty result
        if (is_array($effectiveEventIds) && empty($effectiveEventIds)) {
            return [];
        }
        
        $query = EmployeeLeaveRequest::with(['employee', 'leaveType', 'status', 'event'])
            ->forEvent($effectiveEventIds) // Pass effective event IDs
            ->where('status_id', EmployeeLeaveStatus::pendingId())
            ->active()
            ->orderBy('created_at', 'asc');
        
        // Exclude manager's own leave requests from approval queue
        $currentEmployee = Employee::where('user_id', auth()->id())->first();
        if ($currentEmployee) {
            $query->where('employee_id', '!=', $currentEmployee->id);
        }
        
        $leaveRequests = $query->get();

        return $leaveRequests->map(function($leave) {
            $employee = $leave->employee;
            $empName = $employee ? $employee->full_name : 'Unknown';
            $empNumber = $employee ? $employee->employee_number : 'N/A';
            
            // Create initials from first and last name only
            $initials = '';
            if ($employee) {
                $firstInitial = $employee->first_name ? substr($employee->first_name, 0, 1) : '';
                $lastInitial = $employee->last_name ? substr($employee->last_name, 0, 1) : '';
                $initials = strtoupper($firstInitial . $lastInitial);
            }
            
            return [
                'id'         => $leave->id,
                'emp'        => $empName,
                'empId'      => $empNumber,
                'initials'   => $initials,
                'c'          => crc32($empName) % 8, // Color hash
                'type'       => $leave->leaveType ? $leave->leaveType->title : 'Unknown',
                'from'       => $leave->date_from->format('Y-m-d'),
                'to'         => $leave->date_to->format('Y-m-d'),
                'days'       => $leave->number_of_days,
                'filed'      => $leave->created_at->format('Y-m-d'),
                'note'       => $leave->reason ?? '',
                'eventName'  => $leave->event ? $leave->event->name : null,
                'hasOverlap' => false, // TODO: Implement overlap detection
                'balance'    => null, // TODO: Fetch remaining balance if needed
            ];
        })->toArray();
    }

    protected function pendingTimesheets(): array
    {
        $effectiveEventIds = $this->getEffectiveEventIds();
        
        // If empty array (manager with no events), return empty result
        if (is_array($effectiveEventIds) && empty($effectiveEventIds)) {
            return [];
        }
        
        $query = \App\Models\EmployeeTimesheet::with(['employee', 'status', 'event'])
            ->forEvent($effectiveEventIds) // Pass effective event IDs
            ->where('status_id', \App\Models\EmployeeTimesheetStatus::submittedId())
            ->active()
            ->orderBy('created_at', 'asc');
        
        // Exclude manager's own timesheets from approval queue
        $currentEmployee = Employee::where('user_id', auth()->id())->first();
        if ($currentEmployee) {
            $query->where('employee_id', '!=', $currentEmployee->id);
        }
        
        $timesheets = $query->get();

        return $timesheets->map(function($timesheet) {
            $employee = $timesheet->employee;
            $empName = $employee ? $employee->full_name : 'Unknown';
            $empNumber = $employee ? $employee->employee_number : 'N/A';
            
            return [
                'id'        => $timesheet->id,
                'emp'       => $empName,
                'empId'     => $empNumber,
                'c'         => crc32($empName) % 8, // Color hash
                'period'    => $timesheet->month_year ? $timesheet->month_year->format('F Y') : 'N/A',
                'worked'    => $timesheet->days_worked ?? 0,
                'leave'     => $timesheet->days_leave ?? 0,
                'unpaid'    => $timesheet->days_unpaid ?? 0,
                'projects'  => 0, // Not tracked in current schema
                'submitted' => $timesheet->created_at ? $timesheet->created_at->format('Y-m-d') : 'N/A',
                'note'      => $timesheet->description ?? '',
                'eventName' => $timesheet->event ? $timesheet->event->name : null,
            ];
        })->toArray();
    }

    protected function headcountByDepartment(): array
    {
        $eventId = $this->getSelectedEventId();
        $today = Carbon::today();
        
        // Get employees grouped by department with leave counts
        $departments = Department::where('active_flag', 1)
            ->withCount(['employees' => function ($query) use ($eventId) {
                $query->where('archived', 'N');
                if ($eventId) {
                    $query->whereHas('events', function ($eq) use ($eventId) {
                        $eq->where('events.id', $eventId)
                            ->where('employee_events.is_active', 1);
                    });
                } else {
                    // Count employees with any active event assignment
                    $query->whereHas('events', function ($eq) {
                        $eq->where('employee_events.is_active', 1);
                    });
                }
            }])
            ->orderBy('name')
            ->get();
        
        $maxCount = $departments->max('employees_count') ?: 1;
        
        $result = [];
        
        foreach ($departments as $dept) {
            if ($dept->employees_count > 0) {
                // Count employees on leave today for this department
                $onLeaveToday = Employee::where('archived', 'N')
                    ->when($eventId, function ($query) use ($eventId, $dept) {
                        return $query->whereHas('events', function ($eq) use ($eventId, $dept) {
                            $eq->where('events.id', $eventId)
                                ->where('employee_events.is_active', 1)
                                ->where('employee_events.department_id', $dept->id);
                        });
                    }, function ($query) use ($dept) {
                        // No specific event, count across all active assignments for this dept
                        return $query->whereHas('events', function ($eq) use ($dept) {
                            $eq->where('employee_events.is_active', 1)
                                ->where('employee_events.department_id', $dept->id);
                        });
                    })
                    ->whereHas('leaveRequests', function ($query) use ($today) {
                        $query->where('status_id', EmployeeLeaveStatus::approvedId())
                            ->where('date_from', '<=', $today)
                            ->where('date_to', '>=', $today);
                    })
                    ->count();
                
                $result[] = [
                    'name' => $dept->name,
                    'count' => $dept->employees_count,
                    'leave' => $onLeaveToday,
                    'color' => $this->getDepartmentColor($dept->name),
                ];
            }
        }
        
        return $result;
    }
    
    protected function getOnLeaveToday(): array
    {
        $eventId = $this->getSelectedEventId();
        $today = Carbon::today();
        
        // Get all approved leave requests for today with leave type
        $leaveRequests = EmployeeLeaveRequest::join('employee_leave_types', 'employee_leave_requests.leave_type_id', '=', 'employee_leave_types.id')
            ->join('employee_leave_status', 'employee_leave_requests.status_id', '=', 'employee_leave_status.id')
            ->where('employee_leave_status.title', 'Approved')
            ->where('employee_leave_requests.date_from', '<=', $today)
            ->where('employee_leave_requests.date_to', '>=', $today)
            ->whereHas('employee', function ($query) {
                $query->where('archived', 'N');
            })
            ->when($eventId, function ($query) use ($eventId) {
                return $query->where('employee_leave_requests.event_id', $eventId);
            })
            ->select('employee_leave_requests.*', 'employee_leave_types.title as leave_type_title')
            ->get();
        
        // Group by leave type and count
        $breakdown = $leaveRequests->groupBy('leave_type_title')
            ->map(function ($group) {
                return $group->count();
            })
            ->toArray();
        
        return [
            'total' => $leaveRequests->count(),
            'breakdown' => $breakdown,
        ];
    }
    
    private function getDepartmentColor(string $deptName): string
    {
        // Assign consistent colors based on department name
        $colors = [
            'Engineering' => '#3a6c8c',
            'Product' => '#8a5b9c',
            'Design' => '#4f8a55',
            'People' => '#b6772b',
            'Finance' => '#a8413a',
            'Operations' => '#5e6b3b',
            'HR' => '#b6772b',
            'Human Resources' => '#b6772b',
            'IT' => '#3a6c8c',
            'Marketing' => '#8a5b9c',
            'Sales' => '#5e6b3b',
        ];
        
        // If department name matches predefined colors, use it
        if (isset($colors[$deptName])) {
            return $colors[$deptName];
        }
        
        // Otherwise, generate a unique color based on department name hash
        $colorPalette = [
            // Blues & Teals
            '#3a6c8c', '#2d5a7b', '#4a7c9e', '#1e4d6b', '#5b8aab', '#2a5c7d', '#4277a1', '#1a3e5c',
            
            // Purples & Violets
            '#8a5b9c', '#7a4a8c', '#9a6bac', '#6a3b7c', '#aa7bbc', '#5a2b6c', '#ba8bcc', '#4a1b5c',
            
            // Greens
            '#4f8a55', '#3e7845', '#5f9a65', '#2e6835', '#6faa75', '#1e5825', '#7fba85', '#0e4815',
            
            // Oranges & Browns
            '#b6772b', '#a66620', '#c6873b', '#965610', '#d6974b', '#864600', '#e6a75b', '#763600',
            
            // Reds & Magentas
            '#a8413a', '#983530', '#b8514a', '#882520', '#c86150', '#781510', '#d87160', '#680500',
            
            // Olive & Yellow-Greens
            '#5e6b3b', '#4e5b2b', '#6e7b4b', '#3e4b1b', '#7e8b5b', '#2e3b0b', '#8e9b6b', '#1e2b00',
            
            // Cyan & Aqua
            '#2d8a8a', '#1d7a7a', '#3d9a9a', '#0d6a6a', '#4daaaa', '#005a5a', '#5dbaba', '#004a4a',
            
            // Pink & Rose
            '#c85a7b', '#b84a6b', '#d86a8b', '#a83a5b', '#e87a9b', '#982a4b', '#f88aab', '#881a3b',
            
            // Indigo & Navy
            '#4a5a8c', '#3a4a7c', '#5a6a9c', '#2a3a6c', '#6a7aac', '#1a2a5c', '#7a8abc', '#0a1a4c',
            
            // Slate & Charcoal
            '#5a6a7a', '#4a5a6a', '#6a7a8a', '#3a4a5a', '#7a8a9a', '#2a3a4a', '#8a9aaa', '#1a2a3a',
        ];
        
        $hash = crc32($deptName);
        $index = abs($hash) % count($colorPalette);
        
        return $colorPalette[$index];
    }



    protected function documentCategories(): array
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

    protected function payslipData(): array
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

    protected function employeeDirectory(): array
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

    protected function activity(): array
    {
        return [
            ['id'=>'a-1','who'=>'Adrian Okafor',  'c'=>1,   'action'=>'approved your leave request',          'target'=>'Apr 13 – 17 (Annual)', 'when'=>'2 hours ago'],
            ['id'=>'a-2','who'=>'Payroll',         'c'=>null,'action'=>'issued your payslip for',               'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-3','who'=>'Marcus Chen',     'c'=>3,   'action'=>'submitted a timesheet for your review', 'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-4','who'=>'Adrian Okafor',   'c'=>1,   'action'=>"approved Sofía's timesheet for",        'target'=>'April 2026',           'when'=>'Apr 30'],
            ['id'=>'a-5','who'=>'Naledi Dlamini',  'c'=>6,   'action'=>'uploaded a new policy document',        'target'=>'Remote Work Policy v2','when'=>'Apr 28'],
        ];
    }

    // ── Page handlers ─────────────────────────────────────────────────
    // Note: sharedProps() replaced by getCommonProps() from BaseHRController

    public function dashboard()
    {
        $pendingCounts = $this->getPendingCounts();
        $totalPendingRequests = $pendingCounts['pendingLeaves'] + $pendingCounts['pendingTimesheets'];
        $onLeaveData = $this->getOnLeaveToday();
        
        // Get real headcount from database
        $eventId = $this->getSelectedEventId();
        $headcount = Employee::where('archived', 'N')
            ->forEvent($eventId)
            ->count();

        Log::debug('onleave breakdown: ' . json_encode($onLeaveData['breakdown']));
        
        $isEmployee = !in_array($this->getHRRole(), ['admin', 'manager']);

        // Get next pay information from approved timesheets
        $currentEmployee = Employee::where('user_id', auth()->id())->first();
        $nextPayDate = 'Not available';
        $nextPayFormatted = '$0.00';
        
        if ($currentEmployee) {
            // Get the most recent approved timesheet
            $approvedStatusId = \App\Models\EmployeeTimesheetStatus::approvedId();
            
            $latestTimesheet = EmployeeTimesheet::where('employee_id', $currentEmployee->id)
                ->where('status_id', $approvedStatusId)
                ->where('archived', 'N')
                ->orderBy('year', 'DESC')
                ->orderBy('month_id', 'DESC')
                ->first();
            
            if ($latestTimesheet && $latestTimesheet->total_payment) {
                $nextPayFormatted = '$' . number_format($latestTimesheet->total_payment, 2, '.', ',');
                
                // Calculate pay date as last day of the timesheet month
                $payDate = Carbon::create($latestTimesheet->year, $latestTimesheet->month_id, 1)->endOfMonth();
                $nextPayDate = $payDate->format('l, F j'); // e.g., "Friday, May 31"
            }
        }

        return Inertia::render('MeridianHR/Dashboard', array_merge($this->getCommonProps('dashboard'), [
            'stats'               => [
                'headcount'        => $headcount,
                'onLeaveToday'     => $onLeaveData['total'],
                'onLeaveBreakdown' => $onLeaveData['breakdown'],
                'pendingRequests'  => $totalPendingRequests,
                'nextPayDate'      => $nextPayDate,
                'nextPayFormatted' => $nextPayFormatted,
            ],
            'activity'            => $isEmployee ? $this->employeeActivity() : $this->activity(),
            'upcomingLeaves'      => $isEmployee ? $this->upcomingLeaves() : [],
            'pendingLeaves'       => $this->pendingLeaves(),
            'pendingTimesheets'   => $this->pendingTimesheets(),
            'leaveBalance'        => $this->leaveBalance(),
            'headcountByDept'     => $this->headcountByDepartment(),
        ]));
    }

    public function leave()
    {
        return Inertia::render('MeridianHR/Leave', array_merge($this->getCommonProps('leave'), [
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

    // ── Timesheet methods moved to TimesheetController ────────────────

    public function documents()
    {
        return Inertia::render('MeridianHR/Documents', array_merge($this->getCommonProps('documents'), [
            'categories' => $this->documentCategories(),
        ]));
    }

    public function payslips()
    {
        return Inertia::render('MeridianHR/Payslips', array_merge($this->getCommonProps('payslips'), [
            'payslips' => $this->payslipData(),
        ]));
    }

    public function employee()
    {
        $eventId = $this->getSelectedEventId(); // Get selected event (null = "All Events")
        $isAllEvents = $eventId === null;
        
        // Base query for active employees
        $query = Employee::active()
            ->with(['salutation', 'maritalStatus', 'nationality', 'gender'])
            ->withCount(['documents as documents_count' => function ($query) use ($eventId) {
                $query->where('active_flag', 1);
                if ($eventId) {
                    $query->where('event_id', $eventId);
                }
            }]);
        
        // For All Events view, eager load all events with names
        if ($isAllEvents) {
            $query->with(['events' => function ($q) {
                $q->where('employee_events.is_active', 1)
                  ->select('events.id', 'events.name')
                  ->orderBy('events.name');
            }]);
        }
        
        // Filter by event assignment only if specific event is selected
        if (!$isAllEvents) {
            $query->whereHas('events', function ($q) use ($eventId) {
                $q->where('events.id', $eventId)
                  ->where('employee_events.is_active', 1);
            })->with(['events' => function ($q) use ($eventId) {
                $q->where('events.id', $eventId)
                  ->select('events.id', 'events.name')
                  ->withPivot([
                      'assigned_at', 'released_at', 'is_active',
                      'agreement_number', 'department_id', 'designation_id',
                      'directorate_id', 'functional_area_id', 'job_level_id',
                      'reporting_to_id', 'entity_id', 'contract_type_id',
                      'employee_type', 'salary_basis_id'
                  ]);
            }]);
        }
        
        $employees = $query->orderBy('full_name')
            ->get()
            ->map(function ($emp) use ($eventId, $isAllEvents) {
                // Get event-specific pivot data
                $pivotData = null;
                $eventName = null;
                if (!$isAllEvents && $emp->relationLoaded('events') && $emp->events->isNotEmpty()) {
                    $event = $emp->events->first();
                    $pivotData = $event->pivot;
                    $eventName = $event->name;
                }
                
                // For event context, get organizational data from pivot
                // For All Events context, show as unassigned (null values)
                $departmentId = $pivotData?->department_id ?? null;
                $designationId = $pivotData?->designation_id ?? null;
                $directorateId = $pivotData?->directorate_id ?? null;
                $functionalAreaId = $pivotData?->functional_area_id ?? null;
                $reportingToId = $pivotData?->reporting_to_id ?? null;
                $entityId = $pivotData?->entity_id ?? null;
                $contractTypeId = $pivotData?->contract_type_id ?? null;
                $agreementNumber = $pivotData?->agreement_number ?? null;
                $employeeType = $pivotData?->employee_type ?? null;
                $salaryBasisId = $pivotData?->salary_basis_id ?? null;
                
                // Lazy load related models only when needed
                $department = $departmentId ? Department::find($departmentId) : null;
                $designation = $designationId ? Designation::find($designationId) : null;
                $directorate = $directorateId ? Directorate::find($directorateId) : null;
                $functionalArea = $functionalAreaId ? FunctionalArea::find($functionalAreaId) : null;
                $reportingTo = $reportingToId ? Employee::find($reportingToId) : null;
                $entity = $entityId ? EmployeeEntity::find($entityId) : null;
                $contractType = $contractTypeId ? EmployeeContractType::find($contractTypeId) : null;
                
                return [
                    'id'                => $emp->id,
                    'name'              => $emp->full_name,
                    'eventName'         => $eventName,
                    
                    // Events assigned (for All Events view)
                    'eventsAssigned'    => $isAllEvents && $emp->relationLoaded('events') 
                        ? $emp->events->pluck('name')->toArray()
                        : [],
                    'eventsCount'       => $isAllEvents && $emp->relationLoaded('events')
                        ? $emp->events->count()
                        : 0,
                    'eventIds'          => $isAllEvents && $emp->relationLoaded('events')
                        ? $emp->events->pluck('id')->toArray()
                        : ($emp->relationLoaded('events') ? $emp->events->pluck('id')->toArray() : []),
                    
                    // Event-specific data (from pivot)
                    'assignedAt'        => $pivotData?->assigned_at,
                    'releasedAt'        => $pivotData?->released_at,
                    
                    // Basic Information (from employees table)
                    'firstName'         => $emp->first_name,
                    'middleName'        => $emp->middle_name,
                    'lastName'          => $emp->last_name,
                    'salutation_id'     => $emp->salutation_id,
                    'salutation'        => $emp->salutation->title ?? null,
                    'empNumber'         => $emp->employee_number,
                    
                    // Contact Information (from employees table)
                    'email'             => $emp->work_email_address,
                    'workEmail'         => $emp->work_email_address,
                    'personalEmail'     => $emp->personal_email_address,
                    'mobileNumber'      => $emp->mobile_number,
                    'phone_area_code'   => $emp->phone_area_code,
                    'phone'             => $emp->phone_number,
                    'alt_area_code'     => $emp->alt_area_code,
                    'altPhone'          => $emp->alt_phone_number,
                    
                    // Employment Details (from pivot when in event context)
                    'agreementNumber'   => $agreementNumber,
                    'designation_id'    => $designationId,
                    'role'              => $designation?->name ?? ($isAllEvents ? 'Unassigned' : 'N/A'),
                    'department_id'     => $departmentId,
                    'dept'              => $department?->name ?? ($isAllEvents ? 'Unassigned' : 'N/A'),
                    'directorate_id'    => $directorateId,
                    'directorate'       => $directorate?->title ?? null,
                    'functional_area_id'=> $functionalAreaId,
                    'functionalArea'    => $functionalArea?->title ?? null,
                    'entity'            => $entity?->title ?? null,
                    'entityId'          => $entityId,
                    'contractType'      => $contractType?->title ?? null,
                    'contractTypeId'    => $contractTypeId,
                    'reporting_to_id'   => $reportingToId,
                    'reportingTo'       => $reportingTo?->full_name ?? null,
                    'employeeType'      => $employeeType,
                    'salaryBasisId'     => $salaryBasisId,
                    
                    // Contract & Dates (from pivot when in event context)
                    'contractStart'     => $pivotData?->assigned_at,
                    'contractEnd'       => $pivotData?->released_at,
                    'dateOfHire'        => $emp->date_of_hire?->format('Y-m-d'),
                    'joinDate'          => $emp->join_date?->format('Y-m-d'),
                    
                    // Personal Information (from employees table)
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
                    
                    // Identification (from employees table)
                    'nationalId'        => $emp->national_identifier_number,
                    'passportNumber'    => $emp->passport_number,
                    'passportExpiry'    => $emp->passport_expiry?->format('Y-m-d'),
                    'civilIdExpiry'     => $emp->civil_id_expiry?->format('Y-m-d'),
                    
                    // Sponsorship (from employees table)
                    'sponsorship_id'    => $emp->sponsorship_id,
                    'sponsorshipName'   => $emp->sponsorship_name,
                    
                    // Flags (from employees table)
                    'managerFlag'       => $emp->manager_flag,
                    'adminFlag'         => $emp->administrator_flag,
                    
                    // UI
                    'c'                 => $emp->avatar_color,
                    'initials'          => $emp->initials,
                    
                    // Documents
                    'documentsCount'    => $emp->documents_count ?? 0,
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

        $sponsorships = EmployeeSponsorship::where('active_flag', 1)
            ->orderBy('title')
            ->get()
            ->map(function ($s) {
                return ['id' => $s->id, 'name' => $s->title];
            });

        $reportingToOptions = Employee::active()
            ->where('manager_flag', 'Y')
            ->orderBy('full_name')
            ->get()
            ->map(function ($e) {
                return ['id' => $e->id, 'name' => $e->full_name];
            });

        return Inertia::render('MeridianHR/Employee', array_merge($this->getCommonProps('employee'), [
            'employees'         => $employees,
            'isAllEvents'       => $isAllEvents, // Flag to show/hide import/export
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
            'sponsorships'      => $sponsorships,
            'reportingToOptions'=> $reportingToOptions,
        ]));
    }
    
    /**
     * Legacy masterEmployee method - redirects to main employee() method
     * Kept for backwards compatibility with existing routes
     * @deprecated Use employee() method instead (supports both All Events and specific event views)
     */
    public function masterEmployee()
    {
        // Redirect to employee() with "All Events" context
        // This ensures a unified employee management experience
        return redirect()->route('hr.employee');
    }

    public function store(Request $request)
    {
        // Validate personal fields (stored in employees_all)
        $personalFields = $request->validate([
            // Basic Information
            'first_name'                => 'required|string|max:50',
            'middle_name'               => 'nullable|string|max:100',
            'last_name'                 => 'required|string|max:50',
            'employee_number'           => 'required|string|max:15|unique:employees_all,employee_number',
            'salutation_id'             => 'nullable|integer',
            
            // Contact Information
            'work_email_address'        => 'required|email|max:250|unique:employees_all,work_email_address',
            'personal_email_address'    => 'nullable|email|max:240',
            'phone_number'              => 'nullable|string|max:50',
            'alt_phone_number'          => 'nullable|string|max:50',
            'phone_area_code'           => 'nullable|string|max:10',
            'alt_area_code'             => 'nullable|string|max:10',
            
            // Personal Information
            'gender_id'                 => 'nullable|integer',
            'marital_status_id'         => 'nullable|integer',
            'date_of_birth'             => 'nullable|date',
            'town_of_birth'             => 'nullable|string|max:100',
            'country_of_birth'          => 'nullable|integer',
            'nationality_id'            => 'nullable|integer',
            'language_id'               => 'nullable|string|max:50',
            'date_of_hire'              => 'nullable|date',
            'join_date'                 => 'nullable|date',
            
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
            
            // Event assignment flag
            'assign_to_event'           => 'nullable|boolean',
        ]);

        // Validate event-specific fields (stored in employee_events pivot) if assigning to event
        $eventFields = [];
        if ($request->input('assign_to_event')) {
            $eventFields = $request->validate([
                'event_id'              => 'required|integer|exists:events,id',
                'agreement_number'      => 'nullable|string|max:45',
                'department_id'         => 'nullable|integer|exists:departments,id',
                'designation_id'        => 'nullable|integer|exists:designations,id',
                'directorate_id'        => 'nullable|integer',
                'functional_area_id'    => 'nullable|integer',
                'job_level_id'          => 'nullable|integer',
                'reporting_to_id'       => 'nullable|integer|exists:employees_all,id',
                'entity_id'             => 'nullable|integer',
                'contract_type_id'      => 'nullable|integer',
                'employee_type'         => 'nullable|integer',
                'salary_basis_id'       => 'nullable|integer',
                'assigned_at'           => 'required|date',
                'released_at'           => 'nullable|date',
            ]);
        }

        // Generate full name
        $personalFields['full_name'] = trim(
            ($personalFields['first_name'] ?? '') . ' ' . 
            ($personalFields['middle_name'] ?? '') . ' ' . 
            ($personalFields['last_name'] ?? '')
        );

        // Set default archived flag
        $personalFields['archived'] = 'N';
        
        // Remove non-personal fields
        unset($personalFields['assign_to_event']);

        // Create employee (personal data only)
        $employee = Employee::create($personalFields);

        // Create user account if one doesn't exist
        $this->createUserForEmployee($employee);

        // Assign to event if requested
        if ($request->input('assign_to_event') && !empty($eventFields)) {
            $employee->events()->attach($eventFields['event_id'], [
                'assigned_at'           => $eventFields['assigned_at'],
                'released_at'           => $eventFields['released_at'] ?? null,
                'is_active'             => 1,
                'agreement_number'      => $eventFields['agreement_number'] ?? null,
                'department_id'         => $eventFields['department_id'] ?? null,
                'designation_id'        => $eventFields['designation_id'] ?? null,
                'directorate_id'        => $eventFields['directorate_id'] ?? null,
                'functional_area_id'    => $eventFields['functional_area_id'] ?? null,
                'job_level_id'          => $eventFields['job_level_id'] ?? null,
                'reporting_to_id'       => $eventFields['reporting_to_id'] ?? null,
                'entity_id'             => $eventFields['entity_id'] ?? null,
                'contract_type_id'      => $eventFields['contract_type_id'] ?? null,
                'employee_type'         => $eventFields['employee_type'] ?? null,
                'salary_basis_id'       => $eventFields['salary_basis_id'] ?? null,
                'created_by'            => Auth::id() ?? 1,
                'updated_by'            => Auth::id() ?? 1,
            ]);

            // Initialize leave balances for the event
            LeaveBalanceService::initializeLeaveBalance(null, $employee, $eventFields['event_id']);
        }

        $message = $request->input('assign_to_event') 
            ? 'Employee added and assigned to event successfully.' 
            : 'Employee added successfully (not assigned to any event).';

        return redirect()->route('hr.employee')->with('success', $message);
    }

    public function edit($id)
    {
        $employee = Employee::with(['department', 'designation', 'directorate', 'functionalArea'])
            ->findOrFail($id);

        return Inertia::render('MeridianHR/EmployeeEdit', array_merge($this->getCommonProps('employee'), [
            'hrPage'   => 'employee',
            'employee' => $employee,
        ]));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);

        // Validate personal fields (stored in employees_all)
        $personalFields = $request->validate([
            // Basic Information
            'first_name'                    => 'required|string|max:50',
            'middle_name'                   => 'nullable|string|max:100',
            'last_name'                     => 'required|string|max:50',
            'salutation_id'                 => 'nullable|integer',
            'employee_number'               => ['required', 'string', 'max:15', Rule::unique('employees_all', 'employee_number')->ignore($employee->id)],
            
            // Contact Information
            'work_email_address'            => ['required', 'email', 'max:250', Rule::unique('employees_all', 'work_email_address')->ignore($employee->id)],
            'personal_email_address'        => 'nullable|email|max:240',
            'phone_area_code'               => 'nullable|string|max:10',
            'phone_number'                  => 'nullable|string|max:50',
            'alt_area_code'                 => 'nullable|string|max:10',
            'alt_phone_number'              => 'nullable|string|max:50',
            
            // Personal Information
            'gender_id'                     => 'nullable|integer',
            'marital_status_id'             => 'nullable|integer',
            'date_of_birth'                 => 'nullable|date',
            'town_of_birth'                 => 'nullable|string|max:100',
            'country_of_birth'              => 'nullable|integer',
            'nationality_id'                => 'nullable|integer',
            'language_id'                   => 'nullable|string|max:50',
            'date_of_hire'                  => 'nullable|date',
            'join_date'                     => 'nullable|date',
            
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
            
            // Event assignment flag
            'assign_to_event'               => 'nullable|boolean',
        ]);

        // Validate event-specific fields if assigning/updating event assignment
        $eventFields = [];
        if ($request->input('assign_to_event')) {
            $eventFields = $request->validate([
                'event_id'              => 'required|integer|exists:events,id',
                'agreement_number'      => 'nullable|string|max:45',
                'department_id'         => 'nullable|integer|exists:departments,id',
                'designation_id'        => 'nullable|integer|exists:designations,id',
                'directorate_id'        => 'nullable|integer',
                'functional_area_id'    => 'nullable|integer',
                'job_level_id'          => 'nullable|integer',
                'reporting_to_id'       => 'nullable|integer|exists:employees_all,id',
                'entity_id'             => 'nullable|integer',
                'contract_type_id'      => 'nullable|integer',
                'employee_type'         => 'nullable|integer',
                'salary_basis_id'       => 'nullable|integer',
                'assigned_at'           => 'required|date',
                'released_at'           => 'nullable|date',
            ]);
        }

        // Update full name
        $personalFields['full_name'] = trim(
            ($personalFields['first_name'] ?? '') . ' ' . 
            ($personalFields['middle_name'] ?? '') . ' ' . 
            ($personalFields['last_name'] ?? '')
        );

        // Remove non-personal fields
        unset($personalFields['assign_to_event']);

        // Update employee (personal data only)
        $employee->update($personalFields);

        // Handle event assignment
        if ($request->input('assign_to_event') && !empty($eventFields)) {
            $eventId = $eventFields['event_id'];
            
            // Check if assignment already exists
            $existingAssignment = $employee->events()->where('events.id', $eventId)->exists();
            
            if ($existingAssignment) {
                // Update existing assignment
                $employee->events()->updateExistingPivot($eventId, [
                    'assigned_at'           => $eventFields['assigned_at'],
                    'released_at'           => $eventFields['released_at'] ?? null,
                    'is_active'             => 1,
                    'agreement_number'      => $eventFields['agreement_number'] ?? null,
                    'department_id'         => $eventFields['department_id'] ?? null,
                    'designation_id'        => $eventFields['designation_id'] ?? null,
                    'directorate_id'        => $eventFields['directorate_id'] ?? null,
                    'functional_area_id'    => $eventFields['functional_area_id'] ?? null,
                    'job_level_id'          => $eventFields['job_level_id'] ?? null,
                    'reporting_to_id'       => $eventFields['reporting_to_id'] ?? null,
                    'entity_id'             => $eventFields['entity_id'] ?? null,
                    'contract_type_id'      => $eventFields['contract_type_id'] ?? null,
                    'employee_type'         => $eventFields['employee_type'] ?? null,
                    'salary_basis_id'       => $eventFields['salary_basis_id'] ?? null,
                    'updated_by'            => Auth::id() ?? 1,
                ]);
            } else {
                // Create new assignment
                $employee->events()->attach($eventId, [
                    'assigned_at'           => $eventFields['assigned_at'],
                    'released_at'           => $eventFields['released_at'] ?? null,
                    'is_active'             => 1,
                    'agreement_number'      => $eventFields['agreement_number'] ?? null,
                    'department_id'         => $eventFields['department_id'] ?? null,
                    'designation_id'        => $eventFields['designation_id'] ?? null,
                    'directorate_id'        => $eventFields['directorate_id'] ?? null,
                    'functional_area_id'    => $eventFields['functional_area_id'] ?? null,
                    'job_level_id'          => $eventFields['job_level_id'] ?? null,
                    'reporting_to_id'       => $eventFields['reporting_to_id'] ?? null,
                    'entity_id'             => $eventFields['entity_id'] ?? null,
                    'contract_type_id'      => $eventFields['contract_type_id'] ?? null,
                    'employee_type'         => $eventFields['employee_type'] ?? null,
                    'salary_basis_id'       => $eventFields['salary_basis_id'] ?? null,
                    'created_by'            => Auth::id() ?? 1,
                    'updated_by'            => Auth::id() ?? 1,
                ]);
            }

            // Recalculate leave balances for the event
            LeaveBalanceService::initializeLeaveBalance(null, $employee, $eventId);
        }

        return redirect()->route('hr.employee')->with('success', 'Employee updated successfully.');
    }

    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $eventId = $this->getSelectedEventId();
        
        // If viewing event-filtered employees, remove from event
        if ($eventId) {
            $event = \App\Models\Ems\Event::findOrFail($eventId);
            $event->employees()->updateExistingPivot($employee->id, [
                'is_active' => 0,
                'released_at' => now(),
                'updated_by' => Auth::id() ?? 1,
            ]);
            
            return redirect()->route('hr.employee')->with('success', 'Employee removed from event.');
        }
        
        // If viewing master employee list, archive the employee
        $employee->update(['archived' => 'Y']);
        return redirect()->route('hr.master-employee')->with('success', 'Employee archived successfully.');
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EmployeesTemplateExport(),
            'employee_import_template.xlsx'
        );
    }

    public function downloadBaseTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BaseEmployeesTemplateExport(),
            'employee_base_import_template.xlsx'
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

    public function importBase(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\BaseEmployeesImport();
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
                    ? "{$stats['success']} employee(s) imported successfully (no event assignment)" 
                    : "Import completed",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function downloadEventAssignmentTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EventAssignmentTemplateExport(),
            'event_assignment_import_template.xlsx'
        );
    }

    public function importEventAssignment(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\EventAssignmentImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $stats = $import->getStats();
            $failures = $import->failures();
            
            $errorMessages = [];
            $failedRows = [];
            
            // Add custom errors from import
            foreach ($import->getErrors() as $error) {
                $errorMessages[] = $error;
            }
            
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

            // Build success message
            $messages = [];
            if ($stats['success'] > 0) {
                $messages[] = "{$stats['success']} new assignment(s) created";
            }
            if ($stats['updated'] > 0) {
                $messages[] = "{$stats['updated']} assignment(s) updated";
            }
            $successMessage = !empty($messages) ? implode(', ', $messages) : "Import completed";

            // Return stats as JSON
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'errors' => $errorMessages,
                'hasFailures' => count($failures) > 0 || count($import->getErrors()) > 0,
                'message' => $successMessage,
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
        $me = $this->me();
        $employee = Employee::with([
            'addresses' => fn($q) => $q->where('archived', 'N')->orderBy('created_at', 'desc'),
            'emergencyContacts' => fn($q) => $q->where('archived', 'N')->orderBy('created_at', 'desc'),
            'banks' => fn($q) => $q->where('archived', 'N')->orderBy('created_at', 'desc'),
            'nationality',
            'reportingTo',
            'functionalArea',
        ])->find($me['id']);

        if (!$employee) {
            return Inertia::render('MeridianHR/Profile', array_merge($this->getCommonProps('profile'), [
                'profile' => [],
            ]));
        }

        // Get primary address
        $primaryAddress = $employee->addresses->first();
        $addressParts = array_filter([
            $primaryAddress?->address_line_1,
            $primaryAddress?->address_line_2,
            $primaryAddress?->city,
            $primaryAddress?->state_province,
            $primaryAddress?->postal_code,
        ]);
        $fullAddress = !empty($addressParts) ? implode(', ', $addressParts) : null;

        // Get primary emergency contact
        $emergencyContact = $employee->emergencyContacts->first();
        $emergencyName = $emergencyContact 
            ? trim($emergencyContact->first_name . ' ' . $emergencyContact->last_name)
            : null;
        
        // Get primary bank
        $bank = $employee->banks->first();
        $accountNumber = $bank && $bank->iban 
            ? '••••' . substr($bank->iban, -4)
            : null;

        return Inertia::render('MeridianHR/Profile', array_merge($this->getCommonProps('profile'), [
            'profile' => [
                'phone'          => $employee->phone_number,
                'location'       => $employee->functionalArea?->name,
                'dob'            => $employee->date_of_birth ? date('d F Y', strtotime($employee->date_of_birth)) : null,
                'nationality'    => $employee->nationality?->nationality,
                'address'        => $fullAddress,
                'emergencyName'  => $emergencyName,
                'emergencyPhone' => $emergencyContact?->contact_number,
                'bank'           => $bank?->bank_branch_name,
                'accountNumber'  => $accountNumber,
                'routingNumber'  => $bank && $bank->swift_code ? '••••' . substr($bank->swift_code, -4) : null,
            ],
        ]));
    }

    public function approvalsLeave()
    {
        return Inertia::render('MeridianHR/LeaveApprovals', array_merge($this->getCommonProps('approve-leave'), [
            'items'  => $this->pendingLeaves(),
        ]));
    }

    public function approveLeave(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:employee_leave_requests,id',
            'additional_information' => 'nullable|string|max:1000',
        ]);

        try {
            $ids = $request->input('ids');
            $additionalInfo = $request->input('additional_information');
            $approvedStatusId = EmployeeLeaveStatus::approvedId();
            $pendingStatusId = EmployeeLeaveStatus::pendingId();

            $updated = EmployeeLeaveRequest::forEvent() // Only update leave requests for selected event
                ->whereIn('id', $ids)
                ->where('status_id', $pendingStatusId)
                ->update([
                    'status_id' => $approvedStatusId,
                    'performer_id' => auth()->id(),
                    'additional_information' => $additionalInfo,
                    'updated_at' => now(),
                ]);

            return back()->with('success', "Successfully approved {$updated} leave request" . ($updated > 1 ? 's' : ''));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to approve leave requests', [
                'error' => $e->getMessage(),
                'ids' => $request->input('ids'),
            ]);
            return back()->with('error', 'Failed to approve leave requests.');
        }
    }

    public function rejectLeave(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:employee_leave_requests,id',
            'additional_information' => 'nullable|string|max:1000',
        ]);

        try {
            $ids = $request->input('ids');
            $additionalInfo = $request->input('additional_information');
            $rejectedStatusId = EmployeeLeaveStatus::rejectedId();
            $pendingStatusId = EmployeeLeaveStatus::pendingId();

            $updated = EmployeeLeaveRequest::forEvent() // Only update leave requests for selected event
                ->whereIn('id', $ids)
                ->where('status_id', $pendingStatusId)
                ->update([
                    'status_id' => $rejectedStatusId,
                    'performer_id' => auth()->id(),
                    'additional_information' => $additionalInfo,
                    'updated_at' => now(),
                ]);

            return back()->with('success', "Successfully rejected {$updated} leave request" . ($updated > 1 ? 's' : ''));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to reject leave requests', [
                'error' => $e->getMessage(),
                'ids' => $request->input('ids'),
            ]);
            return back()->with('error', 'Failed to reject leave requests.');
        }
    }

    public function assignToEvent(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees_all,id',
            'event_id' => 'required|exists:events,id',
            'assigned_at' => 'required|date',
            'released_at' => 'nullable|date',
            'agreement_number' => 'nullable|string|max:45',
            'department_id' => 'nullable|integer|exists:departments,id',
            'designation_id' => 'nullable|integer|exists:designations,id',
            'directorate_id' => 'nullable|integer',
            'functional_area_id' => 'nullable|integer',
            'job_level_id' => 'nullable|integer',
            'reporting_to_id' => 'nullable|integer|exists:employees_all,id',
            'entity_id' => 'nullable|integer',
            'contract_type_id' => 'nullable|integer',
            'employee_type' => 'nullable|integer',
            'salary_basis_id' => 'nullable|integer',
        ]);

        try {
            $employeeIds = $validated['employee_ids'];
            $eventId = $validated['event_id'];
            
            // Get event details for response message
            $event = \App\Models\Ems\Event::findOrFail($eventId);
            
            // Get current active assignments to avoid duplicates
            $existingAssignments = $event->employees()
                ->wherePivot('is_active', 1)
                ->whereIn('employee_id', $employeeIds)
                ->pluck('employee_id')
                ->toArray();
            
            // Filter out already assigned employees
            $employeesToAssign = array_diff($employeeIds, $existingAssignments);
            
            if (count($employeesToAssign) > 0) {
                // Prepare pivot data for bulk attach (with organizational attributes)
                $pivotData = [];
                $now = now();
                foreach ($employeesToAssign as $employeeId) {
                    $pivotData[$employeeId] = [
                        'assigned_at' => $validated['assigned_at'],
                        'released_at' => $validated['released_at'] ?? null,
                        'is_active' => 1,
                        'agreement_number' => $validated['agreement_number'] ?? null,
                        'department_id' => $validated['department_id'] ?? null,
                        'designation_id' => $validated['designation_id'] ?? null,
                        'directorate_id' => $validated['directorate_id'] ?? null,
                        'functional_area_id' => $validated['functional_area_id'] ?? null,
                        'job_level_id' => $validated['job_level_id'] ?? null,
                        'reporting_to_id' => $validated['reporting_to_id'] ?? null,
                        'entity_id' => $validated['entity_id'] ?? null,
                        'contract_type_id' => $validated['contract_type_id'] ?? null,
                        'employee_type' => $validated['employee_type'] ?? null,
                        'salary_basis_id' => $validated['salary_basis_id'] ?? null,
                        'created_by' => auth()->id() ?? 1,
                        'updated_by' => auth()->id() ?? 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                
                // Attach employees to event with pivot data
                $event->employees()->attach($pivotData);
                
                $message = count($existingAssignments) > 0
                    ? sprintf(
                        'Successfully assigned %d employee(s) to %s. %d were already assigned.',
                        count($employeesToAssign),
                        $event->name,
                        count($existingAssignments)
                    )
                    : sprintf(
                        'Successfully assigned %d employee(s) to %s',
                        count($employeesToAssign),
                        $event->name
                    );
                
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'All selected employees are already assigned to this event.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to assign employees to event', [
                'error' => $e->getMessage(),
                'employee_ids' => $request->input('employee_ids'),
                'event_id' => $request->input('event_id'),
            ]);
            
            return redirect()->back()->with('error', 'Failed to assign employees to event: ' . $e->getMessage());
        }
    }

    public function unassignFromEvent(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees_all,id',
            'event_id' => 'required|exists:events,id',
        ]);

        try {
            $employeeIds = $validated['employee_ids'];
            $eventId = $validated['event_id'];
            
            // Get event details for response message
            $event = \App\Models\Ems\Event::findOrFail($eventId);
            
            // Delete pivot records (hard removal)
            $deleted = \Illuminate\Support\Facades\DB::table('employee_events')
                ->where('event_id', $eventId)
                ->whereIn('employee_id', $employeeIds)
                ->delete();
            
            if ($deleted > 0) {
                $message = sprintf(
                    'Successfully unassigned %d employee(s) from %s',
                    $deleted,
                    $event->name
                );
                
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'No assignments found for the selected employees in this event.');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to unassign employees from event', [
                'error' => $e->getMessage(),
                'employee_ids' => $request->input('employee_ids'),
                'event_id' => $request->input('event_id'),
            ]);
            
            return redirect()->back()->with('error', 'Failed to unassign employees from event: ' . $e->getMessage());
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // Personal Data Methods
    // ══════════════════════════════════════════════════════════════════

    /**
     * Display employee addresses
     */
    public function addresses()
    {
        $me = $this->me();
        $role = $this->getHRRole();

        // Get addresses based on role
        // Employees and Managers see only their own addresses
        // Only Admins see all addresses
        if ($role === 'admin') {
            $addresses = \App\Models\EmployeeAddress::with(['employee', 'country'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Employees and managers see only their own
            $addresses = \App\Models\EmployeeAddress::with(['country'])
                ->where('employee_id', $me['id'])
                ->active()
                ->orderBy('primary_address', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Map addresses to frontend format
        $formattedAddresses = $addresses->map(function($address) use ($role) {
            return [
                'id' => $address->id,
                'employeeId' => $address->employee_id,
                'employeeName' => $address->employee->full_name ?? 'Unknown',
                'employeeNumber' => $address->employee->employee_number ?? 'N/A',
                'addressType' => $address->address_type,
                'isPrimary' => $address->primary_address === 'Y',
                'address1' => $address->address1,
                'address2' => $address->address2,
                'city' => $address->city,
                'state' => $address->state,
                'zipcode' => $address->zipcode,
                'countryId' => $address->country_id,
                'countryName' => $address->country->name ?? 'Unknown',
                'fullAddress' => $address->full_address,
                'createdAt' => $address->created_at->toISOString(),
            ];
        });

        $countries = Country::all(['id', 'name'])->map(function($country) {
            return [
                'id' => $country->id,
                'name' => $country->name,
            ];
        });

        $employees = [];
        // Only admins can see employee dropdown for managing other employees' addresses
        if ($role === 'admin') {
            $employees = Employee::select('id', 'employee_number', 'full_name')
                ->orderBy('full_name')
                ->get()
                ->toArray();
        }

        // Pass current employee info for employee and manager roles
        $currentEmployee = null;
        if (!in_array($role, ['admin'])) {
            $currentEmployee = [
                'id' => $me['id'],
                'full_name' => $me['name'],
                'employee_number' => $me['empNumber'] ?? 'N/A',
            ];
        }

        return Inertia::render('MeridianHR/Addresses', array_merge(
            $this->getCommonProps('addresses'),
            [
                'addresses' => $formattedAddresses,
                'countries' => $countries,
                'employees' => $employees,
                'currentEmployee' => $currentEmployee,
                'addressTypes' => [
                    ['id' => 1, 'name' => 'Home'],
                    ['id' => 2, 'name' => 'Work'],
                    ['id' => 3, 'name' => 'Mailing'],
                    ['id' => 4, 'name' => 'Other'],
                ],
            ]
        ));
    }

    /**
     * Store a new address
     */
    public function storeAddress(Request $request)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $validated = $request->validate([
            'employee_id' => 'required|integer|min:1|max:214748367|exists:employees_all,id',
            'address_type' => 'required|integer',
            'primary_address' => 'nullable|in:Y,N',
            'address1' => 'required|string|max:250',
            'address2' => 'nullable|string|max:250',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:10',
            'zipcode' => 'nullable|string|max:15',
            'country_id' => 'required|integer|min:1|max:214748367|exists:countries,id',
        ]);

        // Employees and managers can only add their own addresses
        // Only admins can add addresses for other employees
        if ($role !== 'admin' && $validated['employee_id'] != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only add your own addresses.'
            ]);
        }

        // Additional validation: Check if trying to create a primary address when one already exists
        if ($request->primary_address === 'Y') {
            $existingPrimary = \App\Models\EmployeeAddress::where('employee_id', $validated['employee_id'])
                ->where('primary_address', 'Y')
                ->where('archived', 'N')
                ->exists();
            
            if ($existingPrimary) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'primary_address' => 'This employee already has a primary address. Only one primary address is allowed per employee.'
                ]);
            }
        }

        $validated['creator_id'] = Auth::id();
        $validated['archived'] = 'N';

        \App\Models\EmployeeAddress::create($validated);

        return redirect()->back()->with('success', 'Address added successfully.');
    }

    /**
     * Update an existing address
     */
    public function updateAddress(Request $request, $id)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $address = \App\Models\EmployeeAddress::findOrFail($id);

        // Employees and managers can only update their own addresses
        // Only admins can update addresses for other employees
        if ($role !== 'admin' && $address->employee_id != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only update your own addresses.'
            ]);
        }

        $validated = $request->validate([
            'address_type' => 'required|integer',
            'primary_address' => 'nullable|in:Y,N',
            'address1' => 'required|string|max:250',
            'address2' => 'nullable|string|max:250',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:10',
            'zipcode' => 'nullable|string|max:15',
            'country_id' => 'required|integer|min:1|max:214748367|exists:countries,id',
        ]);

        // Additional validation: Check if trying to set as primary when one already exists
        if ($request->primary_address === 'Y') {
            $existingPrimary = \App\Models\EmployeeAddress::where('employee_id', $address->employee_id)
                ->where('primary_address', 'Y')
                ->where('archived', 'N')
                ->where('id', '!=', $id)
                ->exists();
            
            if ($existingPrimary) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'primary_address' => 'This employee already has a primary address. Only one primary address is allowed per employee.'
                ]);
            }
        }

        $address->update($validated);

        return redirect()->back()->with('success', 'Address updated successfully.');
    }

    /**
     * Archive an address
     */
    public function destroyAddress($id)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $address = \App\Models\EmployeeAddress::findOrFail($id);

        // Employees and managers can only delete their own addresses
        // Only admins can delete addresses for other employees
        if ($role !== 'admin' && $address->employee_id != $me['id']) {
            return redirect()->back()->with('error', 'You can only delete your own addresses.');
        }

        $address->update(['archived' => 'Y']);

        return redirect()->back()->with('success', 'Address archived successfully.');
    }

    // ── Bank and Salary methods moved to BankController and SalaryController ──

    /**
     * Display emergency contacts
     */
    public function emergency()
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        // Fetch emergency contacts based on role
        if (!in_array($role, ['admin'])) {
            // Employees and Managers see only their own emergency contacts
            $contacts = \App\Models\EmployeeEmergencyContact::where('employee_id', $me['id'])
                ->with(['employee', 'relationship'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Admin only: filter by event if selected
            $query = \App\Models\EmployeeEmergencyContact::with(['employee', 'relationship'])
                ->active()
                ->orderBy('created_at', 'desc');
            
            // Filter by employees assigned to the selected event
            if ($eventId) {
                $query->whereHas('employee', function ($q) use ($eventId) {
                    $q->whereHas('events', function ($eq) use ($eventId) {
                        $eq->where('events.id', $eventId)
                           ->where('employee_events.is_active', 1);
                    });
                });
            }
            
            $contacts = $query->get();
        }

        // Map contacts to frontend format
        $formattedContacts = $contacts->map(function($contact) {
            return [
                'id' => $contact->id,
                'employeeId' => $contact->employee_id,
                'employeeName' => $contact->employee->full_name ?? 'Unknown',
                'employeeNumber' => $contact->employee->employee_number ?? 'N/A',
                'firstName' => $contact->first_name,
                'lastName' => $contact->last_name,
                'fullName' => $contact->full_name,
                'relationshipId' => $contact->relationship_id,
                'relationshipName' => $contact->relationship->title ?? 'Unknown',
                'contactNumber' => $contact->contact_number,
                'createdAt' => $contact->created_at->toISOString(),
            ];
        });

        $employees = [];
        if ($role === 'admin') {
            // For admin only, show employees filtered by event
            $employeeQuery = Employee::select('id', 'employee_number', 'full_name')
                ->orderBy('full_name');
            
            if ($eventId) {
                $employeeQuery->forEvent($eventId);
            }
            
            $employees = $employeeQuery->get()->toArray();
        }

        $relationships = \App\Models\EmployeeRelationship::orderBy('title')
            ->get()
            ->map(function($rel) {
                return [
                    'id' => $rel->id,
                    'title' => $rel->title,
                ];
            });

        // Pass current employee info for employee and manager roles
        $currentEmployee = null;
        if (!in_array($role, ['admin'])) {
            $currentEmployee = [
                'id' => $me['id'],
                'full_name' => $me['name'],
                'employee_number' => $me['empNumber'] ?? 'N/A',
            ];
        }

        return Inertia::render('MeridianHR/EmergencyContact', array_merge(
            $this->getCommonProps('emergency'),
            [
                'contacts' => $formattedContacts,
                'employees' => $employees,
                'relationships' => $relationships,
                'currentEmployee' => $currentEmployee,
            ]
        ));
    }

    /**
     * Store a new emergency contact
     */
    public function storeEmergencyContact(Request $request)
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        $validated = $request->validate([
            'employee_id' => 'required|integer|min:1|max:214748367|exists:employees_all,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'relationship_id' => 'required|integer|min:1|max:214748367|exists:employee_relationships,id',
            'contact_number' => 'required|string|max:20',
        ]);

        // Employees and Managers can only add their own emergency contacts
        if (!in_array($role, ['admin']) && $validated['employee_id'] != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only add your own emergency contacts.'
            ]);
        }

        // Admin only: verify employee belongs to selected event
        if ($role === 'admin' && $eventId) {
            $employee = Employee::find($validated['employee_id']);
            $belongsToEvent = $employee->events()
                ->where('events.id', $eventId)
                ->where('employee_events.is_active', 1)
                ->exists();
            
            if (!$belongsToEvent) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employee_id' => 'This employee does not belong to the selected event.'
                ]);
            }
        }

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['archived'] = 'N';

        \App\Models\EmployeeEmergencyContact::create($validated);

        return redirect()->back()->with('success', 'Emergency contact added successfully.');
    }

    /**
     * Update an existing emergency contact
     */
    public function updateEmergencyContact(Request $request, $id)
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        $contact = \App\Models\EmployeeEmergencyContact::findOrFail($id);

        // Employees and Managers can only update their own emergency contacts
        if (!in_array($role, ['admin']) && $contact->employee_id != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only update your own emergency contacts.'
            ]);
        }

        // Admin only: verify employee belongs to selected event
        if ($role === 'admin' && $eventId) {
            $employee = $contact->employee;
            $belongsToEvent = $employee->events()
                ->where('events.id', $eventId)
                ->where('employee_events.is_active', 1)
                ->exists();
            
            if (!$belongsToEvent) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employee_id' => 'This employee does not belong to the selected event.'
                ]);
            }
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'relationship_id' => 'required|integer|min:1|max:214748367|exists:employee_relationships,id',
            'contact_number' => 'required|string|max:20',
        ]);

        $validated['updated_by'] = Auth::id();

        $contact->update($validated);

        return redirect()->back()->with('success', 'Emergency contact updated successfully.');
    }

    /**
     * Archive an emergency contact
     */
    public function destroyEmergencyContact($id)
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        $contact = \App\Models\EmployeeEmergencyContact::findOrFail($id);

        // Employees and Managers can only delete their own emergency contacts
        if (!in_array($role, ['admin']) && $contact->employee_id != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only delete your own emergency contacts.'
            ]);
        }

        // Admin only: verify employee belongs to selected event
        if ($role === 'admin' && $eventId) {
            $employee = $contact->employee;
            $belongsToEvent = $employee->events()
                ->where('events.id', $eventId)
                ->where('employee_events.is_active', 1)
                ->exists();
            
            if (!$belongsToEvent) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'employee_id' => 'This employee does not belong to the selected event.'
                ]);
            }
        }

        $contact->update([
            'archived' => 'Y',
            'updated_by' => Auth::id()
        ]);

        return redirect()->back()->with('success', 'Emergency contact archived successfully.');
    }
}

