<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeLeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveBalanceService
{
    /**
     * Initialize or update leave balances for employees
     * 
     * @param LeaveType|null $leaveType If provided, only process this leave type for all employees
     * @param Employee|null $employee If provided, only process this employee for all leave types
     * @param int|null $eventId If provided, scope balances to this event
     * @return void
     */
    public static function initializeLeaveBalance($leaveType = null, $employee = null, $eventId = null)
    {
        if ($leaveType && $employee) {
            // Process specific employee for specific leave type
            static::calculateAndSaveBalance($employee, $leaveType, $eventId);
        } elseif ($leaveType) {
            // Process all employees for this leave type
            static::processLeaveTypeForAllEmployees($leaveType, $eventId);
        } elseif ($employee) {
            // Process this employee for all leave types
            static::processEmployeeForAllLeaveTypes($employee, $eventId);
        }
    }

    /**
     * Process a specific leave type for all eligible employees
     * 
     * @param LeaveType $leaveType
     * @param int|null $eventId
     * @return void
     */
    private static function processLeaveTypeForAllEmployees($leaveType, $eventId = null)
    {
        $query = Employee::where('archived', 'N');
        
        // If event specified, only process employees assigned to that event
        if ($eventId) {
            $query->whereHas('events', function ($q) use ($eventId) {
                $q->where('events.id', $eventId)
                  ->where('employee_events.is_active', 1);
            });
        }
        
        $employees = $query->get();

        foreach ($employees as $employee) {
            static::calculateAndSaveBalance($employee, $leaveType, $eventId);
        }
    }

    /**
     * Process a specific employee for all leave types
     * 
     * @param Employee $employee
     * @param int|null $eventId
     * @return void
     */
    private static function processEmployeeForAllLeaveTypes($employee, $eventId = null)
    {
        $leaveTypes = LeaveType::where('active_flag', 1)->get();

        foreach ($leaveTypes as $leaveType) {
            static::calculateAndSaveBalance($employee, $leaveType, $eventId);
        }
    }

    /**
     * Calculate and save leave balance for a specific employee and leave type
     * 
     * @param Employee $employee
     * @param LeaveType $leaveType
     * @param int|null $eventId
     * @return EmployeeLeaveBalance
     */
    private static function calculateAndSaveBalance($employee, $leaveType, $eventId = null)
    {
        $year = now()->year;
        $periodStart = now()->startOfYear();
        $periodEnd = now()->endOfYear();

        // Get event-specific assignment dates if eventId provided
        $contractStartOverride = null;
        $contractEndOverride = null;
        
        if ($eventId) {
            $eventAssignment = DB::table('employee_events')
                ->where('employee_id', $employee->id)
                ->where('event_id', $eventId)
                ->where('is_active', 1)
                ->first();
            
            if ($eventAssignment) {
                $contractStartOverride = $eventAssignment->assigned_at;
                $contractEndOverride = $eventAssignment->released_at;
            }
        }

        // Calculate allocated days based on accrual frequency and contract dates
        // For event-based employees, use event assignment dates (assigned_at, released_at)
        // For master employees, use contract dates (contract_start_date, contract_end_date)
        $allocatedDays = static::calculateAllocatedDays(
            $employee, 
            $leaveType, 
            $year, 
            $contractStartOverride, 
            $contractEndOverride
        );

        // Calculate used days (approved leaves)
        $usedQuery = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('archived', 'N')
            ->whereNotNull('status_id')
            ->whereHas('status', function ($q) {
                $q->where('title', 'Approved');
            });

        if ($eventId) {
            $usedQuery->where('event_id', $eventId);
        }

        $usedDays = $usedQuery->sum('number_of_days') ?? 0;

        // Calculate pending days (pending approval)
        $pendingQuery = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('archived', 'N')
            ->whereNotNull('status_id')
            ->whereHas('status', function ($q) {
                $q->where('title', 'Pending');
            });

        if ($eventId) {
            $pendingQuery->where('event_id', $eventId);
        }

        $pendingDays = $pendingQuery->sum('number_of_days') ?? 0;

        // Calculate available days
        $availableDays = $allocatedDays - $usedDays - $pendingDays;

        // Create or update balance
        $balance = EmployeeLeaveBalance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'event_id' => $eventId,
                'year' => $year,
            ],
            [
                'allocated_days' => $allocatedDays,
                'used_days' => $usedDays,
                'pending_days' => $pendingDays,
                'available_days' => $availableDays,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'active_flag' => 1,
                'updated_by' => Auth::id() ?? 1,
            ]
        );

        Log::info("Leave balance updated", [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'event_id' => $eventId,
            'allocated' => $allocatedDays,
            'used' => $usedDays,
            'pending' => $pendingDays,
            'available' => $availableDays,
        ]);

        return $balance;
    }

    /**
     * Calculate allocated days based on accrual frequency and contract dates
     * 
     * @param Employee $employee
     * @param LeaveType $leaveType
     * @param int $year
     * @param string|null $contractStartOverride Override contract start (for event-based: assigned_at)
     * @param string|null $contractEndOverride Override contract end (for event-based: released_at)
     * @return float
     */
    private static function calculateAllocatedDays(
        $employee, 
        $leaveType, 
        $year, 
        $contractStartOverride = null, 
        $contractEndOverride = null
    ) {
        $baseAllocation = $leaveType->number_of_leaves ?? 0;
        $accrualFrequency = $leaveType->accrual_frequency;

        // If no accrual frequency specified, return base allocation
        if (!$accrualFrequency || strtolower($accrualFrequency) === 'yearly') {
            return $baseAllocation;
        }

        // Handle monthly accrual
        if (strtolower($accrualFrequency) === 'monthly') {
            // Determine the calculation period
            $yearStart = now()->setYear($year)->startOfYear();
            $yearEnd = now()->setYear($year)->endOfYear();

            // Use override dates if provided (event assignment dates), otherwise use employee contract dates
            $contractStart = $contractStartOverride 
                ? now()->parse($contractStartOverride)
                : ($employee->contract_start_date ? now()->parse($employee->contract_start_date) : null);
            
            $contractEnd = $contractEndOverride 
                ? now()->parse($contractEndOverride)
                : ($employee->contract_end_date ? now()->parse($employee->contract_end_date) : null);

            // Determine effective start and end dates for this year
            $effectiveStart = $yearStart;
            $effectiveEnd = $yearEnd;

            if ($contractStart && $contractStart->year == $year && $contractStart->greaterThan($yearStart)) {
                $effectiveStart = $contractStart;
            } elseif ($contractStart && $contractStart->year > $year) {
                // Contract hasn't started in this year
                return 0;
            }

            if ($contractEnd && $contractEnd->year == $year && $contractEnd->lessThan($yearEnd)) {
                $effectiveEnd = $contractEnd;
            } elseif ($contractEnd && $contractEnd->year < $year) {
                // Contract ended before this year
                return 0;
            }

            // Calculate months worked (with decimal for partial months)
            $monthsWorked = $effectiveStart->floatDiffInMonths($effectiveEnd->endOfDay());

            // Monthly rate is base allocation (assuming base is monthly rate)
            $monthlyRate = $baseAllocation;

            // Calculate prorated allocation
            $proratedAllocation = round($monthlyRate * $monthsWorked, 2);

            Log::info("Prorated monthly allocation calculated", [
                'employee_id' => $employee->id,
                'leave_type' => $leaveType->title,
                'date_source' => $contractStartOverride ? 'event_assignment' : 'master_contract',
                'contract_start' => $contractStart?->toDateString(),
                'contract_end' => $contractEnd?->toDateString(),
                'effective_start' => $effectiveStart->toDateString(),
                'effective_end' => $effectiveEnd->toDateString(),
                'months_worked' => $monthsWorked,
                'monthly_rate' => $monthlyRate,
                'prorated_allocation' => $proratedAllocation,
            ]);

            return $proratedAllocation;
        }

        // Default: return base allocation
        return $baseAllocation;
    }

    /**
     * Recalculate balances after a leave request status change
     * 
     * @param EmployeeLeaveRequest $leaveRequest
     * @return void
     */
    public static function recalculateAfterLeaveRequest($leaveRequest)
    {
        $employee = Employee::find($leaveRequest->employee_id);
        $leaveType = LeaveType::find($leaveRequest->leave_type_id);

        if ($employee && $leaveType) {
            static::calculateAndSaveBalance($employee, $leaveType, $leaveRequest->event_id);
        }
    }

    /**
     * Get employee leave balance summary
     * 
     * @param int $employeeId
     * @param int|null $eventId
     * @param int|null $year
     * @return \Illuminate\Support\Collection
     */
    public static function getEmployeeBalanceSummary($employeeId, $eventId = null, $year = null)
    {
        $year = $year ?? now()->year;

        $query = EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('active_flag', 1)
            ->with('leaveType');

        if ($eventId) {
            $query->where('event_id', $eventId);
        }

        return $query->get();
    }
}
