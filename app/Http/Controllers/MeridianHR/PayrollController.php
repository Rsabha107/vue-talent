<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetStatus;
use App\Models\EmployeeTimesheetEntry;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Payroll Module Controller
 * 
 * Handles payroll operations:
 * - Dashboard (overview stats)
 * - Timesheet Review (Stage 2 - Final Approval)
 * - Payment Batches (generate and manage)
 * - Bank Files (generate payment files)
 * - Missing Timesheets Report
 */
class PayrollController extends Controller
{
    /**
     * Show payroll dashboard
     */
    public function dashboard()
    {
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        $approvedStatusId = EmployeeTimesheetStatus::approvedId();
        
        // Get pending timesheets count
        $pendingCount = EmployeeTimesheet::where('status_id', $pendingPayrollStatusId)
            ->where('archived', 'N')
            ->count();
        
        // Get approved timesheets this month
        $approvedCount = EmployeeTimesheet::where('status_id', $approvedStatusId)
            ->where('archived', 'N')
            ->whereMonth('created_at', now()->month)
            ->count();
        
        $totalApprovedAmount = EmployeeTimesheet::where('status_id', $approvedStatusId)
            ->where('archived', 'N')
            ->whereMonth('created_at', now()->month)
            ->sum('total_payment');
        
        // Get missing timesheets count
        $missingCount = $this->calculateMissingTimesheetsCount();
        
        // Get pending timesheets for display
        $pendingTimesheets = EmployeeTimesheet::with(['employee', 'status'])
            ->where('status_id', $pendingPayrollStatusId)
            ->where('archived', 'N')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($ts) {
                return [
                    'id' => $ts->id,
                    'employeeName' => $ts->employee->full_name ?? 'N/A',
                    'period' => $ts->timesheet_period,
                    'daysWorked' => $ts->days_worked,
                    'totalPayment' => $ts->total_payment,
                ];
            });
        
