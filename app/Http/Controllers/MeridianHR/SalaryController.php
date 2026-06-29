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
use Maatwebsite\Excel\Facades\Excel;

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

    /**
     * Download salary import template
     */
    public function downloadTemplate()
    {
        return Excel::download(
            new \App\Exports\SalaryTemplateExport(),
            'salary_import_template.xlsx'
        );
    }

    /**
     * Import salaries from Excel file
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // 10MB max
        ]);

        try {
            $treatDuplicatesAsError = $request->input('treat_duplicates_as_error', '0') === '1';
            $import = new \App\Imports\SalaryImport($treatDuplicatesAsError);
            Excel::import($import, $request->file('file'));

            $stats = [
                'total' => 0, // Will be calculated at the end
                'success' => $import->getProcessedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
                'failed' => 0, // Will be calculated below
            ];

            $failures = $import->failures();
            $customErrors = $import->getCustomErrors();
            
            $errorMessages = [];
            $failedRows = [];
            
            // Process custom errors (employee not found, duplicates)
            foreach ($customErrors as $error) {
                $errorMsg = implode(', ', $error['errors']);
                $errorMessages[] = $errorMsg;
                
                // Store for export
                $failedRows[] = [
                    'row' => 'N/A',
                    'errors' => $error['errors'],
                    'values' => $error['row_data'],
                ];
            }
            
            // Group validation failures by row number
            if (count($failures) > 0) {
                $groupedFailures = [];
                
                foreach ($failures as $failure) {
                    $rowNumber = $failure->row();
                    
                    if (!isset($groupedFailures[$rowNumber])) {
                        $groupedFailures[$rowNumber] = [
                            'row' => $rowNumber,
                            'errors' => [],
                            'values' => $failure->values(),
                        ];
                    }
                    
                    // Merge all errors for this row
                    $groupedFailures[$rowNumber]['errors'] = array_merge(
                        $groupedFailures[$rowNumber]['errors'],
                        $failure->errors()
                    );
                }
                
                // Convert grouped failures to array and build error messages
                $groupedFailedRows = array_values($groupedFailures);
                
                foreach ($groupedFailedRows as $failedRow) {
                    $errorMsg = "Row {$failedRow['row']}: " . implode(', ', $failedRow['errors']);
                    $errorMessages[] = $errorMsg;
                    $failedRows[] = $failedRow;
                }
            }

            $hasFailures = count($errorMessages) > 0;
            $hasExportableFailures = count($failedRows) > 0;
            
            // Update failed count
            $stats['failed'] = count($errorMessages);
            $stats['total'] = $stats['success'] + $stats['skipped'] + $stats['failed'];

            // Store failed rows in session for export
            if ($hasExportableFailures) {
                session(['failed_salary_import_rows' => $failedRows]);
            }

            $message = $stats['success'] > 0
                ? "{$stats['success']} salary record(s) imported successfully"
                : "No salary records were imported";

            if ($hasFailures) {
                $message .= " ({$stats['failed']} failed validation)";
            }

            return response()->json([
                'success' => $stats['success'] > 0 || !$hasFailures,
                'message' => $message,
                'stats' => $stats,
                'errors' => $errorMessages,
                'hasFailures' => $hasFailures,
                'hasExportableFailures' => $hasExportableFailures,
            ]);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errorMessages = [];
            $failedRows = [];

            // Group validation failures by row number
            $groupedFailures = [];
            
            foreach ($failures as $failure) {
                $rowNumber = $failure->row();
                
                if (!isset($groupedFailures[$rowNumber])) {
                    $groupedFailures[$rowNumber] = [
                        'row' => $rowNumber,
                        'errors' => [],
                        'values' => $failure->values(),
                    ];
                }
                
                // Merge all errors for this row
                $groupedFailures[$rowNumber]['errors'] = array_merge(
                    $groupedFailures[$rowNumber]['errors'],
                    $failure->errors()
                );
            }
            
            // Convert grouped failures to array and build error messages
            $failedRows = array_values($groupedFailures);
            
            foreach ($failedRows as $failedRow) {
                $errorMsg = "Row {$failedRow['row']}: " . implode(', ', $failedRow['errors']);
                $errorMessages[] = $errorMsg;
            }

            // Store failed rows in session for export
            if (count($failedRows) > 0) {
                session(['failed_salary_import_rows' => $failedRows]);
            }

            $stats = [
                'total' => count($failures),
                'success' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => count($failures),
            ];

            return response()->json([
                'success' => false,
                'message' => 'Import failed due to validation errors',
                'stats' => $stats,
                'errors' => $errorMessages,
                'hasFailures' => true,
                'hasExportableFailures' => count($failedRows) > 0,
            ], 422);
        } catch (\Exception $e) {
            Log::error('SalaryController::import error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'stats' => [
                    'total' => 0,
                    'success' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'failed' => 1,
                ],
                'errors' => [$e->getMessage()],
                'hasFailures' => true,
                'hasExportableFailures' => false,
            ], 500);
        }
    }

    /**
     * Export failed salary import rows
     */
    public function exportFailedRows()
    {
        $failedRows = session('failed_salary_import_rows', []);
        
        if (empty($failedRows)) {
            return back()->with('error', 'No failed rows to export.');
        }

        // Clear the session after export
        session()->forget('failed_salary_import_rows');

        return Excel::download(
            new \App\Exports\FailedSalariesExport($failedRows),
            'failed_salaries_' . date('Y-m-d_His') . '.xlsx'
        );
    }
}

