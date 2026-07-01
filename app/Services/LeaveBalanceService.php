<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\EmployeeLeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LeaveBalanceService
{
    /**
     * Initialize or update leave balances for employees.
     * Balances are now global — one per employee per leave type per year,
     * independent of event assignments.
     *
     * @param LeaveType|null $leaveType  If provided, only process this leave type
     * @param Employee|null  $employee   If provided, only process this employee
     */
    public static function initializeLeaveBalance($leaveType = null, $employee = null)
    {
        if ($leaveType && $employee) {
            static::calculateAndSaveBalance($employee, $leaveType);
        } elseif ($leaveType) {
            static::processLeaveTypeForAllEmployees($leaveType);
        } elseif ($employee) {
            static::processEmployeeForAllLeaveTypes($employee);
        }
    }

    private static function processLeaveTypeForAllEmployees(LeaveType $leaveType): void
    {
        Employee::where('archived', 'N')->get()
            ->each(fn($employee) => static::calculateAndSaveBalance($employee, $leaveType));
    }

    private static function processEmployeeForAllLeaveTypes(Employee $employee): void
    {
        LeaveType::where('active_flag', 1)->get()
            ->each(fn($leaveType) => static::calculateAndSaveBalance($employee, $leaveType));
    }

    private static function calculateAndSaveBalance(Employee $employee, LeaveType $leaveType): ?EmployeeLeaveBalance
    {
        if (!$employee->contract_start_date || !$employee->contract_end_date) {
            return null;
        }

        $year = now()->year;

        $contractStart = now()->parse($employee->contract_start_date);
        $contractEnd   = now()->parse($employee->contract_end_date);

        $periodStart = $contractStart;
        $periodEnd   = $contractEnd;

        $allocatedDays = static::calculateAllocatedDays($employee, $leaveType, $year);

        // Count approved and pending requests across ALL events
        $usedDays = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('archived', 'N')
            ->whereHas('status', fn($q) => $q->where('title', 'Approved'))
            ->sum('number_of_days') ?? 0;

        $pendingDays = EmployeeLeaveRequest::where('employee_id', $employee->id)
            ->where('leave_type_id', $leaveType->id)
            ->where('archived', 'N')
            ->whereHas('status', fn($q) => $q->where('title', 'Pending'))
            ->sum('number_of_days') ?? 0;

        $availableDays = $allocatedDays - $usedDays - $pendingDays;

        $balance = EmployeeLeaveBalance::updateOrCreate(
            [
                'employee_id'   => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year'          => $year,
            ],
            [
                'event_id'      => null,
                'allocated_days' => $allocatedDays,
                'used_days'      => $usedDays,
                'pending_days'   => $pendingDays,
                'available_days' => $availableDays,
                'period_start'   => $periodStart,
                'period_end'     => $periodEnd,
                'active_flag'    => 1,
                'updated_by'     => Auth::id() ?? 1,
            ]
        );

        Log::info('Leave balance updated', [
            'employee_id'   => $employee->id,
            'leave_type_id' => $leaveType->id,
            'allocated'     => $allocatedDays,
            'used'          => $usedDays,
            'pending'       => $pendingDays,
            'available'     => $availableDays,
        ]);

        return $balance;
    }

    /**
     * Calculate allocated days based on accrual frequency and employee contract dates.
     * For monthly accrual, prorates against the employee's master contract dates.
     */
    private static function calculateAllocatedDays(Employee $employee, LeaveType $leaveType, int $year): float
    {
        $baseAllocation    = $leaveType->number_of_leaves ?? 0;
        $accrualFrequency  = strtolower($leaveType->accrual_frequency ?? '');

        if (!$accrualFrequency || $accrualFrequency === 'yearly') {
            return $baseAllocation;
        }

        if ($accrualFrequency === 'monthly') {
            $yearStart = now()->setYear($year)->startOfYear()->startOfDay();
            $yearEnd   = now()->setYear($year)->endOfYear()->endOfDay();

            $contractStart = $employee->contract_start_date
                ? now()->parse($employee->contract_start_date)
                : null;
            $contractEnd   = $employee->contract_end_date
                ? now()->parse($employee->contract_end_date)
                : null;

            // No contract dates → employee covers the full period
            if (!$contractStart && !$contractEnd) {
                return round($baseAllocation * 12, 2);
            }

            $effectiveStart = $yearStart->copy();
            $effectiveEnd   = $yearEnd->copy();

            if ($contractStart) {
                if ($contractStart->year > $year) {
                    return 0; // Contract hasn't started yet this year
                }
                if ($contractStart->year === $year && $contractStart->greaterThan($yearStart)) {
                    $effectiveStart = $contractStart->copy()->startOfDay();
                }
            }

            if ($contractEnd) {
                if ($contractEnd->year < $year) {
                    return 0; // Contract ended before this year
                }
                if ($contractEnd->year === $year && $contractEnd->lessThan($yearEnd)) {
                    $effectiveEnd = $contractEnd->copy()->endOfDay();
                }
            }

            $monthsWorked       = $effectiveStart->floatDiffInMonths($effectiveEnd);
            $proratedAllocation = round($baseAllocation * $monthsWorked, 2);

            Log::info('Prorated monthly allocation calculated', [
                'employee_id'         => $employee->id,
                'leave_type'          => $leaveType->title,
                'effective_start'     => $effectiveStart->toDateString(),
                'effective_end'       => $effectiveEnd->toDateString(),
                'months_worked'       => $monthsWorked,
                'monthly_rate'        => $baseAllocation,
                'prorated_allocation' => $proratedAllocation,
            ]);

            return $proratedAllocation;
        }

        return $baseAllocation;
    }

    /**
     * Recalculate balances after a leave request status change.
     */
    public static function recalculateAfterLeaveRequest(EmployeeLeaveRequest $leaveRequest): void
    {
        $employee  = Employee::find($leaveRequest->employee_id);
        $leaveType = LeaveType::find($leaveRequest->leave_type_id);

        if ($employee && $leaveType) {
            static::calculateAndSaveBalance($employee, $leaveType);
        }
    }

    /**
     * Get leave balance summary for an employee.
     */
    public static function getEmployeeBalanceSummary(int $employeeId, $year = null)
    {
        $year = $year ?? now()->year;

        return EmployeeLeaveBalance::where('employee_id', $employeeId)
            ->where('year', $year)
            ->where('active_flag', 1)
            ->with('leaveType')
            ->get();
    }
}