        return Inertia::render('Payroll/Dashboard', [
            'payrollPage' => 'dashboard',
            'stats' => [
                'pendingTimesheetCount' => $pendingCount,
                'missingTimesheetCount' => $missingCount,
                'approvedTimesheetCount' => $approvedCount,
                'totalApprovedAmount' => $totalApprovedAmount,
                'nextPayrollDate' => now()->endOfMonth()->format('d M Y'),
                'daysUntilPayroll' => now()->diffInDays(now()->endOfMonth()),
            ],
            'pendingTimesheets' => $pendingTimesheets,
            'missingTimesheets' => [],
            'recentBatches' => [],
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Show timesheet review page
     */
    public function timesheetReview()
    {
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        
        $DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        $timesheets = EmployeeTimesheet::with(['employee', 'status', 'performer', 'entries'])
            ->where('status_id', $pendingPayrollStatusId)
            ->where('archived', 'N')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($ts) use ($DAYS) {
                // Build entries array (same format as TimesheetApprovals)
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
                    'id' => $ts->id,
                    'employeeName' => $ts->employee->full_name ?? 'N/A',
                    'employeeNumber' => $ts->employee->employee_number ?? 'N/A',
                    'period' => $ts->timesheet_period,
                    'monthId' => $ts->month_id,
                    'monthNumber' => $ts->month_id,
                    'year' => $ts->year,
                    'daysWorked' => $ts->days_worked,
                    'leaveTaken' => $ts->leave_taken,
                    'unpaidLeave' => $ts->unpaid_leave_taken,
                    'totalPayment' => $ts->total_payment,
                    'approverName' => $ts->performer->name ?? 'N/A',
                    'additionalInfo' => $ts->additional_information,
                    'entries' => $entries,
                ];
            });
        
        return Inertia::render('Payroll/TimesheetReview', [
            'payrollPage' => 'timesheet-review',
            'payrollTimesheets' => $timesheets,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Bulk approve timesheets (final approval)
     */
    public function bulkApproveTimesheets(Request $request)
    {
        $request->validate([
            'timesheet_ids' => 'required|array',
            'timesheet_ids.*' => 'required|integer|exists:employee_timesheets,id',
            'additional_information' => 'nullable|string',
        ]);
        
        $approvedStatusId = EmployeeTimesheetStatus::approvedId();
        
        EmployeeTimesheet::whereIn('id', $request->timesheet_ids)
            ->update([
                'status_id' => $approvedStatusId,
                'payroll_approval_id' => $approvedStatusId,
                'payroll_reviewed' => 1,
                'payroll_additional_information' => $request->additional_information,
            ]);
        
        return redirect()->back()->with('success', count($request->timesheet_ids) . ' timesheet(s) approved successfully');
    }

    /**
     * Approve single timesheet (final approval)
     */
    public function approveTimesheet(Request $request, $id)
    {
        $request->validate([
            'additional_information' => 'nullable|string',
        ]);
        
        $timesheet = EmployeeTimesheet::findOrFail($id);
        $approvedStatusId = EmployeeTimesheetStatus::approvedId();
        
        $timesheet->update([
            'status_id' => $approvedStatusId,
            'payroll_approval_id' => $approvedStatusId,
            'payroll_reviewed' => 1,
            'payroll_additional_information' => $request->additional_information,
        ]);
        
        return redirect()->back()->with('success', 'Timesheet approved successfully');
    }

    /**
     * Reject single timesheet
     */
    public function rejectTimesheet(Request $request, $id)
    {
        $request->validate([
            'additional_information' => 'nullable|string',
        ]);
        
        $timesheet = EmployeeTimesheet::findOrFail($id);
        $rejectedStatusId = EmployeeTimesheetStatus::rejectedId();
        
        $timesheet->update([
            'status_id' => $rejectedStatusId,
            'payroll_approval_id' => $rejectedStatusId,
            'payroll_reviewed' => 1,
            'payroll_additional_information' => $request->additional_information,
        ]);
        
        return redirect()->back()->with('success', 'Timesheet rejected');
    }

    /**
     * Bulk reject timesheets
     */
    public function bulkRejectTimesheets(Request $request)
    {
        $request->validate([
            'timesheet_ids' => 'required|array',
            'timesheet_ids.*' => 'required|integer|exists:employee_timesheets,id',
            'additional_information' => 'nullable|string',
        ]);
        
        $rejectedStatusId = EmployeeTimesheetStatus::rejectedId();
        
        EmployeeTimesheet::whereIn('id', $request->timesheet_ids)
            ->update([
                'status_id' => $rejectedStatusId,
                'payroll_approval_id' => $rejectedStatusId,
                'payroll_reviewed' => 1,
                'payroll_additional_information' => $request->additional_information,
            ]);
        
        return redirect()->back()->with('success', count($request->timesheet_ids) . ' timesheet(s) rejected');
    }

    /**
     * Show missing timesheets report
     */
    public function missingTimesheets()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Get all active employees
        $activeEmployees = Employee::active()
            ->with(['department', 'designation', 'user'])
            ->get();
        
        $missingData = [];
        
        foreach ($activeEmployees as $employee) {
            // Check if employee has submitted timesheet for current month
            $hasTimesheet = EmployeeTimesheet::where('employee_id', $employee->id)
                ->where('month_id', $currentMonth)
                ->where('year', $currentYear)
                ->where('archived', 'N')
                ->exists();
            
            if (!$hasTimesheet) {
                // Get last submitted timesheet date
                $lastTimesheet = EmployeeTimesheet::where('employee_id', $employee->id)
                    ->where('archived', 'N')
                    ->orderBy('created_at', 'desc')
                    ->first();
                
                // Calculate days overdue (assuming deadline is 5th of next month)
                $deadline = now()->startOfMonth()->addDays(5);
                $daysOverdue = now()->diffInDays($deadline, false);
                $daysOverdue = $daysOverdue < 0 ? abs($daysOverdue) : 0;
                
                $missingData[] = [
                    'id' => $employee->id,
                    'fullName' => $employee->full_name,
                    'email' => $employee->user->email ?? 'N/A',
                    'employeeNumber' => $employee->employee_number,
                    'department' => $employee->department->name ?? 'N/A',
                    'designation' => $employee->designation->title ?? 'N/A',
                    'period' => now()->format('F Y'),
                    'lastSubmittedDate' => $lastTimesheet?->created_at?->toDateString(),
                    'daysOverdue' => $daysOverdue,
                ];
            }
        }
        
        return Inertia::render('Payroll/MissingTimesheets', [
            'payrollPage' => 'missing-timesheets',
            'missingTimesheets' => $missingData,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Show all timesheets (read-only view for payroll)
     */
    public function allTimesheets()
    {
        $statuses = EmployeeTimesheetStatus::orderBy('id')->get()->map(fn($s) => [
            'id' => $s->id,
            'title' => $s->title,
        ]);
        $statusMap = collect($statuses)->keyBy('id');

        // Get all active employees
        $employees = Employee::active()
            ->orderBy('full_name')
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'full_name' => $e->full_name,
                'employee_number' => $e->employee_number,
            ]);

        // Get all timesheets with entries
        $tsList = EmployeeTimesheet::with(['employee', 'event', 'entries'])
            ->orderBy('year', 'desc')
            ->orderBy('month_id', 'desc')
            ->get();

        // Get approved leaves
        $employeeIds = $employees->pluck('id')->toArray();
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
                'employeeNumber' => $ts->employee?->employee_number,
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
                'dailyRate' => $ts->daily_rate ?? 0,
                'salary' => $ts->salary ?? 0,
                'payment' => $ts->total_payment ?? 0,
            ];
        });

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('Payroll/AllTimesheets', [
            'payrollPage' => 'all-timesheets',
            'employees' => $employees,
            'monthsName' => $months,
            'years' => [2024, 2025, 2026],
            'statuses' => $statuses,
            'timesheets' => $timesheets,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Show payment batches page
     */
    public function paymentBatches()
    {
        // TODO: Implement payment batches logic
        
        return Inertia::render('Payroll/PaymentBatches', [
            'payrollPage' => 'payment-batches',
            'batches' => [],
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Show bank files page
     */
    public function bankFiles()
    {
        // TODO: Implement bank files logic
        
        return Inertia::render('Payroll/BankFiles', [
            'payrollPage' => 'bank-files',
            'bankFiles' => [],
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Get payroll-specific permissions for frontend
     */
    protected function getPayrollPermissions(): array
    {
        $user = Auth::user();
        
        if (!$user) {
            return [
                'payrollAccess' => false,
                'payrollReviewTimesheets' => false,
                'payrollApproveTimesheets' => false,
                'payrollProcessPayments' => false,
            ];
        }
        
        return [
            'payrollAccess' => $user->can('payroll.access'),
            'payrollReviewTimesheets' => $user->can('payroll.review.timesheets'),
            'payrollApproveTimesheets' => $user->can('payroll.approve.timesheets'),
            'payrollProcessPayments' => $user->can('payroll.process.payments'),
        ];
    }

    /**
     * Get pending counts for navigation badges
     */
    protected function getPendingCounts(): array
    {
        $pendingPayrollStatusId = EmployeeTimesheetStatus::pendingPayrollId();
        
        return [
            'pendingPayrollTimesheets' => EmployeeTimesheet::where('status_id', $pendingPayrollStatusId)
                ->where('archived', 'N')
                ->count(),
            'missingTimesheets' => $this->calculateMissingTimesheetsCount(),
        ];
    }

    /**
     * Calculate number of employees with missing timesheets
     */
    protected function calculateMissingTimesheetsCount(): int
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        
        // Get all active employees
        $activeEmployeeIds = Employee::active()->pluck('id');
        
        // Get employees who have submitted timesheets for current month
        $submittedEmployeeIds = EmployeeTimesheet::whereIn('employee_id', $activeEmployeeIds)
            ->where('month_id', $currentMonth)
            ->where('year', $currentYear)
            ->where('archived', 'N')
            ->pluck('employee_id');
        
        // Count employees without timesheets
        return $activeEmployeeIds->diff($submittedEmployeeIds)->count();
    }

    /**
     * Get accessible modules for this user
     * (Shared with HR module for consistent module switcher)
     */
    protected function getAccessibleModules(): array
    {
        $user = Auth::user();
        $modules = [];
        
        // Everyone gets HR module (at minimum for self-service)
        $modules[] = [
            'key' => 'hr',
            'name' => 'HR',
            'icon' => 'users',
            'url' => route('hr.dashboard'),
        ];
        
        // Payroll module
        if ($user->can('payroll.access')) {
            $modules[] = [
                'key' => 'payroll',
                'name' => 'Payroll',
                'icon' => 'dollar',
                'url' => route('payroll.dashboard'),
            ];
        }
        
        // Future modules
        if ($user->can('procurement.access')) {
            $modules[] = [
                'key' => 'procurement',
                'name' => 'Procurement',
                'icon' => 'cart',
                'url' => '/procurement/dashboard',
            ];
        }
        
        if ($user->can('recruiting.access')) {
            $modules[] = [
                'key' => 'recruiting',
                'name' => 'Recruiting',
                'icon' => 'briefcase',
                'url' => '/recruiting/dashboard',
            ];
        }
        
        return $modules;
    }
}
