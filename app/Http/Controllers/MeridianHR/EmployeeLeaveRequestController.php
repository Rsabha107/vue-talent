<?php

namespace App\Http\Controllers\MeridianHR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLeaveRequest;
use App\Models\EmployeeLeaveStatus;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EmployeeLeaveRequestController extends Controller
{
    public function index()
    {
        $leaveRequests = EmployeeLeaveRequest::with([
            'employee',
            'user',
            'leaveType',
            'status',
            'performer'
        ])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id'                    => $request->id,
                    'archived'              => $request->archived,
                    'employeeId'            => $request->employee_id,
                    'employeeName'          => $request->employee?->full_name,
                    'employeeNumber'        => $request->employee?->employee_number,
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

        return Inertia::render('MeridianHR/LeaveRequest', [
            'hrRole'         => $this->getHrRole(),
            'hrPage'         => 'leave-requests',
            'leaveRequests'  => $leaveRequests,
            'employees'      => Employee::orderBy('full_name')->get(['id', 'full_name', 'employee_number']),
            'leaveTypes'     => LeaveType::active()->orderBy('title')->get(['id', 'title']),
            'statuses'       => EmployeeLeaveStatus::active()->orderBy('title')->get(['id', 'title', 'color']),
        ]);
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

        EmployeeLeaveRequest::create([
            'archived'               => 'N',
            'employee_id'            => $validated['employee_id'],
            'user_id'                => Auth::id() ?? 0,
            'leave_type_id'          => $validated['leave_type_id'],
            'number_of_days'         => $validated['number_of_days'],
            'date_from'              => $validated['date_from'],
            'date_to'                => $validated['date_to'],
            'reason'                 => $validated['reason'],
            'status_id'              => $validated['status_id'],
            'additional_information' => $validated['additional_information'] ?? null,
        ]);

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

        return redirect()->route('hr.leave-requests')->with('success', 'Leave request updated successfully.');
    }

    public function destroy($id)
    {
        $leaveRequest = EmployeeLeaveRequest::findOrFail($id);
        $leaveRequest->update(['archived' => 'Y']);

        return redirect()->route('hr.leave-requests')->with('success', 'Leave request archived successfully.');
    }

    private function getHrRole()
    {
        return request()->query('role', 'admin');
    }
}
