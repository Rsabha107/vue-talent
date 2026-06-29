<?php

namespace App\Http\Controllers\MeridianHR;

use App\Models\Country;
use App\Models\Employee;
use App\Models\EmployeeBank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BankController extends BaseHRController
{
    /**
     * Display employee bank accounts
     */
    public function index()
    {
        $me = $this->me();
        $role = $this->getHRRole();
        $eventId = $this->getSelectedEventId();

        // Get banks based on role
        if ($role === 'employee') {
            $banks = EmployeeBank::where('employee_id', $me['id'])
                ->active()
                ->orderBy('effective_start_date', 'desc')
                ->get();
        } else {
            $banksQuery = EmployeeBank::with(['employee'])
                ->active()
                ->orderBy('effective_start_date', 'desc');
            
            // Filter by event if selected
            if ($eventId) {
                $banksQuery->whereHas('employee.events', function ($q) use ($eventId) {
                    $q->where('events.id', $eventId)->where('employee_events.is_active', 1);
                });
            }
            
            $banks = $banksQuery->get();
        }

        // Map banks to frontend format
        $formattedBanks = $banks->map(function($bank) use ($role) {
            return [
                'id' => $bank->id,
                'employeeId' => $bank->employee_id,
                'employeeName' => $bank->employee->full_name ?? 'Unknown',
                'employeeNumber' => $bank->employee->employee_number ?? 'N/A',
                'bankBranchName' => $bank->bank_branch_name,
                'iban' => $bank->iban,
                'swiftCode' => $bank->swift_code,
                'effectiveStartDate' => $bank->effective_start_date?->format('Y-m-d'),
                'effectiveEndDate' => $bank->effective_end_date?->format('Y-m-d'),
                'isActive' => $bank->isActive(),
                'createdAt' => $bank->created_at->toISOString(),
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

        return Inertia::render('MeridianHR/Banks', array_merge(
            $this->getCommonProps('banks'),
            [
                'banks' => $formattedBanks,
                'employees' => $employees,
            ]
        ));
    }

    /**
     * Store a new bank account
     */
    public function store(Request $request)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $validated = $request->validate([
            'employee_id' => 'required|integer|min:1|max:214748367|exists:employees_all,id',
            'bank_branch_name' => 'required',
            'iban' => [
                'required',
                'max:29',
                Rule::unique('employee_banks')->where(function ($query) {
                    return $query->where('archived', 'N');
                })
            ],
            'swift_code' => 'required|min:8|max:8',
            'effective_start_date' => 'required|date',
            'effective_end_date' => 'nullable|date|after_or_equal:effective_start_date',
        ]);

        // Employees can only add their own bank accounts
        if ($role === 'employee' && $validated['employee_id'] != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only add your own bank accounts.'
            ]);
        }

        // Parse the effective start date
        $effective_start_date = Carbon::parse($validated['effective_start_date']);
        
        // Check for overlapping date ranges with CLOSED historical records only
        // We allow replacing open-ended records (9999-12-31) as that's the point of date tracking
        $overlapping = EmployeeBank::where('employee_id', $validated['employee_id'])
            ->where('archived', 'N')
            ->where('effective_end_date', '!=', '9999-12-31') // Exclude open-ended records
            ->where('effective_start_date', '<=', $effective_start_date->toDateString())
            ->where('effective_end_date', '>=', $effective_start_date->toDateString())
            ->exists();

        if ($overlapping) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'effective_start_date' => 'This date conflicts with an existing historical bank account record. Please choose a different date.'
            ]);
        }

        DB::beginTransaction();

        try {
            // Calculate the closing date for existing records (one day before new start date)
            $effective_end_date = $effective_start_date->copy()->subDay()->toDateString();

            // Close any existing open-ended bank records for this employee (only non-archived)
            EmployeeBank::where('employee_id', $validated['employee_id'])
                ->where('archived', 'N')
                ->where('effective_end_date', '9999-12-31')
                ->update([
                    'effective_end_date' => $effective_end_date,
                ]);

            // Create the new bank record
            $validated['user_id'] = Auth::id();
            $validated['archived'] = 'N';
            $validated['effective_start_date'] = $effective_start_date->toDateString();
            $validated['effective_end_date'] = '9999-12-31'; // Set default open-ended date
            
            if ($request->effective_end_date) {
                $validated['effective_end_date'] = Carbon::parse($request->effective_end_date)->toDateString();
            }

            EmployeeBank::create($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Bank account added successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BankController::store error: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'iban' => 'An error occurred while creating the bank account. Please try again.'
            ]);
        }
    }

    /**
     * Update an existing bank account
     */
    public function update(Request $request, $id)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $bank = EmployeeBank::findOrFail($id);

        // Employees can only update their own bank accounts
        if ($role === 'employee' && $bank->employee_id != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only update your own bank accounts.'
            ]);
        }

        $validated = $request->validate([
            'bank_branch_name' => 'required',
            'iban' => [
                'required',
                'max:29',
                Rule::unique('employee_banks')->ignore($id)->where(function ($query) {
                    return $query->where('archived', 'N');
                })
            ],
            'swift_code' => 'required|min:8|max:8',
            'effective_start_date' => 'nullable|date',
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

            $bank->update($validated);

            DB::commit();
            return redirect()->back()->with('success', 'Bank account updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('BankController::update error: ' . $e->getMessage());
            throw \Illuminate\Validation\ValidationException::withMessages([
                'iban' => 'An error occurred while updating the bank account. Please try again.'
            ]);
        }
    }

    /**
     * Archive a bank account
     */
    public function destroy($id)
    {
        $me = $this->me();
        $role = $this->getHRRole();

        $bank = EmployeeBank::findOrFail($id);

        // Employees can only delete their own bank accounts
        if ($role === 'employee' && $bank->employee_id != $me['id']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'employee_id' => 'You can only delete your own bank accounts.'
            ]);
        }

        $bank->update(['archived' => 'Y']);

        return redirect()->back()->with('success', 'Bank account archived successfully.');
    }
}
