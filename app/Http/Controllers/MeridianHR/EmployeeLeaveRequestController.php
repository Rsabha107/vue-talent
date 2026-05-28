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

    /**
     * My Leaves - Personal view for current user
     * Accessible by: everyone (employee, manager, admin)
     */
    public function myLeaves()
    {
        $eventId = $this->getSelectedEventId();
        $currentEmployee = Employee::where('user_id', auth()->id())->first();

        if (!$currentEmployee) {
            return Inertia::render('MeridianHR/MyLeaves', array_merge($this->getCommonProps('my-leaves'), [
                'leaveRequests'   => [],
                'employees'       => [],
                'currentEmployee' => null,
                'leaveTypes'      => LeaveType::active()->orderBy('title')->get(['id', 'title']),
                'statuses'        => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
                'leaveBalances'   => [],
            ]));
        }

        // Get leave requests for current employee only
        $leaveRequests = EmployeeLeaveRequest::with(['employee', 'event', 'user', 'leaveType', 'status', 'performer'])
            ->active()
            ->where('employee_id', $currentEmployee->id)
            ->forEvent($eventId)
            ->orderBy('created_at', 'desc')
            ->get()
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

        // Get leave balances for current employee
        $leaveBalancesQuery = \App\Models\EmployeeLeaveBalance::where('year', now()->year)
            ->where('active_flag', 1)
            ->where('employee_id', $currentEmployee->id);

        if ($eventId !== null) {
            if (is_array($eventId)) {
                $leaveBalancesQuery->whereIn('event_id', $eventId);
            } else {
                $leaveBalancesQuery->where('event_id', $eventId);
            }
        }

        $leaveBalances = $leaveBalancesQuery->with('leaveType')->get()->map(function ($balance) {
            return [
                'employee_id'    => $balance->employee_id,
                'leave_type_id'  => $balance->leave_type_id,
                'allocated_days' => $balance->allocated_days,
                'used_days'      => $balance->used_days,
                'pending_days'   => $balance->pending_days,
                'available_days' => $balance->available_days,
            ];
        });

        // Show only current employee in dropdown (read-only)
        $employees = collect([[
            'id'              => $currentEmployee->id,
            'full_name'       => $currentEmployee->full_name,
            'employee_number' => $currentEmployee->employee_number,
        ]]);

        return Inertia::render('MeridianHR/MyLeaves', array_merge($this->getCommonProps('my-leaves'), [
            'leaveRequests'   => $leaveRequests,
            'employees'       => $employees,
            'currentEmployee' => [
                'id'              => $currentEmployee->id,
                'full_name'       => $currentEmployee->full_name,
                'employee_number' => $currentEmployee->employee_number,
            ],
            'leaveTypes'     => LeaveType::active()->orderBy('title')->get(['id', 'title']),
            'statuses'       => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
            'leaveBalances'  => $leaveBalances,
        ]));
    }

    /**
     * Team Leaves - Manager read-only view of team leave requests
     * Accessible by: manager, admin
     */
    public function teamLeaves()
    {
        $eventId = $this->getEffectiveEventIds(); // Support manager "All My Events"

        // Get team leave requests (all employees in manager's events)
        $leaveRequests = EmployeeLeaveRequest::with(['employee', 'event', 'user', 'leaveType', 'status', 'performer'])
            ->active()
            ->forEvent($eventId)
            ->orderBy('created_at', 'desc')
            ->get()
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

        // Get all employees in manager's events
        $employees = $eventId 
            ? $this->getEventEmployees()->orderBy('full_name')->get(['id', 'full_name', 'employee_number'])
            : Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_number']);

        // Get leave balances for team employees
        $leaveBalancesQuery = \App\Models\EmployeeLeaveBalance::where('year', now()->year)
            ->where('active_flag', 1);

        if ($eventId !== null) {
            if (is_array($eventId)) {
                $leaveBalancesQuery->whereIn('event_id', $eventId);
            } else {
                $leaveBalancesQuery->where('event_id', $eventId);
            }
        }

        $leaveBalances = $leaveBalancesQuery->with('leaveType')->get()->map(function ($balance) {
            return [
                'employee_id'    => $balance->employee_id,
                'leave_type_id'  => $balance->leave_type_id,
                'allocated_days' => $balance->allocated_days,
                'used_days'      => $balance->used_days,
                'pending_days'   => $balance->pending_days,
                'available_days' => $balance->available_days,
            ];
        });

        return Inertia::render('MeridianHR/TeamLeaves', array_merge($this->getCommonProps('team-leaves'), [
            'leaveRequests'   => $leaveRequests,
            'employees'       => $employees,
            'currentEmployee' => null,
            'leaveTypes'      => LeaveType::active()->orderBy('title')->get(['id', 'title']),
            'statuses'        => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
            'leaveBalances'   => $leaveBalances,
        ]));
    }

    /**
     * All Leaves - Admin full-control view of all leave requests
     * Accessible by: admin
     */
    public function allLeaves()
    {
        $eventId = $this->getSelectedEventId();

        // Get all leave requests
        $leaveRequests = EmployeeLeaveRequest::with(['employee', 'event', 'user', 'leaveType', 'status', 'performer'])
            ->active()
            ->forEvent($eventId)
            ->orderBy('created_at', 'desc')
            ->get()
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

        // Get all employees
        $employees = $eventId 
            ? $this->getEventEmployees()->orderBy('full_name')->get(['id', 'full_name', 'employee_number'])
            : Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_number']);

        // Get all leave balances
        $leaveBalancesQuery = \App\Models\EmployeeLeaveBalance::where('year', now()->year)
            ->where('active_flag', 1);

        if ($eventId !== null) {
            if (is_array($eventId)) {
                $leaveBalancesQuery->whereIn('event_id', $eventId);
            } else {
                $leaveBalancesQuery->where('event_id', $eventId);
            }
        }

        $leaveBalances = $leaveBalancesQuery->with('leaveType')->get()->map(function ($balance) {
            return [
                'employee_id'    => $balance->employee_id,
                'leave_type_id'  => $balance->leave_type_id,
                'allocated_days' => $balance->allocated_days,
                'used_days'      => $balance->used_days,
                'pending_days'   => $balance->pending_days,
                'available_days' => $balance->available_days,
            ];
        });

        return Inertia::render('MeridianHR/AllLeaves', array_merge($this->getCommonProps('all-leaves'), [
            'leaveRequests'   => $leaveRequests,
            'employees'       => $employees,
            'currentEmployee' => null,
            'leaveTypes'      => LeaveType::active()->orderBy('title')->get(['id', 'title']),
            'statuses'        => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
            'leaveBalances'   => $leaveBalances,
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
        
        // Validate leave dates against event assignment period
        if ($eventId) {
            $employee = Employee::find($validated['employee_id']);
            $eventPivot = $employee->events()->where('events.id', $eventId)->first();
            
            if ($eventPivot) {
                $assignedAt = $eventPivot->pivot->assigned_at;
                $releasedAt = $eventPivot->pivot->released_at;
                
                // Check if leave dates fall within assignment period
                if ($assignedAt && $validated['date_from'] < $assignedAt) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['date_from' => 'Leave request cannot start before the employee assignment date (' . \Carbon\Carbon::parse($assignedAt)->format('d M Y') . ').']);
                }
                
                if ($releasedAt && $validated['date_to'] > $releasedAt) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['date_to' => 'Leave request cannot extend beyond the employee release date (' . \Carbon\Carbon::parse($releasedAt)->format('d M Y') . ').']);
                }
            }
        }

        // Check for pending/submitted timesheets
        [$hasPendingTimesheets, $timesheetMessage] = $this->checkPendingTimesheets(
            $validated['employee_id'],
            $validated['date_from'],
            $validated['date_to'],
            $eventId
        );

        if ($hasPendingTimesheets) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_from' => $timesheetMessage]);
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
        
        // Validate leave dates against event assignment period
        if ($leaveRequest->event_id) {
            $employee = Employee::find($validated['employee_id']);
            $eventPivot = $employee->events()->where('events.id', $leaveRequest->event_id)->first();
            
            if ($eventPivot) {
                $assignedAt = $eventPivot->pivot->assigned_at;
                $releasedAt = $eventPivot->pivot->released_at;
                
                // Check if leave dates fall within assignment period
                if ($assignedAt && $validated['date_from'] < $assignedAt) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['date_from' => 'Leave request cannot start before the employee assignment date (' . \Carbon\Carbon::parse($assignedAt)->format('d M Y') . ').']);
                }
                
                if ($releasedAt && $validated['date_to'] > $releasedAt) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['date_to' => 'Leave request cannot extend beyond the employee release date (' . \Carbon\Carbon::parse($releasedAt)->format('d M Y') . ').']);
                }
            }
        }

        // Check for pending/submitted timesheets
        [$hasPendingTimesheets, $timesheetMessage] = $this->checkPendingTimesheets(
            $validated['employee_id'],
            $validated['date_from'],
            $validated['date_to'],
            $leaveRequest->event_id
        );

        if ($hasPendingTimesheets) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['date_from' => $timesheetMessage]);
        }

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

    /**
     * Check if employee has pending/submitted timesheets for the leave period
     * 
     * @param int $employeeId
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $eventId
     * @return array [bool hasConflict, string|null message]
     */
    private function checkPendingTimesheets($employeeId, $dateFrom, $dateTo, $eventId = null)
    {
        // Get all month/year combinations in the leave date range
        $start = \Carbon\Carbon::parse($dateFrom);
        $end = \Carbon\Carbon::parse($dateTo);
        
        $monthsToCheck = [];
        $current = $start->copy();
        
        while ($current->lessThanOrEqualTo($end)) {
            $monthsToCheck[] = [
                'month' => $current->month,
                'year' => $current->year,
            ];
            $current->addMonth();
        }
        
        // Build query for conflicting timesheets
        $conflictingTimesheets = \App\Models\EmployeeTimesheet::where('employee_id', $employeeId)
            ->where('archived', 'N')
            ->where(function($query) use ($monthsToCheck) {
                foreach ($monthsToCheck as $period) {
                    $query->orWhere(function($q) use ($period) {
                        $q->where('month_id', $period['month'])
                          ->where('year', $period['year']);
                    });
                }
            })
            ->whereHas('status', function($q) {
                // Check for Pending, Submitted, or Pending Payroll statuses
                $q->whereIn('title', ['Pending', 'Submitted', 'Pending Payroll']);
            });
        
        // Match event context
        if ($eventId) {
            $conflictingTimesheets->where('event_id', $eventId);
        }
        
        $timesheets = $conflictingTimesheets->with('status')->get();
        
        if ($timesheets->isEmpty()) {
            return [false, null];
        }
        
        // Build detailed error message
        $count = $timesheets->count();
        $periods = $timesheets->map(function($ts) {
            return $ts->timesheet_period;
        })->unique()->join(', ');
        
        $message = "Cannot create leave request. Employee has {$count} pending/submitted timesheet(s) for: {$periods}. Please wait until these timesheets are approved or rejected before requesting leave for this period.";
        
        return [true, $message];
    }
}
