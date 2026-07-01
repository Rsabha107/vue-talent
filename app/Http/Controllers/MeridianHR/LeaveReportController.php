<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaveReportController extends BaseHRController
{
    public function leaveBalances(Request $request)
    {
        $year        = (int) $request->query('year', date('Y'));
        $leaveTypeId = $request->filled('leave_type_id') ? (int) $request->query('leave_type_id') : null;

        // All active paid leave types for the filter dropdown
        $paidLeaveTypes = LeaveType::active()
            ->orderBy('title')
            ->get()
            ->filter(fn($lt) => $lt->isPaid())
            ->values();

        // Which leave type IDs to include in the report
        $targetIds = $leaveTypeId
            ? [$leaveTypeId]
            : $paidLeaveTypes->pluck('id')->toArray();

        $balances = EmployeeLeaveBalance::with(['employee', 'leaveType'])
            ->active()
            ->forYear($year)
            ->whereIn('leave_type_id', $targetIds)
            ->orderBy('employee_id')
            ->get()
            ->map(fn($b) => [
                'id'             => $b->id,
                'employeeId'     => $b->employee_id,
                'employeeName'   => $b->employee?->full_name,
                'employeeNumber' => $b->employee?->employee_number,
                'leaveTypeId'    => $b->leave_type_id,
                'leaveTypeName'  => $b->leaveType?->title,
                'allocatedDays'  => (float) $b->allocated_days,
                'usedDays'       => (float) $b->used_days,
                'pendingDays'    => (float) $b->pending_days,
                'availableDays'  => (float) $b->available_days,
                'year'           => $b->year,
                'periodStart'    => $b->period_start?->format('Y-m-d'),
                'periodEnd'      => $b->period_end?->format('Y-m-d'),
            ]);

        return Inertia::render('MeridianHR/LeaveBalancesReport', array_merge(
            $this->getCommonProps('leave-balances'),
            [
                'balances'          => $balances,
                'leaveTypes'        => $paidLeaveTypes->map(fn($lt) => ['id' => $lt->id, 'title' => $lt->title]),
                'selectedYear'      => $year,
                'selectedLeaveType' => $leaveTypeId,
            ]
        ));
    }

    /**
     * Recalculate leave balances for all active employees and all active leave types.
     */
    public function recalculateAll()
    {
        $employees  = Employee::where('archived', 'N')->get();
        $leaveTypes = LeaveType::active()->get();

        // Remove balances for employees missing either contract date
        $incompleteIds = $employees
            ->filter(fn($e) => !$e->contract_start_date || !$e->contract_end_date)
            ->pluck('id');

        if ($incompleteIds->isNotEmpty()) {
            EmployeeLeaveBalance::whereIn('employee_id', $incompleteIds)->delete();
        }

        // Recalculate for employees with both contract dates
        $completeEmployees = $employees->filter(
            fn($e) => $e->contract_start_date && $e->contract_end_date
        );

        foreach ($completeEmployees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                LeaveBalanceService::initializeLeaveBalance($leaveType, $employee);
            }
        }

        return redirect()->route('hr.leave-balances')
            ->with('success', 'Leave balances recalculated. Removed balances for ' . $incompleteIds->count() . ' employee(s) with missing contract dates.');
    }
}
