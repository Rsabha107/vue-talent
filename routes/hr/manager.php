<?php

use App\Http\Controllers\MeridianHR\EmployeeController;
use App\Http\Controllers\MeridianHR\TimesheetController;
use Illuminate\Support\Facades\Route;

/**
 * Manager-level routes
 * Requires manager or admin role
 * Handles approval workflows
 */
Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {
    
    // Leave Approvals
    Route::get('/approvals/leave', [EmployeeController::class, 'approvalsLeave'])->name('approvals.leave');
    Route::post('/approvals/leave/approve', [EmployeeController::class, 'approveLeave'])->name('approvals.leave.approve');
    Route::post('/approvals/leave/reject', [EmployeeController::class, 'rejectLeave'])->name('approvals.leave.reject');
    
    // Timesheet Approvals
    Route::get('/approvals/time', [TimesheetController::class, 'approvalsTime'])->name('approvals.time');
    Route::post('/approvals/time/approve', [TimesheetController::class, 'approveTimesheet'])->name('approvals.time.approve');
    Route::post('/approvals/time/reject', [TimesheetController::class, 'rejectTimesheet'])->name('approvals.time.reject');
    
    // Payroll Approval (Admin/Finance only)
    Route::post('/payroll/time/approve', [TimesheetController::class, 'payrollApproveTimesheet'])->name('payroll.time.approve');
    Route::post('/payroll/time/reject', [TimesheetController::class, 'payrollRejectTimesheet'])->name('payroll.time.reject');
});
