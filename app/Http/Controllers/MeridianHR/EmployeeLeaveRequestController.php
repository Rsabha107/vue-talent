<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeLeaveRequestController extends BaseHRController
{
    public function index()
    {
        $eventId = $this->getEffectiveEventIds(); // Support manager "All My Events"
        $hrRole = $this->getHRRole();
        $scope = request()->query('scope'); // 'team' for team view, null for personal view
        
        // Determine if we should show only personal leaves
        $showPersonalOnly = false;
        
        if (!in_array($hrRole, ['admin'])) {
            // For employees, always show only their own leaves
            if (!in_array($hrRole, ['manager'])) {
                $showPersonalOnly = true;
            }
            // For managers, show personal leaves unless scope=team
            elseif ($scope !== 'team') {
                $showPersonalOnly = true;
            }
        }
        
        // Get current employee record if needed
        $currentEmployee = null;
        if ($showPersonalOnly) {
            $currentEmployee = Employee::where('user_id', auth()->id())->first();
        }
        
        // Build query with optional event filtering
        $query = EmployeeLeaveRequest::with([
            'employee',
            'event',
            'user',
            'leaveType',
            'status',
            'performer'
        ])
            ->active()
            ->orderBy('created_at', 'desc');
        
        // Apply event scope (supports single ID, array of IDs, or null for all)
        $query->forEvent($eventId);
        
        // Filter by employee for personal view
        if ($showPersonalOnly) {
            if ($currentEmployee) {
                $query->where('employee_id', $currentEmployee->id);
            } else {
                // No employee record found - show empty results
                $query->whereRaw('1 = 0');
            }
        }
        
        $leaveRequests = $query->get()
            ->map(function ($request) {
                return [
                    'id'                    => $request->id,
                    'archived'              => $request->archived,
                    'employeeId'            => $request->employee_id,
                    'employeeName'          => $request->employee?->full_name,
                    'employeeNumber'        => $request->employee?->employee_number,
                    'eventId'               => $request->event_id,
                    'eventName'             => $request->event?->name,
                    'userId'                => $request->user_id,
                    'userName'              => $request->user?->name,
                    'leaveTypeId'           => $request->leave_type_id,
                    'leaveTypeTitle'        => $request->leaveType?->title,
                    'numberOfDays'          => $request->number_of_days,
                    'dateFrom'              => $request->date_from?->format('Y-m-d'),
                    'dateTo'                => $request->date_to?->format('Y-m-d'),
                    'reason'                => $request->reason,
                    'statusId'              => $request->status_id,
                    'statusTitle'           => $request->status?->title,
                    'statusColor'           => $request->status?->color,
                    'performerId'           => $request->performer_id,
                    'performerName'         => $request->performer?->name,
                    'additionalInformation' => $request->additional_information,
                    'createdAt'             => $request->created_at?->format('Y-m-d H:i:s'),
                    'updatedAt'             => $request->updated_at?->format('Y-m-d H:i:s'),
                ];
            });

        // Get employees filtered by event if selected
        // For personal view (managers/employees), only show current employee
        if ($showPersonalOnly && $currentEmployee) {
            $employees = collect([
                [
                    'id' => $currentEmployee->id,
                    'full_name' => $currentEmployee->full_name,
                    'employee_number' => $currentEmployee->employee_number,
                ]
            ]);
        } else {
            $employees = $eventId 
                ? $this->getEventEmployees()->orderBy('full_name')->get(['id', 'full_name', 'employee_number'])
                : Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_number']);
        }

        // Get leave balances (filtered by employee in personal view)
        $leaveBalancesQuery = \App\Models\EmployeeLeaveBalance::where('year', now()->year)
            ->where('active_flag', 1);
        
        // Handle event filtering - support single ID, array of IDs, or null
        if ($eventId !== null) {
            if (is_array($eventId)) {
                $leaveBalancesQuery->whereIn('event_id', $eventId);
            } else {
                $leaveBalancesQuery->where('event_id', $eventId);
            }
        }
        
        // Filter by employee in personal view
        if ($showPersonalOnly && $currentEmployee) {
            $leaveBalancesQuery->where('employee_id', $currentEmployee->id);
        }
        
        $leaveBalances = $leaveBalancesQuery
            ->with('leaveType')
            ->get()
            ->map(function ($balance) {
                return [
                    'employee_id'    => $balance->employee_id,
                    'leave_type_id'  => $balance->leave_type_id,
                    'allocated_days' => $balance->allocated_days,
                    'used_days'      => $balance->used_days,
                    'pending_days'   => $balance->pending_days,
                    'available_days' => $balance->available_days,
                ];
            });

        return Inertia::render('MeridianHR/LeaveRequest', array_merge($this->getCommonProps('leave-requests'), [
            'leaveRequests'   => $leaveRequests,
            'employees'       => $employees,
            'currentEmployee' => $currentEmployee ? [
                'id'              => $currentEmployee->id,
                'full_name'       => $currentEmployee->full_name,
                'employee_number' => $currentEmployee->employee_number,
            ] : null,
            'leaveTypes'     => LeaveType::active()->orderBy('title')->get(['id', 'title']),
            'statuses'       => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
            'leaveBalances'  => $leaveBalances,
            'isTeamView'     => $scope === 'team', // Indicates read-only team view for managers
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'            => 'required|integer|exists:employees_all,id',
            'leave_type_id'          => 'required|integer|exists:employee_leave_types,id',
            'number_of_days'         => 'required|integer|min:1',
            'date_from'              => 'required|date',
            'date_to'                => 'required|date|after_or_equal:date_from',
            'reason'                 => 'required|string|max:1000',
            'status_id'              => 'required|integer|exists:employee_leave_status,id',
            'additional_information' => 'nullable|string|max:4000',
        ]);

        // Determine event_id
        $eventId = $this->getSelectedEventId();
        
        // For employees creating their own leave request, ensure we have an event
        $hrRole = $this->getHRRole();
        if (!in_array($hrRole, ['admin', 'manager'])) {
            // If no event selected, try to get employee's active event
            if (!$eventId) {
                $employee = Employee::find($validated['employee_id']);
                $activeEvents = $employee->events()
                    ->wherePivot('is_active', 1)
                    ->get();
                
                if ($activeEvents->count() === 1) {
                    // Auto-select the only active event
                    $eventId = $activeEvents->first()->id;
                } elseif ($activeEvents->count() === 0) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['employee_id' => 'This employee is not assigned to any active event. Please contact your administrator.']);
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['employee_id' => 'Please select an event from the sidebar before creating a leave request.']);
                }
            }
        }

        // Check for overlapping leave dates
        $hasOverlap = $this->checkDateOverlap(
            $validated['employee_id'],
            $validated['date_from'],
            $validated['date_to'],
            $eventId
        );

        if ($hasOverlap) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_from' => 'This employee already has a leave request for overlapping dates.']);
        }

        $leaveRequest = EmployeeLeaveRequest::create([
            'archived'               => 'N',
            'employee_id'            => $validated['employee_id'],
            'event_id'               => $eventId,
            'user_id'                => Auth::id() ?? 0,
            'leave_type_id'          => $validated['leave_type_id'],
            'number_of_days'         => $validated['number_of_days'],
            'date_from'              => $validated['date_from'],
            'date_to'                => $validated['date_to'],
            'reason'                 => $validated['reason'],
            'status_id'              => $validated['status_id'],
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        // Recalculate leave balances after creating new request
        LeaveBalanceService::recalculateAfterLeaveRequest($leaveRequest);

        return redirect()->route('hr.leave-requests')->with('success', 'Leave request created successfully.');
    }

    public function update(Request $request, $id)
    {
        $leaveRequest = EmployeeLeaveRequest::findOrFail($id);

        $validated = $request->validate([
            'employee_id'            => 'required|integer|exists:employees_all,id',
            'leave_type_id'          => 'required|integer|exists:employee_leave_types,id',
            'number_of_days'         => 'required|integer|min:1',
            'date_from'              => 'required|date',
            'date_to'                => 'required|date|after_or_equal:date_from',
            'reason'                 => 'required|string|max:1000',
            'status_id'              => 'required|integer|exists:employee_leave_status,id',
            'performer_id'           => 'nullable|integer|exists:users,id',
            'additional_information' => 'nullable|string|max:4000',
        ]);

        // Check for overlapping leave dates (excluding current record)
        $hasOverlap = $this->checkDateOverlap(
            $validated['employee_id'],
            $validated['date_from'],
            $validated['date_to'],
            $leaveRequest->event_id,
            $id
        );

        if ($hasOverlap) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_from' => 'This employee already has a leave request for overlapping dates.']);
        }

        $leaveRequest->update([
            'employee_id'            => $validated['employee_id'],
            'leave_type_id'          => $validated['leave_type_id'],
            'number_of_days'         => $validated['number_of_days'],
            'date_from'              => $validated['date_from'],
            'date_to'                => $validated['date_to'],
            'reason'                 => $validated['reason'],
            'status_id'              => $validated['status_id'],
            'performer_id'           => $validated['performer_id'] ?? null,
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

        // Recalculate leave balances after status change (approved/rejected)
        LeaveBalanceService::recalculateAfterLeaveRequest($leaveRequest);

        return redirect()->route('hr.leave-requests')->with('success', 'Leave request updated successfully.');
    }

    public function destroy($id)
    {
        $leaveRequest = EmployeeLeaveRequest::findOrFail($id);
        $leaveRequest->update(['archived' => 'Y']);

        // Recalculate leave balances after archiving request
        LeaveBalanceService::recalculateAfterLeaveRequest($leaveRequest);

        return redirect()->route('hr.leave-requests')->with('success', 'Leave request archived successfully.');
    }

    /**
     * Check if leave dates overlap with existing leave requests
     * 
     * @param int $employeeId
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $eventId
     * @param int|null $excludeId ID of leave request to exclude from check (for updates)
     * @return bool
     */
    private function checkDateOverlap($employeeId, $dateFrom, $dateTo, $eventId = null, $excludeId = null)
    {
        $query = EmployeeLeaveRequest::where('employee_id', $employeeId)
            ->where('archived', 'N')
            ->whereNotNull('status_id')
            ->whereHas('status', function ($q) {
                // Only check against Pending or Approved leaves (not Rejected)
                $q->whereIn('title', ['Pending', 'Approved']);
            })
            ->where(function ($q) use ($dateFrom, $dateTo) {
                // Check for overlapping date ranges
                // Overlap occurs when: (start1 <= end2) AND (end1 >= start2)
                $q->where(function ($query) use ($dateFrom, $dateTo) {
                    $query->where('date_from', '<=', $dateTo)
                          ->where('date_to', '>=', $dateFrom);
                });
            });

        // Match event context
        if ($eventId) {
            $query->where('event_id', $eventId);
        } else {
            $query->whereNull('event_id');
        }

        // Exclude current record when updating
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
