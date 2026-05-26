<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SalaryController extends BaseHRController
{
    /**
     * Display employee salaries
     */
    public function index()
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        // Get salaries based on role
        if ($role === 'employee') {
            $salaries = EmployeeSalary::where('employee_id', $me['id'])
                ->active()
                ->orderBy('effective_start_date', 'desc')
                ->get();
        } else {
            $salariesQuery = EmployeeSalary::with(['employee'])
                ->active()
                ->orderBy('effective_start_date', 'desc');
            
            // Filter by event if selected
            if ($eventId) {
                $salariesQuery->whereHas('employee.events', function ($q) use ($eventId) {
                    $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
                });
            }
            
            $salaries = $salariesQuery->get();
        }

        // Map salaries to frontend format
        $formattedSalaries = $salaries->map(function($salary) use ($role) {
            return [
                'id' => $salary->id,
                'employeeId' => $salary->employee_id,
                'employeeName' => $salary->employee->full_name ?? 'Unknown',
                'employeeNumber' => $salary->employee->employee_number ?? 'N/A',
                'netSalary' => $salary->net_salary,
                'payrollCycleId' => $salary->payroll_cycle_id,
                'effectiveStartDate' => $salary->effective_start_date?->format('Y-m-d'),
                'effectiveEndDate' => $salary->effective_end_date?->format('Y-m-d'),
                'isActive' => $salary->isActive(),
                'createdAt' => $salary->created_at->toISOString(),
            ];
        });

        $employees = [];
        if ($role !== 'employee') {
            $empQuery = Employee::select('id', 'employee_number', 'full_name')
                ->orderBy('full_name');
            
            // Filter by event if selected
            if ($eventId) {
                $empQuery->whereHas('events', function ($q) use ($eventId) {
                    $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
                });
            }
            
            $employees = $empQuery->get()->toArray();
        }

        return Inertia::render('MeridianHR/Salary', array_merge(
            $this->getCommonProps('salary'),
            [
                'salaries' => $formattedSalaries,
                'employees' => $employees,
            ]
        ));
    }

    /**
     * Store a new salary record
     */
    public function store(Request $request)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        // Only admin/manager can add salary records
        if ($role === 'employee') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You do not have permission to add salary records.'
            ]);
        }

        $validated = $request->validate([
            'employee_id' => 'required|integer|min:1|max:214748367|exists:employees_all,id',
            'net_salary' => 'required|numeric|min:0',
            'payroll_cycle_id' => 'nullable|integer',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ]);

        // Parse the effective start date
        $effective_start_date = Carbon::parse($validated['effective_start_date']);
        
        // Check for overlapping date ranges with CLOSED historical records only
        $overlapping = EmployeeSalary::where('employee_id', $validated['employee_id'])
            ->where('archived', 'N')
            ->where('effective_end_date', '!=', '9999-12-31')
            ->where('effective_start_date', '<=', $effective_start_date->toDateString())
            ->where('effective_end_date', '>=', $effective_start_date->toDateString())
            ->exists();

        if ($overlapping) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'effective_start_date' => 'This date conflicts with an existing historical salary record. Please choose a different date.'
            ]);
        }

        DB::beginTransaction();

        try {
            // Calculate the closing date for existing records (one day before new start date)
            $effective_end_date = $effective_start_date->copy()->subDay()->toDateString();

            // Close any existing open-ended salary records for this employee (only non-archived)
            EmployeeSalary::where('employee_id', $validated['employee_id'])
                ->where('archived', 'N')
                ->where('effective_end_date', '9999-12-31')
                ->update([
                    'effective_end_date' => $effective_end_date,
                ]);

            // Create the new salary record
            $validated['creator_id'] = Auth::id();
            $validated['archived'] = 'N';
            $validated['active_flag'] = 1;
            $validated['effective_start_date'] = $effective_start_date->toDateString();
            $validated['effective_end_date'] = '9999-12-31'; // Set default open-ended date
            
            if ($request->effective_end_date) {
                $validated['effective_end_date'] = Carbon::parse($request->effective_end_date)->toDateString();
            }

            EmployeeSalary::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Salary record added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SalaryController::store error: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'net_salary' => 'An error occurred while creating the salary record. Please try again.'
            ]);
        }
    }

    /**
     * Update an existing salary record
     */
    public function update(Request $request, $id)
    {
        $role = $this->getHRRole();

        // Only admin/manager can update salary records
        if ($role === 'employee') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You do not have permission to update salary records.'
            ]);
        }

        $salary = EmployeeSalary::findOrFail($id);

        $validated = $request->validate([
            'net_salary' => 'required|numeric|min:0',
            'payroll_cycle_id' => 'nullable|integer',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ]);

        DB::beginTransaction();

        try {
            // Parse and set effective dates
            if ($request->effective_start_date) {
                $validated['effective_start_date'] = Carbon::parse($request->effective_start_date)->toDateString();
            }
            
            if ($request->effective_end_date) {
                $validated['effective_end_date'] = Carbon::parse($request->effective_end_date)->toDateString();
            } else {
                $validated['effective_end_date'] = '9999-12-31';
            }

            $salary->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Salary record updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SalaryController::update error: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'net_salary' => 'An error occurred while updating the salary record. Please try again.'
            ]);
        }
    }

    /**
     * Archive a salary record
     */
    public function destroy($id)
    {
        $role = $this->getHRRole();

        // Only admin/manager can delete salary records
        if ($role === 'employee') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You do not have permission to delete salary records.'
            ]);
        }

        $salary = EmployeeSalary::findOrFail($id);
        $salary->update(['archived' => 'Y', 'active_flag' => 0]);

        return redirect()->back()->with('success', 'Salary record archived successfully.');
    }
}
