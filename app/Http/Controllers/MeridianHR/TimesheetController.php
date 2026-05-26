<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\EmployeeBank;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\EmployeeSalary;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        return [
            ['id' => 1, 'title' => 'Pending'  ],
            ['id' => 2, 'title' => 'Submitted'],
            ['id' => 3, 'title' => 'Approved' ],
            ['id' => 4, 'title' => 'Rejected' ],
        ];
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

    public function submitTimesheet(Request $request)
    {
        $request->validate(['month' => 'required|string']);
        // In production: mark timesheet as submitted
        return back()->with('success', 'Timesheet submitted.');
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

    // ── TimesheetTalent (Admin/Manager) ───────────────────────────────

    public function timesheetTalent()
    {
        $eventId  = $this->getSelectedEventId();
        $statuses = $this->timesheetStatuses();
        $statusMap = collect($statuses)->keyBy('id');

        // Employees list
        $empQuery = Employee::active()->orderBy('full_name');
        if ($eventId) {
            $empQuery->whereHas('events', function ($q) use ($eventId) {
                $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
            });
        }
        $employees = $empQuery->get()->map(fn($e) => ['id' => $e->id, 'fullName' => $e->full_name]);

        // Timesheets with entries
        $tsQuery = EmployeeTimesheet::active()
            ->with(['employee', 'entries', 'performer:id,full_name'])
            ->forEvent($eventId)
            ->orderByDesc('year')
            ->orderByDesc('month_id');

        $DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        // Approved leave dates keyed by employee_id → [Y-m-d => true]
        $approvedStatusId = EmployeeLeaveStatus::where('title', 'Approved')->value('id');
        $leaveDays = [];
        if ($approvedStatusId) {
            EmployeeLeaveRequest::active()
                ->where('status_id', $approvedStatusId)
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

        $timesheets = $tsQuery->get()->map(function ($ts) use ($statusMap, $DAYS, $monthNames, $leaveDays, $eventId) {
            $emp         = $ts->employee;
            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $ts->month_id, $ts->year);
            $startDay    = 1;
            $endDay      = $daysInMonth;

            // Narrow to contract / event assignment window if applicable
            if ($eventId && $emp) {
                $pivot = $emp->events()->where('events.id', $eventId)->first()?->pivot;
                if ($pivot) {
                    if ($pivot->assigned_at) {
                        $a = \Carbon\Carbon::parse($pivot->assigned_at);
                        if ($a->year === $ts->year && $a->month === $ts->month_id) {
                            $startDay = max($startDay, $a->day);
                        }
                    }
                    if ($pivot->released_at) {
                        $r = \Carbon\Carbon::parse($pivot->released_at);
                        if ($r->year === $ts->year && $r->month === $ts->month_id) {
                            $endDay = min($endDay, $r->day);
                        }
                    }
                }
            }

            $empLeave = $leaveDays[$ts->employee_id] ?? [];
            $entries = $ts->entries->map(function ($e) use ($ts, $DAYS, $empLeave) {
                $date      = new \DateTime("{$ts->year}-{$ts->month_id}-{$e->calendar_day}");
                $dow       = (int) $date->format('w'); // 0=Sun, 5=Fri, 6=Sat
                return [
                    'day'       => $e->calendar_day,
                    'dayName'   => $DAYS[$dow],
                    'isWeekend' => $dow === 5 || $dow === 6, // Friday or Saturday
                    'action'    => $e->day_action,
                    'isLeave'   => isset($empLeave[$date->format('Y-m-d')]),
                ];
            })->values()->all();

            $period     = $monthNames[$ts->month_id] . '-' . $ts->year;
            $statusId   = $ts->status_id;
            $statusTitle = $statusMap[$statusId]['title'] ?? 'Pending';

            return [
                'id'            => $ts->id,
                'employeeId'    => $ts->employee_id,
                'employeeName'  => $emp?->full_name ?? 'Unknown',
                'employeeColor' => $emp?->avatar_color ?? 0,
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
                'totalDays'     => $ts->total_days_eligible_for_payment ?? 0,
                'dailyRate'     => $ts->daily_rate ? number_format($ts->daily_rate, 2) : '0.00',
                'salary'        => $ts->salary ? number_format($ts->salary, 2) : '0.00',
                'payment'       => $ts->total_payment ? number_format($ts->total_payment, 2) : '0.00',
                'approver'      => $ts->performer?->full_name ?? null,
            ];
        });

        return Inertia::render('MeridianHR/TimesheetTalent', array_merge($this->getCommonProps('timesheet-talent'), [
            'employees'  => $employees,
            'monthsName' => $this->monthsName(),
            'years'      => [2024, 2025, 2026],
            'statuses'   => $statuses,
            'timesheets' => $timesheets,
        ]));
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
            
            // Validation 2: Check for duplicate timesheet
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
            
            // Validation 3: Check for bank account in the timesheet period
            $bankAccount = EmployeeBank::where('employee_id', $employeeId)
                ->where('archived', 'N')
                ->where(function ($query) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                    $query->where('effective_start_date', '<=', $timesheetPeriodEnd)
                        ->where(function ($q) use ($timesheetPeriodStart) {
                            $q->where('effective_end_date', '>=', $timesheetPeriodStart)
                                ->orWhere('effective_end_date', '9999-12-31');
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
            
            // Validation 4: Check for salary in the timesheet period
            $salary = EmployeeSalary::where('employee_id', $employeeId)
                ->where('archived', 'N')
                ->where('active_flag', 1)
                ->where(function ($query) use ($timesheetPeriodStart, $timesheetPeriodEnd) {
                    $query->where('effective_start_date', '<=', $timesheetPeriodEnd)
                        ->where(function ($q) use ($timesheetPeriodStart) {
                            $q->where('effective_end_date', '>=', $timesheetPeriodStart)
                                ->orWhere('effective_end_date', '9999-12-31');
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
            EmployeeTimesheet::create([
                'archived'         => 'N',
                'employee_id'      => $employeeId,
                'user_id'          => Auth::id(),
                'event_id'         => $eventId,
                'month_id'         => $request->month_selected_id,
                'year'             => (string) $request->year_selected,
                'timesheet_period' => $monthName . '-' . $request->year_selected,
                'days_in_month'    => $daysInMonth,
                'status_id'        => 1, // Pending
                'creator_id'       => Auth::id(),
            ]);

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

            // Calculate and update timesheet summary fields
            $timesheet = EmployeeTimesheet::findOrFail($tsId);
            
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
                        ->orWhere('effective_end_date', '9999-12-31');
                })
                ->orderBy('effective_start_date', 'DESC')
                ->first();

            // Get bank account for this period
            $bank = EmployeeBank::where('employee_id', $employeeId)
                ->where('archived', 'N')
                ->where('effective_start_date', '<=', $timesheetPeriodEnd)
                ->where(function ($query) use ($timesheetPeriodStart) {
                    $query->where('effective_end_date', '>=', $timesheetPeriodStart)
                        ->orWhere('effective_end_date', '9999-12-31');
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

            $startDay = $firstDay->calendar_day;
            $endDay = $lastDay->calendar_day;

            // Adjust end day to 30 for month-end calculations
            if ($endDay === 31 || ($timesheet->month_id == 2 && in_array($endDay, [28, 29]))) {
                $endDay = 30;
            }

            // Count actions
            $countWorked = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'W')->count();
            $countLeaves = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'L')->count();
            $countUnpaid = EmployeeTimesheetEntry::where('employee_timesheet_id', $tsId)->where('day_action', 'U')->count();

            // Calculate payment
            $monthlySalary = $salary?->net_salary ?? 0;
            $dailyRate = $monthlySalary / 30;
            $workedDays = $endDay - $startDay + 1;
            $paidDays = max(0, $workedDays - $countUnpaid);
            $totalPayment = $dailyRate * $paidDays;

            // Update timesheet with calculated values
            $timesheet->update([
                'days_worked' => $countWorked,
                'leave_taken' => $countLeaves,
                'unpaid_leave_taken' => $countUnpaid,
                'total_days_eligible_for_payment' => $paidDays,
                'salary' => $monthlySalary,
                'daily_rate' => $dailyRate,
                'total_payment' => $totalPayment,
                'bank_id' => $bank?->id,
                'entries_exists' => 'Y',
                'status_id' => 1, // Reset to Pending when entries are updated
            ]);
        });

        return back()->with('success', 'Timesheet entries saved.');
    }

    public function timesheetTalentDestroy($id)
    {
        $ts = EmployeeTimesheet::findOrFail($id);
        $ts->update(['archived' => 'Y']);
        return back()->with('success', 'Timesheet deleted.');
    }

    // ── Timesheet Approvals ───────────────────────────────────────────

    public function approvalsTime()
    {
        return Inertia::render('MeridianHR/TimesheetApprovals', array_merge($this->getCommonProps('approve-time'), [
            'items'  => $this->pendingTimesheets(),
        ]));
    }
}
