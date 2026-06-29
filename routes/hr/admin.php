<?php

use App\Http\Controllers\MeridianHR\BankController;
use App\Http\Controllers\MeridianHR\DocumentCategoryController;
use App\Http\Controllers\MeridianHR\DocumentController;
use App\Http\Controllers\MeridianHR\EmployeeController;
use App\Http\Controllers\MeridianHR\EmployeeLeaveRequestController;
use App\Http\Controllers\MeridianHR\EventController as MeridianEventController;
use App\Http\Controllers\MeridianHR\EventTeamTemplateController;
use App\Http\Controllers\MeridianHR\LeaveTypeController;
use App\Http\Controllers\MeridianHR\LookupTablesController;
use App\Http\Controllers\MeridianHR\SalaryController;
use App\Http\Controllers\MeridianHR\SettingsController;
use App\Http\Controllers\MeridianHR\SetupController;
use App\Http\Controllers\MeridianHR\TimesheetController;
use App\Http\Controllers\MeridianHR\RolesPermissionsController;
use App\Http\Controllers\MeridianHR\UserManagementController;
use App\Http\Controllers\MeridianHR\VenueController as MeridianVenueController;
use Illuminate\Support\Facades\Route;

/**
 * Admin-level routes
 * Requires admin role
 * Handles system configuration and employee management
 */
