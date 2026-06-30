<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeContractType;
use App\Models\EmployeeLeaveBalance;
use App\Models\Gender;
use App\Models\LeaveEligibility;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeaveTypeController extends BaseHRController
{
    public function index()
    {
        $leaveTypes = LeaveType::with('eligibilities')
            ->orderBy('title')
            ->get()
            ->map(function ($type) {
                $eligibilities = $type->eligibilities;
                return [
                    'id'                => $type->id,
                    'title'             => $type->title,
                    'activeFlag'        => $type->active_flag,
                    'accrualFrequency'  => $type->accrual_frequency,
                    'numberOfLeaves'    => $type->number_of_leaves,
                    'eligible'          => $type->eligible,
                    'createdAt'         => $type->created_at?->format('Y-m-d'),
                    'updatedAt'         => $type->updated_at?->format('Y-m-d'),
                    'eligibleContractTypes'  => $eligibilities->whereNotNull('contract_type_id')->pluck('contract_type_id')->unique()->values(),
                    'eligibleGenders'        => $eligibilities->whereNotNull('gender_id')->pluck('gender_id')->unique()->values(),
                    'eligibleDepartments'    => $eligibilities->whereNotNull('department_id')->pluck('department_id')->unique()->values(),
                    'eligibleDesignations'   => $eligibilities->whereNotNull('designation_id')->pluck('designation_id')->unique()->values(),
                ];
            });

        return Inertia::render('MeridianHR/LeaveType', array_merge($this->getCommonProps('leave-types'), [
            'leaveTypes'    => $leaveTypes,
            'contractTypes' => EmployeeContractType::orderBy('title')->get(['id', 'title']),
            'genders'       => Gender::orderBy('title')->get(['id', 'title']),
            'departments'   => Department::active()->orderBy('name')->get(['id', 'name']),
            'designations'  => Designation::active()->orderBy('name')->get(['id', 'name']),
        ]));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'             => 'required|string|max:100',
            'active_flag'       => 'required|integer|in:0,1',
            'accrual_frequency' => 'nullable|string|max:45',
            'number_of_leaves'  => 'nullable|integer|min:0',
            'eligible'          => 'nullable|boolean',
            'eligible_contract_types' => 'nullable|array',
            'eligible_genders'        => 'nullable|array',
            'eligible_departments'    => 'nullable|array',
            'eligible_designations'   => 'nullable|array',
        ]);

        $leaveType = LeaveType::create([
            'title'             => $validated['title'],
            'active_flag'       => $validated['active_flag'],
            'accrual_frequency' => $validated['accrual_frequency'] ?? null,
            'number_of_leaves'  => $validated['number_of_leaves'] ?? null,
            'eligible'          => $validated['eligible'] ?? true,
            'created_by'        => Auth::id(),
            'updated_by'        => Auth::id(),
        ]);

        $this->syncEligibilities($leaveType->id, $validated);

        if ($leaveType->active_flag) {
            LeaveBalanceService::initializeLeaveBalance($leaveType, null);
        }

        return redirect()->route('hr.leave-types')->with('success', 'Leave type created successfully.');
    }

    public function update(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);

        $validated = $request->validate([
            'title'             => 'required|string|max:100',
            'active_flag'       => 'required|integer|in:0,1',
            'accrual_frequency' => 'nullable|string|max:45',
            'number_of_leaves'  => 'nullable|integer|min:0',
            'eligible'          => 'nullable|boolean',
            'eligible_contract_types' => 'nullable|array',
            'eligible_genders'        => 'nullable|array',
            'eligible_departments'    => 'nullable|array',
            'eligible_designations'   => 'nullable|array',
        ]);

        $leaveType->update([
            'title'             => $validated['title'],
            'active_flag'       => $validated['active_flag'],
            'accrual_frequency' => $validated['accrual_frequency'] ?? null,
            'number_of_leaves'  => $validated['number_of_leaves'] ?? null,
            'eligible'          => $validated['eligible'] ?? true,
            'updated_by'        => Auth::id(),
        ]);

        $this->syncEligibilities($leaveType->id, $validated);

        if ($leaveType->active_flag) {
            // Active (or reactivated) — reinitialize balances for all employees
            LeaveBalanceService::initializeLeaveBalance($leaveType, null);
        } else {
            // Deactivated — hide all related balance records
            EmployeeLeaveBalance::where('leave_type_id', $leaveType->id)
                ->update(['active_flag' => 0, 'updated_by' => Auth::id()]);
        }

        return redirect()->route('hr.leave-types')->with('success', 'Leave type updated successfully.');
    }

    public function destroy($id)
    {
        $leaveType = LeaveType::findOrFail($id);
        $leaveType->update(['active_flag' => 0, 'updated_by' => Auth::id()]);

        // Deactivate all employee balance records for this leave type
        EmployeeLeaveBalance::where('leave_type_id', $leaveType->id)
            ->update(['active_flag' => 0, 'updated_by' => Auth::id()]);

        return redirect()->route('hr.leave-types')->with('success', 'Leave type deactivated successfully.');
    }

    private function syncEligibilities($leaveTypeId, array $data)
    {
        LeaveEligibility::where('leave_type_id', $leaveTypeId)->delete();

        $contractTypes = $data['eligible_contract_types'] ?? [];
        $genders       = $data['eligible_genders'] ?? [];
        $departments   = $data['eligible_departments'] ?? [];
        $designations  = $data['eligible_designations'] ?? [];

        foreach ($contractTypes as $ctId) {
            LeaveEligibility::create(['leave_type_id' => $leaveTypeId, 'contract_type_id' => $ctId]);
        }
        foreach ($genders as $gId) {
            LeaveEligibility::create(['leave_type_id' => $leaveTypeId, 'gender_id' => $gId]);
        }
        foreach ($departments as $dId) {
            LeaveEligibility::create(['leave_type_id' => $leaveTypeId, 'department_id' => $dId]);
        }
        foreach ($designations as $desId) {
            LeaveEligibility::create(['leave_type_id' => $leaveTypeId, 'designation_id' => $desId]);
        }
    }

}
