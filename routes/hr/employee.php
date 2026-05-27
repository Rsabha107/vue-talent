<?php

use App\Http\Controllers\MeridianHR\DocumentController;
use App\Http\Controllers\MeridianHR\EmployeeController;
use App\Http\Controllers\MeridianHR\TimesheetController;
use Illuminate\Support\Facades\Route;

/**
 * Employee-facing routes
 * Organized by access level using Spatie permissions
 */

// =============================================================================
// BASIC EMPLOYEE ROUTES - Available to all authenticated employees
// =============================================================================
// Accessible by: employee-basic, employee-full, manager, admin
// Features: Dashboard, Profile, Leave, Timesheet, Emergency Contacts

Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {
    
    // Dashboard & Profile (Everyone)
    Route::get('/dashboard', [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    
    // Leave Management (Employee's own - Everyone)
    Route::get('/leave', [EmployeeController::class, 'leave'])->name('leave');
    Route::post('/leave', [EmployeeController::class, 'storeLeave'])->name('leave.store');
    
    // Timesheet Management (Employee's own - Everyone)
    Route::get('/timesheet', [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::post('/timesheet/submit', [TimesheetController::class, 'submitTimesheet'])->name('timesheet.submit');
    Route::post('/timesheet/day', [TimesheetController::class, 'saveTimesheetDay'])->name('timesheet.day');
    Route::post('/timesheet/start', [TimesheetController::class, 'startTimesheet'])->name('timesheet.start');
    
    // My Timesheets - View all timesheets for current employee (Everyone)
    Route::get('/my-timesheets', [TimesheetController::class, 'myTimesheets'])->name('my-timesheets');
    Route::post('/my-timesheets/store', [TimesheetController::class, 'timesheetTalentStore'])->name('my-timesheets.store');
    Route::delete('/my-timesheets/{id}', [TimesheetController::class, 'timesheetTalentDestroy'])->name('my-timesheets.destroy');
    
    // Emergency Contacts (Everyone)
    Route::get('/emergency', [EmployeeController::class, 'emergency'])->name('emergency');
    Route::post('/emergency', [EmployeeController::class, 'storeEmergencyContact'])->name('emergency.store');
    Route::put('/emergency/{id}', [EmployeeController::class, 'updateEmergencyContact'])->name('emergency.update');
    Route::delete('/emergency/{id}', [EmployeeController::class, 'destroyEmergencyContact'])->name('emergency.destroy');
});

// =============================================================================
// EXTENDED EMPLOYEE ROUTES - Require specific permissions
// =============================================================================
// Accessible by: employee-full, manager, admin (with permissions)

// Addresses - Requires 'view-addresses' permission
Route::middleware(['auth', 'permission:view-addresses'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/addresses', [EmployeeController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [EmployeeController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/addresses/{id}', [EmployeeController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{id}', [EmployeeController::class, 'destroyAddress'])->name('addresses.destroy');
});

// Documents - Requires 'view-documents' permission (optional per employee)
Route::middleware(['auth', 'permission:view-documents'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('/documents/{id}/view', [DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{id}/download', [DocumentController::class, 'download'])->name('documents.download');
});

// Payslips - Requires 'view-payslips' permission
Route::middleware(['auth', 'permission:view-payslips'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/payslips', [EmployeeController::class, 'payslips'])->name('payslips');
});
