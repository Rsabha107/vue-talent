<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\EmployeeTimesheet;
use App\Models\EmployeeTimesheetStatus;
use App\Models\EmployeeTimesheetEntry;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\PaymentBatch;
use App\Models\PaymentBatchItem;
use App\Models\EmployeeBank;
use App\Models\BankFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
class PayrollController extends BaseHRController
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
        // Payroll is organization-wide, no event filtering
        $batches = PaymentBatch::with(['creator', 'finalizer', 'processor'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'batchNumber' => $batch->batch_number,
                    'batchName' => $batch->batch_name,
                    'period' => $batch->period,
                    'status' => $batch->status,
                    'timesheetCount' => $batch->timesheet_count,
                    'employeeCount' => $batch->employee_count,
                    'totalAmount' => $batch->total_amount,
                    'createdAt' => $batch->created_at?->format('Y-m-d'),
                    'createdBy' => $batch->creator?->name,
                    'finalizedAt' => $batch->finalized_at?->format('Y-m-d'),
                    'finalizedBy' => $batch->finalizer?->name,
                    'processedAt' => $batch->processed_at?->format('Y-m-d'),
                    'processedBy' => $batch->processor?->name,
                    'canEdit' => $batch->canEdit(),
                    'canFinalize' => $batch->canFinalize(),
                    'canProcess' => $batch->canProcess(),
                ];
            });
        
        return Inertia::render('Payroll/PaymentBatches', [
            'payrollPage' => 'payment-batches',
            'batches' => $batches,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Create a new payment batch from approved timesheets
     */
    public function createPaymentBatch(Request $request)
    {
        $request->validate([
            'batch_name' => 'required|string|max:200',
            'month_id' => 'required|integer',
            'year' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        $approvedStatusId = EmployeeTimesheetStatus::approvedId();

        try {
            DB::beginTransaction();

            // Log what we're looking for
            \Log::info('Creating payment batch', [
                'month_id' => $request->month_id,
                'year' => $request->year,
                'approved_status_id' => $approvedStatusId,
            ]);

            // Get approved timesheets for the period that aren't already in a batch
            // Payroll is organization-wide, includes all events
            $timesheets = EmployeeTimesheet::with(['employee', 'employee.banks'])
                ->where('status_id', $approvedStatusId)
                ->where('month_id', $request->month_id)
                ->where('year', $request->year)
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('payment_batch_items')
                        ->whereColumn('payment_batch_items.timesheet_id', 'employee_timesheets.id');
                })
                ->get();

            \Log::info('Found timesheets', [
                'count' => $timesheets->count(),
            ]);

            if ($timesheets->isEmpty()) {
                DB::rollBack();
                return back()->withErrors(['error' => 'No approved timesheets found for this period that are not already in a batch.']);
            }

            // Generate batch number (format: BATCH-YYYYMM-XXX)
            $batchPrefix = 'BATCH-' . $request->year . str_pad($request->month_id, 2, '0', STR_PAD_LEFT);
            $lastBatch = PaymentBatch::where('batch_number', 'LIKE', $batchPrefix . '%')
                ->orderBy('batch_number', 'desc')
                ->first();
            
            $sequence = 1;
            if ($lastBatch) {
                $lastSequence = (int) substr($lastBatch->batch_number, -3);
                $sequence = $lastSequence + 1;
            }
            $batchNumber = $batchPrefix . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);

            // Create the payment batch
            $batch = PaymentBatch::create([
                'batch_number' => $batchNumber,
                'batch_name' => $request->batch_name,
                'event_id' => null, // Payroll is organization-wide
                'period' => date('F Y', mktime(0, 0, 0, $request->month_id, 1, $request->year)),
                'month_id' => $request->month_id,
                'year' => $request->year,
                'status' => 'draft',
                'timesheet_count' => $timesheets->count(),
                'employee_count' => $timesheets->pluck('employee_id')->unique()->count(),
                'total_amount' => $timesheets->sum('total_payment'),
                'notes' => $request->notes,
                'created_by' => Auth::id(),
            ]);

            \Log::info('Payment batch created', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
            ]);

            // Create batch items
            foreach ($timesheets as $timesheet) {
                // Get agreement number and designation from employee_events pivot table
                $eventAssignment = DB::table('employee_events')
                    ->where('employee_id', $timesheet->employee_id)
                    ->where('event_id', $timesheet->event_id)
                    ->first();
                
                $agreementNumber = $eventAssignment->agreement_number ?? null;
                
                // Get designation name
                $designationName = null;
                if ($eventAssignment && $eventAssignment->designation_id) {
                    $designation = \App\Models\Designation::find($eventAssignment->designation_id);
                    $designationName = $designation?->name;
                }
                
                // Get primary bank (last created bank with no end date - still active)
                $primaryBank = $timesheet->employee->banks
                    ->filter(function ($bank) {
                        return !$bank->effective_end_date || 
                               $bank->effective_end_date->format('Y-m-d') === '9999-12-31';
                    })
                    ->sortByDesc('created_at')
                    ->first();
                
                Log::info('Processing timesheet', [
                    'timesheet_id' => $timesheet->id,
                    'employee_id' => $timesheet->employee_id,
                    'event_id' => $timesheet->event_id,
                    'employee_name' => $timesheet->employee->full_name ?? 'N/A',
                    'employee_number' => $timesheet->employee->employee_number,
                    'agreement_number' => $agreementNumber,
                    'role' => $designationName,
                    'primary_bank_id' => $primaryBank?->id,
                    'iban' => $primaryBank?->iban,
                ]);

                PaymentBatchItem::create([
                    'payment_batch_id' => $batch->id,
                    'timesheet_id' => $timesheet->id,
                    'employee_id' => $timesheet->employee_id,
                    'employee_number' => $timesheet->employee->employee_number,
                    'agreement_number' => $agreementNumber,
                    'employee_name' => $timesheet->employee->first_name . ' ' . $timesheet->employee->last_name,
                    'role' => $designationName,
                    'bank_id' => $primaryBank?->id,
                    'account_number' => $primaryBank?->iban, // IBAN from employee_banks table
                    'days_worked' => $timesheet->days_worked,
                    'leave_taken' => $timesheet->leave_taken,
                    'unpaid_leave_taken' => $timesheet->unpaid_leave_taken,
                    'total_days_paid' => $timesheet->total_days_eligible_for_payment,
                    'daily_rate' => $timesheet->daily_rate,
                    'payment_amount' => $timesheet->total_payment,
                ]);
            }

            DB::commit();

            return redirect()->route('payroll.payment-batches')
                ->with('success', 'Payment batch created successfully with ' . $timesheets->count() . ' timesheet(s).');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create payment batch: ' . $e->getMessage()]);
        }
    }

    /**
     * Show batch details
     */
    public function showPaymentBatch($id)
    {
        $batch = PaymentBatch::with([
            'creator', 
            'finalizer', 
            'processor', 
            'items.employee',
            'items.bank',
            'items.timesheet'
        ])->findOrFail($id);

        $batchData = [
            'id' => $batch->id,
            'batchNumber' => $batch->batch_number,
            'batchName' => $batch->batch_name,
            'period' => $batch->period,
            'status' => $batch->status,
            'timesheetCount' => $batch->timesheet_count,
            'employeeCount' => $batch->employee_count,
            'totalAmount' => $batch->total_amount,
            'notes' => $batch->notes,
            'createdAt' => $batch->created_at?->format('Y-m-d H:i'),
            'createdBy' => $batch->creator?->name,
            'finalizedAt' => $batch->finalized_at?->format('Y-m-d H:i'),
            'finalizedBy' => $batch->finalizer?->name,
            'processedAt' => $batch->processed_at?->format('Y-m-d H:i'),
            'processedBy' => $batch->processor?->name,
            'canEdit' => $batch->canEdit(),
            'canFinalize' => $batch->canFinalize(),
            'canProcess' => $batch->canProcess(),
            'items' => $batch->items->map(function ($item) {
                $employee = $item->employee;
                $timesheet = $item->timesheet;
                
                // Get employee event assignment dates (from timesheet's event)
                $assignment = $employee && $timesheet ? \DB::table('employee_events')
                    ->where('employee_id', $employee->id)
                    ->where('event_id', $timesheet->event_id)
                    ->first() : null;

                // Get latest salary
                $latestSalary = $employee ? \App\Models\EmployeeSalary::where('employee_id', $employee->id)
                    ->where('effective_start_date', '<=', now())
                    ->orderBy('effective_start_date', 'desc')
                    ->first() : null;

                // Determine salary basis (full vs partial month)
                $salaryBasis = 'N/A';
                if ($timesheet) {
                    $daysInMonth = $timesheet->days_in_month ?: 30;
                    $eligibleDays = $timesheet->total_days_eligible_for_payment;
                    // If eligible days >= 90% of month, consider it full month
                    $salaryBasis = ($eligibleDays >= ($daysInMonth * 0.9)) ? 'Full Month' : 'Partial Month';
                }

                return [
                    'id' => $item->id,
                    'timesheetPeriod' => $timesheet?->timesheet_period,
                    'agreementNumber' => $item->agreement_number ?: 'N/A',
                    'employeeName' => $item->employee_name,
                    'role' => $item->role ?: 'N/A',
                    'startDate' => $assignment?->assigned_at ? \Carbon\Carbon::parse($assignment->assigned_at)->format('d M Y') : 'N/A',
                    'endDate' => $assignment?->released_at ? \Carbon\Carbon::parse($assignment->released_at)->format('d M Y') : 'Ongoing',
                    'monthlySalary' => $latestSalary?->net_salary ?? 0,
                    'salaryBasis' => $salaryBasis,
                    'daysWorked' => $item->days_worked,
                    'paymentAmount' => $item->payment_amount,
                    'iban' => $item->account_number ?: 'N/A',
                    'accountHolderName' => $item->bank?->bank_account_name ?: 'N/A',
                    // Keep old fields for backward compatibility
                    'employeeNumber' => $item->employee_number,
                    'bankName' => $item->bank?->bank_name,
                    'accountNumber' => $item->account_number,
                    'leaveTaken' => $item->leave_taken,
                    'unpaidLeave' => $item->unpaid_leave_taken,
                    'totalDaysPaid' => $item->total_days_paid,
                    'dailyRate' => $item->daily_rate,
                ];
            }),
        ];

        return Inertia::render('Payroll/PaymentBatchDetails', [
            'payrollPage' => 'payment-batches',
            'batch' => $batchData,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Export payment batch to Excel
     */
    public function exportPaymentBatch($id)
    {
        $batch = PaymentBatch::findOrFail($id);
        
        $fileName = 'Payment_Batch_' . $batch->batch_number . '_' . now()->format('Ymd') . '.xlsx';
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\PaymentBatchExport($id),
            $fileName
        );
    }

    /**
     * Finalize a payment batch (locks it)
     */
    public function finalizePaymentBatch($id)
    {
        $batch = PaymentBatch::findOrFail($id);

        if (!$batch->canFinalize()) {
            return back()->withErrors(['error' => 'This batch cannot be finalized.']);
        }

        $batch->update([
            'status' => 'finalized',
            'finalized_at' => now(),
            'finalized_by' => Auth::id(),
        ]);

        return back()->with('success', 'Payment batch finalized successfully. It is now locked and ready for processing.');
    }

    /**
     * Mark batch as processed
     */
    public function processPaymentBatch($id)
    {
        $batch = PaymentBatch::findOrFail($id);

        if (!$batch->canProcess()) {
            return back()->withErrors(['error' => 'This batch cannot be processed.']);
        }

        $batch->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Payment batch marked as processed.');
    }

    /**
     * Bulk process multiple payment batches
     */
    public function bulkProcessPaymentBatches(Request $request)
    {
        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'integer|exists:payment_batches,id',
        ]);

        try {
            DB::beginTransaction();

            $processedCount = 0;
            $failedCount = 0;

            foreach ($request->batch_ids as $batchId) {
                $batch = PaymentBatch::find($batchId);
                
                if ($batch && $batch->canProcess()) {
                    $batch->update([
                        'status' => 'processed',
                        'processed_at' => now(),
                        'processed_by' => Auth::id(),
                    ]);
                    $processedCount++;
                } else {
                    $failedCount++;
                }
            }

            DB::commit();

            if ($processedCount > 0 && $failedCount === 0) {
                return back()->with('success', "{$processedCount} batch(es) marked as processed.");
            } elseif ($processedCount > 0 && $failedCount > 0) {
                return back()->with('success', "{$processedCount} batch(es) marked as processed. {$failedCount} batch(es) could not be processed.");
            } else {
                return back()->withErrors(['error' => 'No batches could be processed. Make sure they are in Finalized status.']);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process batches: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete a draft payment batch
     */
    public function deletePaymentBatch($id)
    {
        $batch = PaymentBatch::findOrFail($id);

        if (!$batch->canEdit()) {
            return back()->withErrors(['error' => 'Only draft batches can be deleted.']);
        }

        $batch->delete();

        return redirect()->route('payroll.payment-batches')
            ->with('success', 'Payment batch deleted successfully.');
    }

    /**
     * Show bank files page
     */
    public function bankFiles()
    {
        // Payroll is organization-wide, no event filtering
        // Get all finalized batches for dropdown
        $finalizedBatches = PaymentBatch::finalized()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                return [
                    'id' => $batch->id,
                    'batchNumber' => $batch->batch_number,
                    'batchName' => $batch->batch_name,
                    'period' => $batch->period,
                ];
            });

        // Get generated bank files
        $bankFiles = BankFile::with(['batch', 'generator'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($file) {
                return [
                    'id' => $file->id,
                    'fileName' => $file->file_name,
                    'batchNumber' => $file->batch->batch_number,
                    'batchName' => $file->batch->batch_name,
                    'fileFormat' => strtoupper($file->file_format),
                    'recordCount' => $file->record_count,
                    'totalAmount' => $file->total_amount,
                    'fileSize' => $file->getFileSize(),
                    'generatedAt' => $file->created_at?->format('Y-m-d H:i'),
                    'generatedBy' => $file->generator?->name,
                    'exists' => $file->exists(),
                ];
            });
        
        return Inertia::render('Payroll/BankFiles', [
            'payrollPage' => 'bank-files',
            'bankFiles' => $bankFiles,
            'finalizedBatches' => $finalizedBatches,
            'can' => $this->getPayrollPermissions(),
            'pendingCounts' => $this->getPendingCounts(),
            'modules' => $this->getAccessibleModules(),
            'userRoles' => Auth::user()->roles->pluck('name')->toArray(),
        ]);
    }

    /**
     * Generate bank file from payment batch
     */
    public function generateBankFile(Request $request)
    {
        $request->validate([
            'payment_batch_id' => 'required|exists:payment_batches,id',
            'file_format' => 'required|in:csv,txt',
            'notes' => 'nullable|string',
        ]);

        $batch = PaymentBatch::with('items')->findOrFail($request->payment_batch_id);

        if (!$batch->isFinalized()) {
            return back()->withErrors(['error' => 'Only finalized batches can be exported to bank files.']);
        }

        try {
            // Create directory if it doesn't exist
            Storage::disk('local')->makeDirectory('bank_files');

            // Generate file name
            $timestamp = now()->format('YmdHis');
            $fileName = 'PAYROLL_' . $batch->batch_number . '_' . $timestamp . '.' . $request->file_format;
            $filePath = 'bank_files/' . $fileName;

            // Generate file content based on format
            $content = $this->generateBankFileContent($batch, $request->file_format);

            // Save file
            Storage::disk('local')->put($filePath, $content);

            // Create bank file record
            $bankFile = BankFile::create([
                'payment_batch_id' => $batch->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_format' => $request->file_format,
                'record_count' => $batch->items->count(),
                'total_amount' => $batch->total_amount,
                'generation_notes' => $request->notes,
                'generated_by' => Auth::id(),
            ]);

            return back()->with('success', 'Bank file generated successfully: ' . $fileName);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to generate bank file: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate bank file content based on format
     */
    private function generateBankFileContent(PaymentBatch $batch, string $format): string
    {
        if ($format === 'csv') {
            return $this->generateCSVContent($batch);
        } elseif ($format === 'txt') {
            return $this->generateTXTContent($batch);
        }

        throw new \Exception('Unsupported file format: ' . $format);
    }

    /**
     * Generate CSV format bank file
     */
    private function generateCSVContent(PaymentBatch $batch): string
    {
        $lines = [];
        
        // Header row
        $lines[] = '"Timesheet Period","Emp Number","Name","IBAN","SWIFT","Amount"';

        // Data rows
        foreach ($batch->items as $item) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s","%s"',
                $batch->period,
                $item->employee_number ?: 'N/A',
                $item->employee_name,
                $item->account_number ?: 'N/A',
                $item->bank?->swift_code ?: 'N/A',
                number_format($item->payment_amount, 2, '.', '')
            );
        }

        // Footer row with total
        $lines[] = sprintf(
            '"","","","","TOTAL","%s"',
            number_format($batch->total_amount, 2, '.', '')
        );

        return implode("\n", $lines);
    }

    /**
     * Generate TXT format bank file (pipe-delimited)
     */
    private function generateTXTContent(PaymentBatch $batch): string
    {
        $lines = [];
        
        // Header
        $lines[] = 'PAYMENT_BATCH|' . $batch->batch_number;
        $lines[] = 'PERIOD|' . $batch->period;
        $lines[] = 'RECORD_COUNT|' . $batch->items->count();
        $lines[] = 'TOTAL_AMOUNT|' . number_format($batch->total_amount, 2, '.', '');
        $lines[] = '';
        $lines[] = 'TIMESHEET_PERIOD|EMP_NUMBER|NAME|IBAN|SWIFT|AMOUNT';

        // Data rows
        foreach ($batch->items as $item) {
            $lines[] = sprintf(
                '%s|%s|%s|%s|%s|%s',
                $batch->period,
                $item->employee_number ?: 'N/A',
                $item->employee_name,
                $item->account_number ?: 'N/A',
                $item->bank?->swift_code ?: 'N/A',
                number_format($item->payment_amount, 2, '.', '')
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Download bank file
     */
    public function downloadBankFile($id)
    {
        $bankFile = BankFile::findOrFail($id);

        if (!Storage::disk('local')->exists($bankFile->file_path)) {
            return back()->withErrors(['error' => 'File not found on server. Path: ' . $bankFile->file_path]);
        }

        return Storage::disk('local')->download($bankFile->file_path, $bankFile->file_name);
    }

    /**
     * Delete bank file
     */
    public function destroyBankFile($id)
    {
        $bankFile = BankFile::findOrFail($id);

        try {
            // Delete physical file if it exists
            if (Storage::disk('local')->exists($bankFile->file_path)) {
                Storage::disk('local')->delete($bankFile->file_path);
            }

            // Delete database record
            $bankFile->delete();

            return back()->with('success', 'Bank file deleted successfully.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete bank file: ' . $e->getMessage()]);
        }
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