Route::middleware(['auth'])->prefix('hr')->name('hr.')->group(function () {
    
    // Employee Management (consolidated - handles both All Events and event-filtered views)
    Route::get('/employee', [EmployeeController::class, 'employee'])->name('employee');
    // Legacy route: master-employee now redirects to employee (All Events view)
    // Route::get('/master-employee', [EmployeeController::class, 'masterEmployee'])->name('master-employee');
    Route::post('/employee', [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee/{id}/edit', [EmployeeController::class, 'edit'])->name('employee.edit');
    Route::put('/employee/{id}', [EmployeeController::class, 'update'])->name('employee.update');
    Route::delete('/employee/{id}', [EmployeeController::class, 'destroy'])->name('employee.destroy');
    
    // Employee Import/Export
    Route::get('/employee/template/download', [EmployeeController::class, 'downloadTemplate'])->name('employee.template');
    Route::get('/employee/template/base/download', [EmployeeController::class, 'downloadBaseTemplate'])->name('employee.base-template');
    Route::get('/employee/template/event-assignment/download', [EmployeeController::class, 'downloadEventAssignmentTemplate'])->name('employee.event-assignment-template');
    Route::post('/employee/import', [EmployeeController::class, 'import'])->name('employee.import');
    Route::post('/employee/import/base', [EmployeeController::class, 'importBase'])->name('employee.import.base');
    Route::post('/employee/import/event-assignment', [EmployeeController::class, 'importEventAssignment'])->name('employee.import.event-assignment');
    Route::get('/employee/export-failed', [EmployeeController::class, 'exportFailedRows'])->name('employee.export.failed');
    Route::post('/employee/export-selected', [EmployeeController::class, 'exportSelected'])->name('employee.export.selected');
    
    // Event-Employee Assignment
    Route::post('/employee/assign-to-event', [EmployeeController::class, 'assignToEvent'])->name('employee.assign-to-event');
    Route::post('/employee/unassign-from-event', [EmployeeController::class, 'unassignFromEvent'])->name('employee.unassign-from-event');
    
    // Bank Management (Admin manages all employees' banks)
    Route::get('/banks', [BankController::class, 'index'])->name('banks');
    Route::post('/banks', [BankController::class, 'store'])->name('banks.store');
    Route::put('/banks/{id}', [BankController::class, 'update'])->name('banks.update');
    Route::delete('/banks/{id}', [BankController::class, 'destroy'])->name('banks.destroy');
    
    // Salary Management (Admin manages all employees' salaries)
    Route::get('/salary', [SalaryController::class, 'index'])->name('salary');
    Route::post('/salary', [SalaryController::class, 'store'])->name('salary.store');
    Route::put('/salary/{id}', [SalaryController::class, 'update'])->name('salary.update');
    Route::delete('/salary/{id}', [SalaryController::class, 'destroy'])->name('salary.destroy');
    
    // Salary Import/Export
    Route::get('/salary/template/download', [SalaryController::class, 'downloadTemplate'])->name('salary.template');
    Route::post('/salary/import', [SalaryController::class, 'import'])->name('salary.import');
    Route::get('/salary/export-failed', [SalaryController::class, 'exportFailedRows'])->name('salary.export.failed');
    
    // Document Management (Admin can upload/delete)
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    // Document Categories Management
    Route::get('/document-categories', [DocumentCategoryController::class, 'index'])->name('document-categories');
    Route::post('/document-categories', [DocumentCategoryController::class, 'store'])->name('document-categories.store');
    Route::put('/document-categories/{id}', [DocumentCategoryController::class, 'update'])->name('document-categories.update');
    Route::delete('/document-categories/{id}', [DocumentCategoryController::class, 'destroy'])->name('document-categories.destroy');
    
    // Leave Type Management (Settings)
    Route::get('/leave-types', [LeaveTypeController::class, 'index'])->name('leave-types');
    Route::post('/leave-types', [LeaveTypeController::class, 'store'])->name('leave-types.store');
    Route::put('/leave-types/{id}', [LeaveTypeController::class, 'update'])->name('leave-types.update');
    Route::delete('/leave-types/{id}', [LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
    
    // Application Settings Management
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/settings/check-pending-jobs', [SettingsController::class, 'checkPendingJobs'])->name('settings.check-pending-jobs');
    Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');
    Route::put('/settings/{id}', [SettingsController::class, 'update'])->name('settings.update');
    Route::delete('/settings/{id}', [SettingsController::class, 'destroy'])->name('settings.destroy');
    
    // Setup Page (Events & Venues Management)
    Route::get('/setup', [SetupController::class, 'index'])->name('setup');
    
    // Lookup Tables (Generic Settings Management)
    Route::get('/lookup/{type}', [LookupTablesController::class, 'index'])->name('lookup');
    Route::post('/lookup/{type}', [LookupTablesController::class, 'store'])->name('lookup.store');
    Route::put('/lookup/{type}/{id}', [LookupTablesController::class, 'update'])->name('lookup.update');
    Route::delete('/lookup/{type}/{id}', [LookupTablesController::class, 'destroy'])->name('lookup.destroy');
    
    // Leave Request Management (Admin View)
    Route::get('/leave-requests', [EmployeeLeaveRequestController::class, 'index'])->name('leave-requests'); // Legacy route
    Route::get('/all-leaves', [EmployeeLeaveRequestController::class, 'allLeaves'])->name('all-leaves'); // New route
    Route::post('/leave-requests', [EmployeeLeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::put('/leave-requests/{id}', [EmployeeLeaveRequestController::class, 'update'])->name('leave-requests.update');
    Route::delete('/leave-requests/{id}', [EmployeeLeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');
    
    // Timesheet Talent (Admin View)
    Route::get('/timesheet-talent', [TimesheetController::class, 'timesheetTalent'])->name('timesheet-talent'); // Legacy route
    Route::get('/all-timesheets', [TimesheetController::class, 'allTimesheetsView'])->name('all-timesheets'); // New route
    Route::post('/timesheet-talent/store', [TimesheetController::class, 'timesheetTalentStore'])->name('timesheet-talent.store');
    Route::post('/timesheet-talent/status', [TimesheetController::class, 'timesheetTalentStatus'])->name('timesheet-talent.status');
    Route::post('/timesheet-talent/entries/store', [TimesheetController::class, 'timesheetTalentEntriesStore'])->name('timesheet-talent.entries.store');
    Route::delete('/timesheet-talent/{id}', [TimesheetController::class, 'timesheetTalentDestroy'])->name('timesheet-talent.destroy');
    
    // Event Management
    Route::get('/events', [MeridianEventController::class, 'index'])->name('events');
    Route::get('/events/{event}', [MeridianEventController::class, 'show'])->name('events.show');
    Route::post('/events', [MeridianEventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}', [MeridianEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [MeridianEventController::class, 'destroy'])->name('events.destroy');
    
    // Event-Employee Assignment
    Route::get('/events/{event}/employees', [MeridianEventController::class, 'showEmployees'])->name('events.employees');
    Route::post('/events/{event}/assign-employees', [MeridianEventController::class, 'assignEmployees'])->name('events.assign-employees');
    Route::delete('/events/{event}/employees/{employee}', [MeridianEventController::class, 'removeEmployee'])->name('events.remove-employee');
    Route::get('/events/{event}/unassigned-employees', [MeridianEventController::class, 'unassignedEmployees'])->name('events.unassigned-employees');
    Route::get('/events/{event}/source-events', [MeridianEventController::class, 'listSourceEvents'])->name('events.source-events');
    Route::post('/events/{event}/copy-team', [MeridianEventController::class, 'copyTeamFrom'])->name('events.copy-team');
    Route::post('/events/{event}/import-csv', [MeridianEventController::class, 'importFromCsv'])->name('events.import-csv');
    
    // Event Team Templates
    Route::get('/event-templates', [EventTeamTemplateController::class, 'index'])->name('event-templates');
    Route::get('/event-templates/list', [EventTeamTemplateController::class, 'list'])->name('event-templates.list');
    Route::post('/event-templates', [EventTeamTemplateController::class, 'store'])->name('event-templates.store');
    Route::put('/event-templates/{template}', [EventTeamTemplateController::class, 'update'])->name('event-templates.update');
    Route::delete('/event-templates/{template}', [EventTeamTemplateController::class, 'destroy'])->name('event-templates.destroy');
    Route::post('/events/{event}/apply-template', [EventTeamTemplateController::class, 'applyToEvent'])->name('events.apply-template');
    
    // Venue Management
    Route::get('/venues', [MeridianVenueController::class, 'index'])->name('venues');
    Route::post('/venues', [MeridianVenueController::class, 'store'])->name('venues.store');
    Route::put('/venues/{venue}', [MeridianVenueController::class, 'update'])->name('venues.update');
    Route::delete('/venues/{venue}', [MeridianVenueController::class, 'destroy'])->name('venues.destroy');
    
    // User Management (Security/Privacy)
    Route::get('/manager-users', [UserManagementController::class, 'index'])->name('manager-users');
    Route::post('/manager-users', [UserManagementController::class, 'store'])->name('manager-users.store');
    Route::put('/manager-users/{id}', [UserManagementController::class, 'update'])->name('manager-users.update');
    Route::delete('/manager-users/{id}', [UserManagementController::class, 'destroy'])->name('manager-users.destroy');

    // Roles & Permissions Management (Security/Privacy)
    Route::get('/roles-permissions', [RolesPermissionsController::class, 'index'])->name('roles-permissions');
    Route::post('/permissions', [RolesPermissionsController::class, 'storePermission'])->name('permissions.store');
    Route::put('/permissions/{id}', [RolesPermissionsController::class, 'updatePermission'])->name('permissions.update');
    Route::delete('/permissions/{id}', [RolesPermissionsController::class, 'destroyPermission'])->name('permissions.destroy');
    Route::post('/roles', [RolesPermissionsController::class, 'storeRole'])->name('roles.store');
    Route::put('/roles/{id}', [RolesPermissionsController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{id}', [RolesPermissionsController::class, 'destroyRole'])->name('roles.destroy');
    Route::put('/roles/{id}/permissions', [RolesPermissionsController::class, 'syncRolePermissions'])->name('roles.permissions');
});
