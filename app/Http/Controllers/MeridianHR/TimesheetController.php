<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\EmployeeBank;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\EmployeeSalary;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetEntry;
use App\Models\EmployeeTimesheetStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class TimesheetController extends BaseHRController
{
    // ── Mock Data ─────────────────────────────────────────────────────
    
    protected function pendingTimesheets(): array
    {
        return [
            ['id'=>'ts-26-04-PR','emp'=>'Priya Ramaswamy','empId'=>'M-00204','c'=>2,'period'=>'April 2026','worked'=>168,'leave'=>8, 'unpaid'=>0,'projects'=>4,'submitted'=>'2026-05-01','note'=>'Logged extra hours on Atlas migration.'],
            ['id'=>'ts-26-04-MC','emp'=>'Marcus Chen',    'empId'=>'M-00387','c'=>3,'period'=>'April 2026','worked'=>152,'leave'=>16,'unpaid'=>0,'projects'=>3,'submitted'=>'2026-05-02','note'=>''],
            ['id'=>'ts-26-04-SV','emp'=>'Sofía Vargas',  'empId'=>'M-00401','c'=>4,'period'=>'April 2026','worked'=>160,'leave'=>8, 'unpaid'=>8,'projects'=>5,'submitted'=>'2026-05-02','note'=>'Took unpaid day for family matter.'],
            ['id'=>'ts-26-04-AT','emp'=>'Aiko Tanaka',   'empId'=>'M-00444','c'=>6,'period'=>'April 2026','worked'=>144,'leave'=>24,'unpaid'=>0,'projects'=>2,'submitted'=>'2026-05-01','note'=>''],
            ['id'=>'ts-26-04-TB','emp'=>'Tomás Bergström','empId'=>'M-00420','c'=>5,'period'=>'April 2026','worked'=>160,'leave'=>8, 'unpaid'=>8,'projects'=>3,'submitted'=>'2026-05-03','note'=>''],
        ];
    }

    protected function timesheets(): array
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

    protected function monthsName(): array
    {
        return [
            ['id' => 1,  'monthName' => 'January',   'monthNumber' => 1 ],
            ['id' => 2,  'monthName' => 'February',  'monthNumber' => 2 ],
            ['id' => 3,  'monthName' => 'March',      'monthNumber' => 3 ],
            ['id' => 4,  'monthName' => 'April',      'monthNumber' => 4 ],
            ['id' => 5,  'monthName' => 'May',        'monthNumber' => 5 ],
            ['id' => 6,  'monthName' => 'June',       'monthNumber' => 6 ],
            ['id' => 7,  'monthName' => 'July',       'monthNumber' => 7 ],
            ['id' => 8,  'monthName' => 'August',     'monthNumber' => 8 ],
            ['id' => 9,  'monthName' => 'September',  'monthNumber' => 9 ],
            ['id' => 10, 'monthName' => 'October',    'monthNumber' => 10],
            ['id' => 11, 'monthName' => 'November',   'monthNumber' => 11],
            ['id' => 12, 'monthName' => 'December',   'monthNumber' => 12],
        ];
    }

    protected function timesheetStatuses(): array
    {
        return EmployeeTimesheetStatus::active()
            ->orderBy('id')
            ->get(['id', 'title', 'color'])
            ->map(function ($status) {
                return [
                    'id'    => $status->id,
                    'title' => $status->title,
                    'color' => $this->getColorForStatus($status->color),
                ];
            })
            ->toArray();
    }
    
    /**
     * Map color names to hex codes
     */
    private function getColorForStatus(string $color): string
    {
        $colors = [
            'warning' => '#f59e0b',
            'info'    => '#3b82f6',
            'primary' => '#3b82f6',
            'success' => '#10b981',
            'danger'  => '#ef4444',
        ];
        return $colors[$color] ?? '#6b7280';
    }

    protected function generateMonthSkeleton(string $monthKey): array
    {
        [$y, $m] = array_map('intval', explode('-', $monthKey));
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $y);
        $label = date('F Y', mktime(0, 0, 0, $m, 1, $y));
        $days  = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dow  = (int) date('N', mktime(0, 0, 0, $m, $d, $y)); // 1=Mon … 7=Sun
            $days[] = ['d' => $d, 'type' => $dow >= 6 ? '0' : ' ', 'hours' => 0];
        }

        return ['label' => $label, 'submitted' => false, 'days' => $days];
    }

    // ── Employee Timesheet (Self-Service) ────────────────────────────
    
    public function timesheet()
    {
        return Inertia::render('MeridianHR/Timesheet', array_merge($this->getCommonProps('timesheet'), [
            'timesheets' => $this->timesheets(),
        ]));
    }

    /**
     * Calculate and update timesheet payment fields based on entries
     */
    protected function calculateTimesheetPayment(EmployeeTimesheet $timesheet): void
    {
        $tsId = $timesheet->id;
        $employeeId = $timesheet->employee_id;
        
        // Get timesheet period dates
        $timesheetPeriodStart = \Carbon\Carbon::create($timesheet->year, $timesheet->month_id, 1)->startOfMonth();
        $timesheetPeriodEnd = $timesheetPeriodStart->copy()->endOfMonth();

        // Get salary for this period
        $salary = EmployeeSalary::where('employee_id', $employeeId)
            ->where('archived', 'N')
            ->where('active_flag', 1)
            ->where('effective_start_date', '<=', $timesheetPeriodEnd)
            ->where(function ($query) use ($timesheetPeriodStart) {
                $query->where('effective_end_date', '>=', $timesheetPeriodStart)
                    ->orWhere('effective_end_date', '9999-12-31')
                    ->orWhereNull('effective_end_date');
            })
            ->orderBy('effective_start_date', 'DESC')
            ->first();

        // Get bank account for this period
        $bank = EmployeeBank::where('employee_id', $employeeId)
            ->where('archived', 'N')
            ->where('effective_start_date', '<=', $timesheetPeriodEnd)
            ->where(function ($query) use ($timesheetPeriodStart) {
                $query->where('effective_end_date', '>=', $timesheetPeriodStart)
                    ->orWhere('effective_end_date', '9999-12-31')
                    ->orWhereNull('effective_end_date');
            })
            ->orderBy('effective_start_date', 'DESC')
            ->first();

        // Get first and last days from entries
        $firstDay = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)
            ->orderBy('calendar_day', 'ASC')
            ->first();
        $lastDay = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)
            ->orderBy('calendar_day', 'DESC')
            ->first();

        if (!$firstDay || !$lastDay) {
            // No entries found - set default values
            $startDay = 1;
            $endDay = cal_days_in_month(CAL_GREGORIAN, $timesheet->month_id, $timesheet->year);
        } else {
            $startDay = $firstDay->calendar_day;
            $endDay = $lastDay->calendar_day;
        }

        // Count actions
        $countWorked = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'W')->count();
        $countLeaves = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'L')->count();
        $countUnpaid = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'U')->count();

        // Calculate payment
        $monthlySalary = $salary?->net_salary ?? 0;
        $dailyRate = $monthlySalary / 30;
        
        // Determine if this is a full month (contract goes to month-end)
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $timesheet->month_id, $timesheet->year);
        $isFullMonth = ($startDay <= 5 && $endDay >= $daysInMonth - 2); // Covers substantially full month
        
        if ($isFullMonth) {
            // Full month: use 30-day model (or actual days in February)
            // For February, use actual days (28/29) to avoid confusion
            $baseDays = ($timesheet->month_id == 2) ? $daysInMonth : 30;
            
            // Worked days = base - leave - unpaid
            $daysWorkedDisplay = $baseDays - $countLeaves - $countUnpaid;
            // Payment = full salary minus unpaid days only (leave is paid)
            $totalPayment = $monthlySalary - ($countUnpaid * $dailyRate);
            $paidDays = 30 - $countUnpaid; // Payment still based on 30-day standard
        } else {
            // Partial month: prorate based on actual calendar days in contract period
            $workedDays = $endDay - $startDay + 1; // Actual calendar days in range
            $daysWorkedDisplay = $workedDays - $countLeaves - $countUnpaid;
            $paidDays = max(0, $workedDays - $countUnpaid);
            $totalPayment = $dailyRate * $paidDays;
        }

        // Update timesheet with calculated values (but not status)
        $timesheet->update([
            'days_worked' => $daysWorkedDisplay, // Actual worked days (excluding leave and unpaid)
            'leave_taken' => $countLeaves,
            'unpaid_leave_taken' => $countUnpaid,
            'total_days_eligible_for_payment' => $paidDays,
            'salary' => $monthlySalary,
            'daily_rate' => $dailyRate,
            'total_payment' => $totalPayment,
            'bank_id' => $bank?->id,
            'updated_at' => now(),
        ]);
    }

    public function submitTimesheet(Request $request)
    {
        $request->validate([
            'timesheet_id' => 'required|integer|exists:employee_timesheets,id',
        ]);
        
        $timesheet = EmployeeTimesheet::findOrFail($request->timesheet_id);
        
        // Only allow submission if status is Pending
        if ($timesheet->status_id !== EmployeeTimesheetStatus::pendingId()) {
            return back()->withErrors(['error' => 'Only pending timesheets can be submitted.']);
        }
        
        // Recalculate payment before submission
        DB::transaction(function () use ($timesheet) {
            // Use the shared calculation method
            $this->calculateTimesheetPayment($timesheet);
            
            // Update status to Submitted
            $timesheet->update([
                'status_id' => EmployeeTimesheetStatus::submittedId(),
                'updated_at' => now(),
            ]);
        });
        
        return back()->with('success', 'Timesheet submitted successfully.');
    }

    /**
     * Calculate payment for a timesheet without changing status
     */
    public function calculatePayment(Request $request)
    {
        $request->validate([
            'timesheet_id' => 'required|integer|exists:employee_timesheets,id',
        ]);
        
        $timesheet = EmployeeTimesheet::findOrFail($request->timesheet_id);
        
        // Calculate payment
        $this->calculateTimesheetPayment($timesheet);
        
        // Refresh the model to get updated values
        $timesheet->refresh();
        
        // Return updated values to frontend
        return back()->with([
            'updatedTimesheet' => [
                'id' => $timesheet->id,
                'daysWorked' => $timesheet->days_worked ?? 0,
                'leaveTaken' => $timesheet->leave_taken ?? 0,
                'unpaidLeave' => $timesheet->unpaid_leave_taken ?? 0,
                'totalDays' => $this->calculateBaseDaysFromTimesheet($timesheet),
                'dailyRate' => $timesheet->daily_rate ? number_format($timesheet->daily_rate, 2) : '0.00',
                'salary' => $timesheet->salary ? number_format($timesheet->salary, 2) : '0.00',
                'payment' => $timesheet->total_payment ? number_format($timesheet->total_payment, 2) : '0.00',
            ],
        ]);
    }

    public function saveTimesheetDay(Request $request)
    {
        $request->validate([
            'month' => 'required|string',
            'day'   => 'required|integer|min:1|max:31',
            'type'  => 'required|in:W,L,U,0',
            'hours' => 'required|numeric|min:0|max:24',
        ]);
        // In production: upsert timesheet day record
        return back()->with('success', 'Day saved.');
    }

    public function startTimesheet(Request $request)
    {
        $request->validate(['month' => 'required|string|regex:/^\d{4}-\d{2}$/']);
        // In production: create timesheet record and generate day rows
        return redirect()->route('hr.timesheet', ['role' => $request->query('role', 'employee')])
            ->with('success', 'Timesheet started.');
    }

    // ── My Timesheets (Employee viewing their own) ────────────────────

    public function myTimesheets()
    {
        // Get current employee
        $employee = Employee::where('user_id', auth()->id())->first();
        
        if (!$employee) {
            return Inertia::render('MeridianHR/TimesheetTalent', array_merge($this->getCommonProps('my-timesheets'), [
                'timesheets' => [],
                'employees' => [],
                'statuses' => $this->timesheetStatuses(),
            ]));
        }

        $eventId  = $this->getSelectedEventId();
        $statuses = $this->timesheetStatuses();
        $statusMap = collect($statuses)->keyBy('id');

        // Only show current employee in the dropdown (read-only context)
        $employees = collect([[
            'id' => $employee->id,
            'full_name' => $employee->full_name,
            'employee_number' => $employee->employee_number
        ]]);

        // Timesheets with entries - filtered by current employee
        $tsQuery = EmployeeTimesheet::active()
            ->with([
                'employee' => function ($query) use ($eventId) {
                    if ($eventId) {
                        $query->with(['events' => function ($q) use ($eventId) {
                            $q->where('events.id', $eventId);
                        }]);
                    }
                },
                'event',
                'entries',
                'performer:id,full_name'
            ])
            ->where('employee_id', $employee->id)  // Filter by current employee
            ->forEvent($eventId)
            ->orderByDesc('year')
            ->orderByDesc('month_id');

        $DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        // Approved leave dates keyed by employee_id → [Y-m-d => true]
        $approvedStatusId = EmployeeLeaveStatus::approvedId();
        $leaveDays = [];
        if ($approvedStatusId) {
            EmployeeLeaveRequest::active()
                ->where('status_id', $approvedStatusId)
                ->where('employee_id', $employee->id)  // Only for current employee
                ->forEvent($eventId)
                ->get(['employee_id', 'date_from', 'date_to'])
                ->each(function ($lr) use (&$leaveDays) {
                    $cur = $lr->date_from->copy();
                    while ($cur->lte($lr->date_to)) {
                        $leaveDays[$lr->employee_id][$cur->format('Y-m-d')] = true;
                        $cur->addDay();
                    }
                });
        }

        $timesheets = $tsQuery->get()->map(function ($ts) use ($statusMap, $DAYS, $monthNames, $leaveDays, $eventId, $employee) {
            $emp         = $ts->employee;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $ts->month_id, $ts->year);
            $startDay    = 1;
            $endDay      = $daysInMonth;

            // Narrow to contract / event assignment window if applicable
            if ($eventId && $emp) {
                $eventPivot = null;
                
                if ($emp->relationLoaded('events') && $emp->events->isNotEmpty()) {
                    $eventPivot = $emp->events->first()->pivot;
                } else {
                    $eventRelation = $emp->events()->where('events.id', $eventId)->first();
                    $eventPivot = $eventRelation?->pivot;
                }
                
                if ($eventPivot) {
                    if ($eventPivot->assigned_at) {
                        $a = \Carbon\Carbon::parse($eventPivot->assigned_at);
                        if ($a->year == $ts->year && $a->month == $ts->month_id) {
                            $startDay = max($startDay, $a->day);
                        } elseif ($a->year > $ts->year || ($a->year == $ts->year && $a->month > $ts->month_id)) {
                            $startDay = $daysInMonth + 1;
                        }
                    }
                    if ($eventPivot->released_at) {
                        $r = \Carbon\Carbon::parse($eventPivot->released_at);
                        if ($r->year == $ts->year && $r->month == $ts->month_id) {
                            $endDay = min($endDay, $r->day);
                        } elseif ($r->year < $ts->year || ($r->year == $ts->year && $r->month < $ts->month_id)) {
                            $endDay = 0;
                        }
                    }
                }
            }

            $empLeave = $leaveDays[$ts->employee_id] ?? [];
            
            // Build entries for ALL days in the valid range, not just saved ones
            $savedEntries = $ts->entries->keyBy('calendar_day');
            $entries = [];
            
            for ($d = $startDay; $d <= $endDay; $d++) {
                $date = new \DateTime("{$ts->year}-{$ts->month_id}-{$d}");
                $dow = (int) $date->format('w'); // 0=Sun, 5=Fri, 6=Sat
                $isWeekend = $dow === 5 || $dow === 6; // Friday or Saturday
                $isLeave = isset($empLeave[$date->format('Y-m-d')]);
                
                // If there's a saved entry for this day, use its action; otherwise default
                $savedEntry = $savedEntries[$d] ?? null;
                $action = $savedEntry ? $savedEntry->day_action : ($isWeekend ? '0' : 'W');
                
                $entries[] = [
                    'day'       => $d,
                    'dayName'   => $DAYS[$dow],
                    'isWeekend' => $isWeekend,
                    'action'    => $action,
                    'isLeave'   => $isLeave,
                ];
            }

            $period     = $monthNames[$ts->month_id] . '-' . $ts->year;
            $statusId   = $ts->status_id;
            $status     = $statusMap->get($statusId, ['title' => 'Unknown', 'color' => '#999']);
            $statusTitle = $status['title'];

            return [
                'id'            => $ts->id,
                'employeeId'    => $ts->employee_id,
                'employeeName'  => $emp?->full_name ?? 'Unknown',
                'employeeNumber' => $emp?->employee_number,
                'employeeColor' => $emp?->avatar_color ?? 0,
                'eventId'       => $ts->event_id,
                'eventName'     => $ts->event?->name,
                'period'        => $period,
                'monthNumber'   => $ts->month_id,
                'year'          => $ts->year,
                'daysInMonth'   => $daysInMonth,
                'startDay'      => $startDay,
                'endDay'        => $endDay,
                'statusId'      => $statusId,
                'statusTitle'   => $statusTitle,
                'hasEntries'    => count($entries) > 0,
                'entries'       => $entries,
                // Payment calculation fields
                'daysWorked'    => $ts->days_worked ?? 0,
                'leaveTaken'    => $ts->leave_taken ?? 0,
                'unpaidLeave'   => $ts->unpaid_leave_taken ?? 0,
                'totalDays'     => $this->calculateBaseDays($startDay, $endDay, $daysInMonth, $ts->month_id),
                'dailyRate'     => $ts->daily_rate ? number_format($ts->daily_rate, 2) : '0.00',
                'salary'        => $ts->salary ? number_format($ts->salary, 2) : '0.00',
                'payment'       => $ts->total_payment ? number_format($ts->total_payment, 2) : '0.00',
                'approver'      => $ts->performer?->full_name ?? null,
            ];
        });

        // Calculate timesheet submission cutoff
        $cutoffDay = config('settings.talent_timesheet_submission_closed_after_day', 21);
        $disableSubmission = false;
        $formattedCutoff = null;
        
        if ($cutoffDay != 0) {
            $today = \Carbon\Carbon::today();
            $cutoff = \Carbon\Carbon::createFromDate(null, null, $cutoffDay);
            $formattedCutoff = $cutoff->format('jS');
            $disableSubmission = $today->greaterThanOrEqualTo($cutoff);
        }

        return Inertia::render('MeridianHR/TimesheetTalent', array_merge($this->getCommonProps('my-timesheets'), [
            'timesheets' => $timesheets,
            'employees'  => $employees,
            'monthsName' => $this->monthsName(),
            'years'      => [2024, 2025, 2026],
            'statuses'   => $statuses,
            'currentEmployee' => [
                'id' => $employee->id,
                'full_name' => $employee->full_name,
                'employee_number' => $employee->employee_number,
            ],
            'cutoffDay' => $cutoffDay,
            'disableSubmission' => $disableSubmission,
            'formattedCutoff' => $formattedCutoff,
        ]));
    }

    // ── TimesheetTalent (Admin/Manager) ───────────────────────────────

    public function timesheetTalent()
    {
        $eventId  = $this->getEffectiveEventIds(); // Support manager "All My Events"
        $hrRole = $this->getHRRole();
        $scope = request()->query('scope'); // 'team' for team view, null for personal view
        $statuses = $this->timesheetStatuses();
        $statusMap = collect($statuses)->keyBy('id');

        // Determine if we should show only personal timesheets
        $showPersonalOnly = false;
        $currentEmployee = null;
        
        if (!in_array($hrRole, ['admin'])) {
            // For employees, always show only their own timesheets
            if (!in_array($hrRole, ['manager'])) {
                $showPersonalOnly = true;
            }
            // For managers, show personal timesheets unless scope=team
            elseif ($scope !== 'team') {
                $showPersonalOnly = true;
            }
        }
        
        // Get current employee record if needed
        if ($showPersonalOnly) {
            $currentEmployee = Employee::where('user_id', auth()->id())->first();
        }

        // Employees list
        // For personal view (managers/employees), only show current employee
        if ($showPersonalOnly) {
            if ($currentEmployee) {
                $employees = collect([[
                    'id' => $currentEmployee->id,
                    'full_name' => $currentEmployee->full_name,
                    'employee_number' => $currentEmployee->employee_number,
                ]]);
            } else {
                $employees = collect([]);
            }
        } else {
            $empQuery = Employee::active()->orderBy('full_name');
            
            if ($eventId) {
                if (is_array($eventId)) {
                    $empQuery->whereHas('events', function ($q) use ($eventId) {
                        $q->whereIn('events.id', $eventId)->where('employee_events.is_active', 1);
                    });
                } else {
                    $empQuery->whereHas('events', function ($q) use ($eventId) {
                        $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
                    });
                }
            }
            
            $employees = $empQuery->get()->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'employee_number' => $e->employee_number
            ]);
        }

        // Timesheets with entries
        $tsQuery = EmployeeTimesheet::active()
            ->with([
                'employee' => function ($query) use ($eventId) {
                    if ($eventId) {
                        if (is_array($eventId)) {
                            $query->with(['events' => function ($q) use ($eventId) {
                                $q->whereIn('events.id', $eventId);
                            }]);
                        } else {
                            $query->with(['events' => function ($q) use ($eventId) {
                                $q->where('events.id', $eventId);
                            }]);
                        }
                    }
                },
                'event',
                'entries',
                'performer:id,full_name'
            ])
            ->forEvent($eventId)
            ->when($showPersonalOnly, function ($query) use ($currentEmployee) {
                if ($currentEmployee) {
                    return $query->where('employee_id', $currentEmployee->id);
                } else {
                    // No employee record found - show empty results
                    return $query->whereRaw('1 = 0');
                }
            })
            ->orderByDesc('year')
            ->orderByDesc('month_id');

        $DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        // Approved leave dates keyed by employee_id → [Y-m-d => true]
        $approvedStatusId = EmployeeLeaveStatus::approvedId();
        $leaveDays = [];
        if ($approvedStatusId) {
            EmployeeLeaveRequest::active()
                ->where('status_id', $approvedStatusId)
                ->forEvent($eventId)
                ->when($showPersonalOnly, function ($query) use ($currentEmployee) {
                    if ($currentEmployee) {
                        return $query->where('employee_id', $currentEmployee->id);
                    } else {
                        // No employee record found - show empty results
                        return $query->whereRaw('1 = 0');
                    }
                })
                ->get(['employee_id', 'date_from', 'date_to'])
                ->each(function ($lr) use (&$leaveDays) {
                    $cur = $lr->date_from->copy();
                    while ($cur->lte($lr->date_to)) {
                        $leaveDays[$lr->employee_id][$cur->format('Y-m-d')] = true;
                        $cur->addDay();
                    }
                });
        }

        $timesheets = $tsQuery->get()->map(function ($ts) use ($statusMap, $DAYS, $monthNames, $leaveDays, $eventId) {
            $emp         = $ts->employee;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $ts->month_id, $ts->year);
            $startDay    = 1;
            $endDay      = $daysInMonth;

            // Narrow to contract / event assignment window if applicable
            if ($eventId && $emp) {
                // Event context: use event assignment dates (assigned_at/released_at)
                // Use eagerly loaded events instead of fresh query
                $eventPivot = null;
                
                // Try to get from eagerly loaded relationship first
                if ($emp->relationLoaded('events') && $emp->events->isNotEmpty()) {
                    $eventPivot = $emp->events->first()->pivot;
                } else {
                    // Fallback to fresh query if relationship not loaded
                    $eventRelation = $emp->events()->where('events.id', $eventId)->first();
                    $eventPivot = $eventRelation?->pivot;
                }
                
                if ($eventPivot) {
                    if ($eventPivot->assigned_at) {
                        $a = \Carbon\Carbon::parse($eventPivot->assigned_at);
                        if ($a->year == $ts->year && $a->month == $ts->month_id) {
                            $startDay = max($startDay, $a->day);
                        }
                    }
                    if ($eventPivot->released_at) {
                        $r = \Carbon\Carbon::parse($eventPivot->released_at);
                        if ($r->year == $ts->year && $r->month == $ts->month_id) {
                            $endDay = min($endDay, $r->day);
                        }
                    }
                }
            } elseif ($emp) {
                // No event context: use employee contract dates (contract_start_date/contract_end_date)
                if ($emp->contract_start_date) {
                    $contractStart = \Carbon\Carbon::parse($emp->contract_start_date);
                    if ($contractStart->year == $ts->year && $contractStart->month == $ts->month_id) {
                        $startDay = max($startDay, $contractStart->day);
                    }
                }
                if ($emp->contract_end_date) {
                    $contractEnd = \Carbon\Carbon::parse($emp->contract_end_date);
                    if ($contractEnd->year == $ts->year && $contractEnd->month == $ts->month_id) {
                        $endDay = min($endDay, $contractEnd->day);
                    }
                }
            }

            $empLeave = $leaveDays[$ts->employee_id] ?? [];
            
            // Build entries for ALL days in the valid range, not just saved ones
            $savedEntries = $ts->entries->keyBy('calendar_day');
            $entries = [];
            
            for ($d = $startDay; $d <= $endDay; $d++) {
                $date = new \DateTime("{$ts->year}-{$ts->month_id}-{$d}");
                $dow = (int) $date->format('w'); // 0=Sun, 5=Fri, 6=Sat
                $isWeekend = $dow === 5 || $dow === 6; // Friday or Saturday
                $isLeave = isset($empLeave[$date->format('Y-m-d')]);
                
                // If there's a saved entry for this day, use its action; otherwise default
                $savedEntry = $savedEntries[$d] ?? null;
                $action = $savedEntry ? $savedEntry->day_action : ($isWeekend ? '0' : 'W');
                
                $entries[] = [
                    'day'       => $d,
                    'dayName'   => $DAYS[$dow],
                    'isWeekend' => $isWeekend,
                    'action'    => $action,
                    'isLeave'   => $isLeave,
                ];
            }

            $period     = $monthNames[$ts->month_id] . '-' . $ts->year;
            $statusId   = $ts->status_id;
            $statusTitle = $statusMap[$statusId]['title'] ?? 'Pending';

            return [
                'id'            => $ts->id,
                'employeeId'    => $ts->employee_id,
                'employeeName'  => $emp?->full_name ?? 'Unknown',
                'employeeNumber' => $emp?->employee_number,
                'employeeColor' => $emp?->avatar_color ?? 0,
                'eventId'       => $ts->event_id,
                'eventName'     => $ts->event?->name,
                'period'        => $period,
                'monthNumber'   => $ts->month_id,
                'year'          => $ts->year,
                'daysInMonth'   => $daysInMonth,
                'startDay'      => $startDay,
                'endDay'        => $endDay,
                'statusId'      => $statusId,
                'statusTitle'   => $statusTitle,
                'hasEntries'    => count($entries) > 0,
                'entries'       => $entries,
                // Payment calculation fields
                'daysWorked'    => $ts->days_worked ?? 0,
                'leaveTaken'    => $ts->leave_taken ?? 0,
                'unpaidLeave'   => $ts->unpaid_leave_taken ?? 0,
                'totalDays'     => $this->calculateBaseDays($startDay, $endDay, $daysInMonth, $ts->month_id),
                'dailyRate'     => $ts->daily_rate ? number_format($ts->daily_rate, 2) : '0.00',
                'salary'        => $ts->salary ? number_format($ts->salary, 2) : '0.00',
                'payment'       => $ts->total_payment ? number_format($ts->total_payment, 2) : '0.00',
                'approver'      => $ts->performer?->full_name ?? null,
            ];
        });

        // Calculate timesheet submission cutoff
        $cutoffDay = config('settings.timesheet_cutoff_day');
        $disableSubmission = false;
        $formattedCutoff = null;
        
        if ($cutoffDay != 0) {
            $today = \Carbon\Carbon::today();
            $cutoff = \Carbon\Carbon::createFromDate(null, null, $cutoffDay);
            $formattedCutoff = $cutoff->format('jS');
            $disableSubmission = $today->greaterThanOrEqualTo($cutoff);
        }

        return Inertia::render('MeridianHR/TimesheetTalent', array_merge($this->getCommonProps('timesheet-talent'), [
            'employees'  => $employees,
            'monthsName' => $this->monthsName(),
            'years'      => [2024, 2025, 2026],
            'statuses'   => $statuses,
            'timesheets' => $timesheets,
            'cutoffDay' => $cutoffDay,
            'disableSubmission' => $disableSubmission,
            'formattedCutoff' => $formattedCutoff,
            'isTeamView' => $scope === 'team', // Indicates read-only team view for managers
        ]));
    }

    /**
     * My Timesheets View - Personal view for current user
     * Accessible by: everyone (employee, manager, admin)
     */
    public function myTimesheetsView()
    {
        $data = $this->getTimesheetData(personalOnly: true);
        return Inertia::render('MeridianHR/MyTimesheetsView', array_merge($this->getCommonProps('my-timesheets-view'), $data));
    }

    /**
     * Team Timesheets View - Manager read-only view of team timesheets
     * Accessible by: manager, admin
     */
    public function teamTimesheetsView()
    {
        $data = $this->getTimesheetData(personalOnly: false);
        return Inertia::render('MeridianHR/TeamTimesheetsView', array_merge($this->getCommonProps('team-timesheets'), $data));
    }

    /**
     * All Timesheets View - Admin full-control view of all timesheets
     * Accessible by: admin
     */
    public function allTimesheetsView()
    {
        $data = $this->getTimesheetData(personalOnly: false);
        return Inertia::render('MeridianHR/AllTimesheetsView', array_merge($this->getCommonProps('all-timesheets'), $data));
    }

    /**
     * Shared helper to get timesheet data
     */
    private function getTimesheetData($personalOnly = false)
    {
        $eventId  = $personalOnly ? $this->getSelectedEventId() : $this->getEffectiveEventIds();
        $statuses = $this->timesheetStatuses();
        $statusMap = collect($statuses)->keyBy('id');
        $currentEmployee = null;
        
        // If empty array (manager with no events), return empty data for team views
        if (!$personalOnly && is_array($eventId) && empty($eventId)) {
            return [
                'employees'  => [],
                'monthsName' => $this->monthsName(),
                'years'      => [2024, 2025, 2026],
                'statuses'   => $statuses,
                'timesheets' => [],
                'leaveDays'  => [],
                'cutoffDay'  => config('settings.talent_timesheet_submission_closed_after_day', 21),
                'disableSubmission' => false,
                'formattedCutoff' => null,
            ];
        }
        
        if ($personalOnly) {
            $currentEmployee = Employee::where('user_id', auth()->id())->first();
            if (!$currentEmployee) {
                return [
                    'employees'  => [],
                    'monthsName' => $this->monthsName(),
                    'years'      => [2024, 2025, 2026],
                    'statuses'   => $statuses,
                    'timesheets' => [],
                    'cutoffDay'  => config('settings.talent_timesheet_submission_closed_after_day', 21),
                    'disableSubmission' => false,
                    'formattedCutoff' => null,
                ];
            }
        }

        // Get employees list
        if ($personalOnly && $currentEmployee) {
            $employees = collect([[
                'id' => $currentEmployee->id,
                'full_name' => $currentEmployee->full_name,
                'employee_number' => $currentEmployee->employee_number,
            ]]);
        } else {
            $empQuery = Employee::active()->orderBy('full_name');
            if ($eventId) {
                if (is_array($eventId)) {
                    $empQuery->whereHas('events', function ($q) use ($eventId) {
                        $q->whereIn('events.id', $eventId)->where('employee_events.is_active', 1);
                    });
                } else {
                    $empQuery->whereHas('events', function ($q) use ($eventId) {
                        $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
                    });
                }
            }
            $employees = $empQuery->get()->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'employee_number' => $e->employee_number
            ]);
        }

        // Get timesheets with entries
        $tsQuery = EmployeeTimesheet::with(['employee', 'event', 'entries'])
            ->orderBy('year', 'desc')
            ->orderBy('month_id', 'desc');
        
        if ($eventId) {
            if (is_array($eventId)) {
                $tsQuery->whereIn('event_id', $eventId);
            } else {
                $tsQuery->where('event_id', $eventId);
            }
        }
        
        if ($personalOnly && $currentEmployee) {
            $tsQuery->where('employee_id', $currentEmployee->id);
        }
        
        $tsList = $tsQuery->get();

        // Get approved leaves for the employees
        $employeeIds = $personalOnly && $currentEmployee ? [$currentEmployee->id] : $employees->pluck('id')->toArray();
        $approvedLeaves = [];
        if (!empty($employeeIds)) {
            $approvedStatusId = EmployeeLeaveStatus::where('title', 'Approved')->value('id');
            $leaves = EmployeeLeaveRequest::whereIn('employee_id', $employeeIds)
                ->where('status_id', $approvedStatusId)
                ->where('archived', 'N')
                ->get();
            
            foreach ($leaves as $leave) {
                $start = \Carbon\Carbon::parse($leave->date_from);
                $end = \Carbon\Carbon::parse($leave->date_to);
                for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                    $key = $leave->employee_id . '_' . $date->format('Y-m-d');
                    $approvedLeaves[$key] = true;
                }
            }
        }

        // Build timesheet array
        $timesheets = $tsList->map(function ($ts) use ($statusMap, $approvedLeaves) {
            // ... (condensed for space - full mapping logic remains the same as original)
            $year = $ts->year;
            $month = $ts->month_id;
            $period = date('M-Y', mktime(0, 0, 0, $month, 1, $year));
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
            
            $entries = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $dayOfWeek = date('N', strtotime($date));
                $isWeekend = in_array($dayOfWeek, [6, 7]);
                $dayName = date('D', strtotime($date));
                $key = $ts->employee_id . '_' . $date;
                $isLeave = isset($approvedLeaves[$key]);
                
                $entry = $ts->entries->firstWhere('calendar_day', $day);
                $action = $entry ? $entry->day_action : ($isLeave ? 'L' : ($isWeekend ? '0' : 'W'));
                
                $entries[] = [
                    'day' => $day,
                    'dayName' => $dayName,
                    'isWeekend' => $isWeekend,
                    'action' => $action,
                    'isLeave' => $isLeave
                ];
            }
            
            return [
                'id' => $ts->id,
                'employeeId' => $ts->employee_id,
                'employeeName' => $ts->employee?->full_name,
                'employeeColor' => $ts->employee?->avatar_color ?? 0,
                'period' => $period,
                'monthNumber' => $month,
                'year' => $year,
                'daysInMonth' => $daysInMonth,
                'startDay' => 1,
                'endDay' => $daysInMonth,
                'statusId' => $ts->status_id,
                'statusTitle' => $statusMap[$ts->status_id]['title'] ?? 'Unknown',
                'hasEntries' => $ts->entries->count() > 0,
                'entries' => $entries,
                'daysWorked' => $ts->days_worked ?? 0,
                'leaveTaken' => $ts->leave_taken ?? 0,
                'unpaidLeave' => $ts->unpaid_leave_taken ?? 0,
                'totalDays' => $this->calculateBaseDays(1, $daysInMonth, $daysInMonth, $month),
                'dailyRate' => $ts->daily_rate ? number_format($ts->daily_rate, 2) : '0.00',
                'salary' => $ts->salary ? number_format($ts->salary, 2) : '0.00',
                'payment' => $ts->total_payment ? number_format($ts->total_payment, 2) : '0.00',
            ];
        });

        // Cutoff day logic
        $cutoffDay = config('settings.timesheet_cutoff_day', 21);
        $disableSubmission = false;
        $formattedCutoff = null;
        
        if ($cutoffDay != 0) {
            $today = \Carbon\Carbon::today();
            $cutoff = \Carbon\Carbon::createFromDate(null, null, $cutoffDay);
            $formattedCutoff = $cutoff->format('jS');
            $disableSubmission = $today->greaterThanOrEqualTo($cutoff);
        }

        return [
            'employees'  => $employees,
            'currentEmployee' => $currentEmployee ? [
                'id' => $currentEmployee->id,
                'full_name' => $currentEmployee->full_name,
                'employee_number' => $currentEmployee->employee_number,
            ] : null,
            'monthsName' => $this->monthsName(),
            'years'      => [2024, 2025, 2026],
            'statuses'   => $statuses,
            'timesheets' => $timesheets,
            'cutoffDay' => $cutoffDay,
            'disableSubmission' => $disableSubmission,
            'formattedCutoff' => $formattedCutoff,
        ];
    }

    public function timesheetTalentStore(Request $request)
    {
        $request->validate([
            'month_selected_id' => 'required|integer|min:1|max:12',
            'year_selected'     => 'required|integer|min:2000|max:2100',
            'employee_id'       => 'required|integer|exists:employees_all,id',
        ]);

        $eventId    = $this->getSelectedEventId();
        $employeeId = $request->employee_id;

        DB::beginTransaction();
        try {
            $employee = Employee::findOrFail($employeeId);
            
            // Get the month name for display
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
            $monthName = $monthNames[$request->month_selected_id];
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $request->month_selected_id, $request->year_selected);
            
            // Create Carbon instances for validation
            $timesheetPeriodStart = \Carbon\Carbon::create($request->year_selected, $request->month_selected_id, 1)->startOfMonth();
            $timesheetPeriodEnd = $timesheetPeriodStart->copy()->endOfMonth();
            
            // Validation 1: Check contract date range
            if ($eventId && $employee) {
                // In event context: use pivot assigned_at/released_at
                $pivot = $employee->events()->where('events.id', $eventId)->first()?->pivot;
                
                if ($pivot) {
                    $contractStartDate = $pivot->assigned_at ? \Carbon\Carbon::parse($pivot->assigned_at) : null;
                    $contractEndDate = $pivot->released_at ? \Carbon\Carbon::parse($pivot->released_at) : null;
                    
                    if ($contractStartDate && $timesheetPeriodEnd->lessThan($contractStartDate->startOfMonth())) {
                        DB::rollBack();
                        return back()->withErrors([
                            'month_selected_id' => 'Cannot create timesheet before employee assignment date (' . $contractStartDate->format('F Y') . ').'
                        ]);
                    }
                    
                    if ($contractEndDate && $timesheetPeriodStart->greaterThan($contractEndDate->endOfMonth())) {
                        DB::rollBack();
                        return back()->withErrors([
                            'month_selected_id' => 'Cannot create timesheet after employee release date (' . $contractEndDate->format('F Y') . ').'
                        ]);
                    }
                }
            } else {
                // In master employee view: use contract_start_date/contract_end_date
                if ($employee->contract_start_date) {
                    $contractStartDate = \Carbon\Carbon::parse($employee->contract_start_date);
                    if ($timesheetPeriodEnd->lessThan($contractStartDate->startOfMonth())) {
                        DB::rollBack();
                        return back()->withErrors([
                            'month_selected_id' => 'Cannot create timesheet before employee contract start date (' . $contractStartDate->format('F Y') . ').'
                        ]);
                    }
                }
                
                if ($employee->contract_end_date) {
                    $contractEndDate = \Carbon\Carbon::parse($employee->contract_end_date);
                    if ($timesheetPeriodStart->greaterThan($contractEndDate->endOfMonth())) {
                        DB::rollBack();
                        return back()->withErrors([
                            'month_selected_id' => 'Cannot create timesheet after employee contract end date (' . $contractEndDate->format('F Y') . ').'
                        ]);
                    }
                }
            }
            
            // Validation 2: Check for pending leave requests in the selected month/year
            $pendingLeaveStatus = \App\Models\EmployeeLeaveStatus::pendingId();
            $pendingLeaves = \App\Models\EmployeeLeaveRequest::where('employee_id', $employeeId)
                ->where('status_id', $pendingLeaveStatus)
                ->where('archived', 'N')
                ->where(function ($query) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                    // Check for any leave requests that overlap with the timesheet month
                    $query->where(function ($q) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                        $q->where('date_from', '<=', $timesheetPeriodEnd)
                          ->where('date_to', '>=', $timesheetPeriodStart);
                    });
                })
                ->when($eventId, fn($q) => $q->where('event_id', $eventId))
                ->count();

            if ($pendingLeaves > 0) {
                DB::rollBack();
                return back()->withErrors([
                    'month_selected_id' => 'Cannot create timesheet for ' . $monthName . ' ' . $request->year_selected . '. This employee has ' . $pendingLeaves . ' pending leave request(s) for this period. Please approve or reject the leave request(s) first.'
                ]);
            }
            
            // Validation 3: Check for duplicate timesheet
            $exists = EmployeeTimesheet::active()
                ->where('employee_id', $employeeId)
                ->where('month_id', $request->month_selected_id)
                ->where('year', $request->year_selected)
                ->where(fn($q) => $eventId ? $q->where('event_id', $eventId) : $q->whereNull('event_id'))
                ->exists();

            if ($exists) {
                DB::rollBack();
                return back()->withErrors([
                    'month_selected_id' => 'A timesheet for ' . $monthName . ' ' . $request->year_selected . ' already exists for this employee.'
                ]);
            }
            
            // Validation 4: Check for bank account in the timesheet period
            $bankAccount = EmployeeBank::where('employee_id', $employeeId)
                ->where('archived', 'N')
                ->where(function ($query) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                    $query->where('effective_start_date', '<=', $timesheetPeriodEnd)
                        ->where(function ($q) use ($timesheetPeriodStart) {
                            $q->where('effective_end_date', '>=', $timesheetPeriodStart)
                                ->orWhere('effective_end_date', '9999-12-31')
                                ->orWhereNull('effective_end_date');
                        });
                })
                ->orderBy('effective_start_date', 'DESC')
                ->first();

            if (!$bankAccount) {
                DB::rollBack();
                return back()->withErrors([
                    'employee_id' => 'No bank account found for this employee within the requested period (' . $monthName . ' ' . $request->year_selected . '). Please add a bank account first.'
                ]);
            }
            
            // Validation 5: Check for salary in the timesheet period
            $salary = EmployeeSalary::where('employee_id', $employeeId)
                ->where('archived', 'N')
                ->where('active_flag', 1)
                ->where(function ($query) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                    $query->where('effective_start_date', '<=', $timesheetPeriodEnd)
                        ->where(function ($q) use ($timesheetPeriodStart) {
                            $q->where('effective_end_date', '>=', $timesheetPeriodStart)
                                ->orWhere('effective_end_date', '9999-12-31')
                                ->orWhereNull('effective_end_date');
                        });
                })
                ->orderBy('effective_start_date', 'DESC')
                ->first();

            if (!$salary) {
                DB::rollBack();
                return back()->withErrors([
                    'employee_id' => 'No salary found for this employee within the requested period (' . $monthName . ' ' . $request->year_selected . '). Please add a salary first.'
                ]);
            }

            // All validations passed - create the timesheet
            $timesheet = EmployeeTimesheet::create([
                'archived'         => 'N',
                'employee_id'      => $employeeId,
                'user_id'          => Auth::id(),
                'event_id'         => $eventId,
                'month_id'         => $request->month_selected_id,
                'year'             => (string) $request->year_selected,
                'timesheet_period' => $monthName . '-' . $request->year_selected,
                'days_in_month'    => $daysInMonth,
                'status_id'        => EmployeeTimesheetStatus::pendingId(),
                'creator_id'       => Auth::id(),
            ]);

            // Calculate initial payment values (salary, daily rate, projected payment)
            $this->calculateTimesheetPayment($timesheet);

            DB::commit();
            return back()->with('success', 'Timesheet for ' . $monthName . ' ' . $request->year_selected . ' created successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Timesheet creation error: ' . $e->getMessage());
            return back()->withErrors([
                'error' => 'Error creating timesheet. Please try again.'
            ]);
        }
    }

    public function timesheetTalentStatus(Request $request)
    {
        $request->validate([
            'id'                     => 'required|integer|exists:employee_timesheets,id',
            'status_id'              => 'required|integer|in:1,2,3,4',
            'additional_information' => 'nullable|string|max:1000',
        ]);

        $ts = EmployeeTimesheet::findOrFail($request->id);
        $ts->update([
            'status_id'              => $request->status_id,
            'additional_information' => $request->additional_information,
            'performer_id'           => Auth::id(),
        ]);

        return back()->with('success', 'Status updated.');
    }

    public function timesheetTalentEntriesStore(Request $request)
    {
        $request->validate([
            'employee_timesheet_id' => 'required|integer|exists:employee_timesheets,id',
            'employee_id'           => 'required|integer',
            'calendar_day'          => 'required|array|min:1',
            'calendar_day.*'        => 'required|integer|min:1|max:31',
            'day_action'            => 'required|array',
            'day_action.*'          => 'required|in:W,L,U',
        ]);

        $tsId       = $request->employee_timesheet_id;
        $employeeId = $request->employee_id;
        $days       = $request->calendar_day;
        $actions    = $request->day_action;

        DB::transaction(function () use ($tsId, $employeeId, $days, $actions) {
            // Get the timesheet and employee
            $timesheet = EmployeeTimesheet::with('employee')->findOrFail($tsId);
            $employee = $timesheet->employee;
            $eventId = $timesheet->event_id;
            
            // Determine valid day range based on contract dates
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $timesheet->month_id, $timesheet->year);
            $validStartDay = 1;
            $validEndDay = $daysInMonth;
            
            if ($eventId && $employee) {
                // Event context: use event assignment dates
                $pivot = $employee->events()->where('events.id', $eventId)->first()?->pivot;
                if ($pivot) {
                    if ($pivot->assigned_at) {
                        $assignedAt = \Carbon\Carbon::parse($pivot->assigned_at);
                        if ($assignedAt->year == $timesheet->year && $assignedAt->month == $timesheet->month_id) {
                            $validStartDay = max($validStartDay, $assignedAt->day);
                        }
                    }
                    if ($pivot->released_at) {
                        $releasedAt = \Carbon\Carbon::parse($pivot->released_at);
                        if ($releasedAt->year == $timesheet->year && $releasedAt->month == $timesheet->month_id) {
                            $validEndDay = min($validEndDay, $releasedAt->day);
                        }
                    }
                }
            } elseif ($employee) {
                // No event: use employee contract dates
                if ($employee->contract_start_date) {
                    $contractStart = \Carbon\Carbon::parse($employee->contract_start_date);
                    if ($contractStart->year == $timesheet->year && $contractStart->month == $timesheet->month_id) {
                        $validStartDay = max($validStartDay, $contractStart->day);
                    }
                }
                if ($employee->contract_end_date) {
                    $contractEnd = \Carbon\Carbon::parse($employee->contract_end_date);
                    if ($contractEnd->year == $timesheet->year && $contractEnd->month == $timesheet->month_id) {
                        $validEndDay = min($validEndDay, $contractEnd->day);
                    }
                }
            }
            
            // Validate all submitted days are within valid range
            $invalidDays = [];
            foreach ($days as $day) {
                if ($day < $validStartDay || $day > $validEndDay) {
                    $invalidDays[] = $day;
                }
            }
            
            if (!empty($invalidDays)) {
                $contractInfo = '';
                if ($eventId) {
                    $contractInfo = 'Event assignment period';
                } else {
                    $contractInfo = 'Employee contract period';
                }
                throw new \Exception(
                    "Cannot save entries for day(s) " . implode(', ', $invalidDays) . ". " .
                    "{$contractInfo} allows only days {$validStartDay} to {$validEndDay} for this month."
                );
            }
            
            // Delete existing entries for this timesheet then re-insert
            EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->delete();

            $now    = now();
            $userId = Auth::id();
            $rows   = [];
            foreach ($days as $i => $day) {
                $rows[] = [
                    'employee_timesheet_id' => $tsId,
                    'employee_id'           => $employeeId,
                    'user_id'               => $userId,
                    'calendar_day'          => $day,
                    'day_action'            => $actions[$i],
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }
            EmployeeTimesheetEntry::insert($rows);

            // Calculate and update timesheet payment
            $timesheet = EmployeeTimesheet::findOrFail($tsId);
            $this->calculateTimesheetPayment($timesheet);
            
            // Mark entries as existing and ensure status is Pending
            $timesheet->update([
                'entries_exists' => 'Y',
                'status_id' => EmployeeTimesheetStatus::pendingId(),
            ]);
        });

        return back()->with('success', 'Timesheet entries saved.');
    }

    public function timesheetTalentDestroy($id)
    {
        $ts = EmployeeTimesheet::findOrFail($id);
        
        // Authorization: Check if user has permission to delete this timesheet
        $hrRole = $this->getHRRole();
        $isEmployee = !in_array($hrRole, ['admin', 'manager']);
        
        if ($isEmployee) {
            // Get current employee
            $currentEmployee = Employee::where('user_id', auth()->id())->first();
            
            // Ensure employee can only delete their own timesheets
            if (!$currentEmployee || $ts->employee_id !== $currentEmployee->id) {
                return back()->with('error', 'You can only delete your own timesheets.');
            }
            
            // Ensure timesheet is not approved
            if ($ts->status_id === EmployeeTimesheetStatus::approvedId()) {
                return back()->with('error', 'Cannot delete approved timesheets.');
            }
        }
        
        $ts->update(['archived' => 'Y']);
        return back()->with('success', 'Timesheet deleted.');
    }

    // ── Timesheet Approvals ───────────────────────────────────────────

    public function approvalsTime()
    {
        $eventId = $this->getEffectiveEventIds(); // Support manager "All My Events"
        $role = $this->getHRRole();
        
        // Get submitted timesheets (ready for manager/admin approval)
        $submittedStatusId = EmployeeTimesheetStatus::submittedId();
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
        
        // Submitted timesheets (manager review)
        $submittedQuery = EmployeeTimesheet::active()
            ->with(['employee', 'event', 'performer:id,full_name', 'entries'])
            ->where('status_id', $submittedStatusId)
            ->forEvent($eventId)
            ->orderBy('created_at', 'DESC');
        
        $DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        $submittedTimesheets = $submittedQuery->get()->map(function ($ts) use ($monthNames, $DAYS) {
            $emp = $ts->employee;
            $period = $monthNames[$ts->month_id] . ' ' . $ts->year;
            
            // Build entries array
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $ts->month_id, $ts->year);
            $savedEntries = $ts->entries->keyBy('calendar_day');
            $entries = [];
            
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $date = new \DateTime("{$ts->year}-{$ts->month_id}-{$d}");
                $dow = (int) $date->format('w');
                $isWeekend = $dow === 5 || $dow === 6;
                $savedEntry = $savedEntries[$d] ?? null;
                $action = $savedEntry ? $savedEntry->day_action : ($isWeekend ? '0' : 'W');
                
                $entries[] = [
                    'day'       => $d,
                    'dayName'   => $DAYS[$dow],
                    'isWeekend' => $isWeekend,
                    'action'    => $action,
                ];
            }
            
            return [
                'id'        => $ts->id,
                'emp'       => $emp?->full_name ?? 'Unknown',
                'empId'     => $emp?->employee_number ?? 'N/A',
                'c'         => $emp?->avatar_color ?? 0,
                'period'    => $period,
                'monthNumber' => $ts->month_id,
                'year'      => $ts->year,
                'daysInMonth' => $daysInMonth,
                'worked'    => $ts->days_worked ?? 0,
                'leave'     => $ts->leave_taken ?? 0,
                'unpaid'    => $ts->unpaid_leave_taken ?? 0,
                'submitted' => $ts->created_at?->format('Y-m-d'),
                'note'      => $ts->additional_information ?? '',
                'status'    => 'submitted',
                'eventName' => $ts->event?->name,
                'entries'   => $entries,
            ];
        });
        
        // Pending payroll timesheets (payroll review) - admin only
        $payrollTimesheets = collect([]);
        if ($role === 'admin') {
            $payrollQuery = EmployeeTimesheet::active()
                ->with(['employee', 'event', 'performer:id,full_name', 'entries'])
                ->where('status_id', $pendingPayrollStatusId)
                ->forEvent($eventId)
                ->orderBy('updated_at', 'DESC');
            
            $payrollTimesheets = $payrollQuery->get()->map(function ($ts) use ($monthNames, $DAYS) {
                $emp = $ts->employee;
                $period = $monthNames[$ts->month_id] . ' ' . $ts->year;
                
                // Build entries array
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $ts->month_id, $ts->year);
                $savedEntries = $ts->entries->keyBy('calendar_day');
                $entries = [];
                
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $date = new \DateTime("{$ts->year}-{$ts->month_id}-{$d}");
                    $dow = (int) $date->format('w');
                    $isWeekend = $dow === 5 || $dow === 6;
                    $savedEntry = $savedEntries[$d] ?? null;
                    $action = $savedEntry ? $savedEntry->day_action : ($isWeekend ? '0' : 'W');
                    
                    $entries[] = [
                        'day'       => $d,
                        'dayName'   => $DAYS[$dow],
                        'isWeekend' => $isWeekend,
                        'action'    => $action,
                    ];
                }
                
                return [
                    'id'        => $ts->id,
                    'emp'       => $emp?->full_name ?? 'Unknown',
                    'empId'     => $emp?->employee_number ?? 'N/A',
                    'c'         => $emp?->avatar_color ?? 0,
                    'period'    => $period,
                    'monthNumber' => $ts->month_id,
                    'year'      => $ts->year,
                    'daysInMonth' => $daysInMonth,
                    'worked'    => $ts->days_worked ?? 0,
                    'leave'     => $ts->leave_taken ?? 0,
                    'unpaid'    => $ts->unpaid_leave_taken ?? 0,
                    'approved'  => $ts->updated_at?->format('Y-m-d'),
                    'note'      => $ts->additional_information ?? '',
                    'status'    => 'pending-payroll',
                    'eventName' => $ts->event?->name,
                    'entries'   => $entries,
                ];
            });
        }
        
        return Inertia::render('MeridianHR/TimesheetApprovals', array_merge($this->getCommonProps('approve-time'), [
            'submittedTimesheets' => $submittedTimesheets,
            'payrollTimesheets' => $payrollTimesheets,
            'isAdmin' => $role === 'admin',
        ]));
    }
    
    public function approveTimesheet(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:employee_timesheets,id',
            'additional_information' => 'nullable|string|max:1000',
        ]);
        
        // Manager/Admin approval sends to Pending Payroll status
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        $submittedStatusId = EmployeeTimesheetStatus::submittedId();
        $userId = Auth::id();
        $additionalInfo = $request->additional_information;
        
        DB::transaction(function () use ($request, $pendingPayrollStatusId, $submittedStatusId, $userId, $additionalInfo) {
            EmployeeTimesheet::whereIn('id', $request->ids)
                ->where('status_id', $submittedStatusId)
                ->update([
                    'status_id'   => $pendingPayrollStatusId,
                    'performer_id' => $userId,
                    'additional_information' => $additionalInfo,
                    'updated_at'  => now(),
                ]);
        });
        
        return back()->with('success', count($request->ids) . ' timesheet(s) approved and sent to payroll');
    }
    
    public function rejectTimesheet(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:employee_timesheets,id',
            'additional_information' => 'nullable|string|max:1000',
        ]);
        
        $rejectedStatusId = EmployeeTimesheetStatus::rejectedId();
        $submittedStatusId = EmployeeTimesheetStatus::submittedId();
        $userId = Auth::id();
        $additionalInfo = $request->additional_information;
        
        DB::transaction(function () use ($request, $rejectedStatusId, $submittedStatusId, $userId, $additionalInfo) {
            EmployeeTimesheet::whereIn('id', $request->ids)
                ->where('status_id', $submittedStatusId)
                ->update([
                    'status_id'   => $rejectedStatusId,
                    'performer_id' => $userId,
                    'additional_information' => $additionalInfo,
                    'updated_at'  => now(),
                ]);
        });
        
        return back()->with('success', count($request->ids) . ' timesheet(s) rejected');
    }

    /**
     * Payroll approval - final approval after manager review
     */
    public function payrollApproveTimesheet(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:employee_timesheets,id',
            'payroll_additional_information' => 'nullable|string|max:1000',
        ]);
        
        $approvedStatusId = EmployeeTimesheetStatus::approvedId();
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        $userId = Auth::id();
        $payrollInfo = $request->payroll_additional_information;
        
        DB::transaction(function () use ($request, $approvedStatusId, $pendingPayrollStatusId, $userId, $payrollInfo) {
            EmployeeTimesheet::whereIn('id', $request->ids)
                ->where('status_id', $pendingPayrollStatusId)
                ->update([
                    'status_id'   => $approvedStatusId,
                    'payroll_approval_id' => $approvedStatusId,
                    'payroll_reviewed' => 1,
                    'payroll_additional_information' => $payrollInfo,
                    'updated_at'  => now(),
                ]);
        });
        
        return back()->with('success', count($request->ids) . ' timesheet(s) approved by payroll');
    }

    /**
     * Payroll rejection - rejects timesheet after manager approval
     */
    public function payrollRejectTimesheet(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'required|integer|exists:employee_timesheets,id',
            'payroll_additional_information' => 'nullable|string|max:1000',
        ]);
        
        $rejectedStatusId = EmployeeTimesheetStatus::rejectedId();
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        $userId = Auth::id();
        $payrollInfo = $request->payroll_additional_information;
        
        DB::transaction(function () use ($request, $rejectedStatusId, $pendingPayrollStatusId, $userId, $payrollInfo) {
            EmployeeTimesheet::whereIn('id', $request->ids)
                ->where('status_id', $pendingPayrollStatusId)
                ->update([
                    'status_id'   => $rejectedStatusId,
                    'payroll_approval_id' => $rejectedStatusId,
                    'payroll_reviewed' => 1,
                    'payroll_additional_information' => $payrollInfo,
                    'updated_at'  => now(),
                ]);
        });
        
        return back()->with('success', count($request->ids) . ' timesheet(s) rejected by payroll');
    }

    /**
     * Calculate base days for display (what worked+leave+unpaid adds up to)
     * - February full month: 28 or 29
     * - Other months full month: 30
     * - Partial month: calendar days in range
     */
    private function calculateBaseDays(int $startDay, int $endDay, int $daysInMonth, int $monthId): int
    {
        $isFullMonth = ($startDay <= 5 && $endDay >= $daysInMonth - 2);
        
        if ($isFullMonth) {
            // Full month: use actual days for February, 30 for others
            return ($monthId == 2) ? $daysInMonth : 30;
        } else {
            // Partial month: calendar days in range
            return $endDay - $startDay + 1;
        }
    }

    /**
     * Calculate base days from a timesheet model
     */
    private function calculateBaseDaysFromTimesheet(EmployeeTimesheet $timesheet): int
    {
        // Get first and last days from entries
        $firstDay = EmployeeTimesheetEntry::where('employee_timesheet_id', $timesheet->id)
            ->orderBy('calendar_day', 'ASC')
            ->first();
        $lastDay = EmployeeTimesheetEntry::where('employee_timesheet_id', $timesheet->id)
            ->orderBy('calendar_day', 'DESC')
            ->first();

        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $timesheet->month_id, $timesheet->year);
        
        if (!$firstDay || !$lastDay) {
            $startDay = 1;
            $endDay = $daysInMonth;
        } else {
            $startDay = $firstDay->calendar_day;
            $endDay = $lastDay->calendar_day;
        }

        return $this->calculateBaseDays($startDay, $endDay, $daysInMonth, $timesheet->month_id);
    }
}
