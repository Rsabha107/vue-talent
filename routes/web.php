<?php

use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Ems\EventController;
use App\Http\Controllers\EventSessionController;
use App\Http\Controllers\MeridianHR\BankController;
use App\Http\Controllers\MeridianHR\EmployeeController;
use App\Http\Controllers\MeridianHR\EmployeeLeaveRequestController;
use App\Http\Controllers\MeridianHR\EventController as MeridianEventController;
use App\Http\Controllers\MeridianHR\EventTeamTemplateController;
use App\Http\Controllers\MeridianHR\LeaveTypeController;
use App\Http\Controllers\MeridianHR\SalaryController;
use App\Http\Controllers\MeridianHR\TimesheetController;
use App\Http\Controllers\MeridianHR\VenueController as MeridianVenueController;
use App\Http\Controllers\Ems\FunctionalAreaController;
use App\Http\Controllers\Ems\VenueController;
use App\Http\Controllers\GlobalStatusController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolesPermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserExportController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('MyAuth/Login', 'MyAuth/Login')->name('mylogin');
Route::inertia('MyAuth/Register', 'MyAuth/Register')->name('myregister');
Route::inertia('MyAuth/ForgotPassword', 'MyAuth/ForgotPassword')->name('myforgotpassword');

Route::middleware('otp.pending')->group(function () {
    Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
});

Route::controller(MicrosoftController::class)->group(function () {
    Route::get('auth/microsoft/redirect', 'redirectToMicrosoft')->name('auth.microsoft');
    Route::get('auth/microsoft/callback', 'handleMicrosoftCallback');
});


Route::get('password/confirmed', function () {
    return Inertia::render('MyAuth/Confirmation', [
        'icon'          => 'bx bx-check-circle',
        'iconColor'     => 'text-success',
        'title'         => 'Password Changed!',
        'message'       => session('status'),
        'buttonText'    => 'Sign In',
        'buttonHref'    => route('mylogin'),
        'buttonVariant' => 'primary',
    ]);
})->name('password.confirmed');

// check email route
// Route::get('/send-mail', [SendMailController::class, 'index']);

// Route::get('/dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/', function () {
        return redirect()->route('hr.dashboard');
    })->name('home');

    // Event Session Management
    Route::post('/event/select', [EventSessionController::class, 'setEvent'])->name('event.select');
    Route::post('/event/clear', [EventSessionController::class, 'clearEvent'])->name('event.clear');

    Route::get('/users/export', [UserExportController::class, 'export'])->name('users.export');
    Route::get('/statuses', [GlobalStatusController::class, 'getStatuses'])->name('global.statuses.get');

    // Route::post('forget-password', [PasswordResetLinkController::class, 'submitForgetPasswordForm'])->name('forgot.password.post');

    Route::controller(PermissionController::class)->group(function () {
        Route::get('/permissions', 'index')->name('permissions.index');
        Route::get('/api/permissions', 'data')->name('permissions.data');
        Route::post('/permissions', 'store')->name('permissions.store');
        Route::get('/permissions/{permission}', 'show')->name('permissions.show');
        Route::put('/permissions/{permission}', 'update')->name('permissions.update');
        Route::delete('/permissions/{permission}', 'destroy')->name('permissions.destroy');
    });

    Route::controller(EventController::class)->group(function () {
        Route::get('/events', 'index')->name('events.index');
        Route::get('/api/events', 'data')->name('events.data');
        Route::post('/events', 'store')->name('events.store');
        Route::get('/events/{event}', 'show')->name('events.show');
        Route::match(['put', 'post'], '/events/{event}', 'update')->name('events.update');
        Route::delete('/events/{event}', 'destroy')->name('events.destroy');
    });

    Route::controller(VenueController::class)->group(function () {
        Route::get('/venues', 'index')->name('venues.index');
        Route::get('/api/venues', 'data')->name('venues.data');
        Route::get('/api/venues/all', 'all')->name('venues.all');
        Route::post('/venues', 'store')->name('venues.store');
        Route::get('/venues/{venue}', 'show')->name('venues.show');
        Route::match(['put', 'post'], '/venues/{venue}', 'update')->name('venues.update');
        Route::delete('/venues/{venue}', 'destroy')->name('venues.destroy');
    });

    Route::controller(FunctionalAreaController::class)->group(function () {
        Route::get('/functional-areas', 'index')->name('functional-areas.index');
        Route::get('/api/functional-areas', 'data')->name('functional-areas.data');
        Route::get('/api/functional-areas/all', 'all')->name('functional-areas.all');
        Route::post('/functional-areas', 'store')->name('functional-areas.store');
        Route::get('/functional-areas/{functionalArea}', 'show')->name('functional-areas.show');
        Route::match(['put', 'post'], '/functional-areas/{functionalArea}', 'update')->name('functional-areas.update');
        Route::delete('/functional-areas/{functionalArea}', 'destroy')->name('functional-areas.destroy');
    });

    Route::controller(RolesPermissionController::class)->group(function () {
        Route::get('/roles-permissions', 'index')->name('roles-permissions.index');
        Route::get('/api/roles-permissions', 'data')->name('roles-permissions.data');
        // Flat lists for the assign modal (must be before /{role} routes)
        Route::get('/api/roles-permissions/all-permissions', 'allPermissions')->name('roles-permissions.all-permissions');
        Route::get('/api/roles-permissions/all-roles', 'allRoles')->name('roles-permissions.all-roles');
        // CRUD
        Route::put('/api/roles-permissions/{role}', 'syncPermissions')->name('roles-permissions.sync');
        Route::delete('/api/roles-permissions/{role}', 'destroy')->name('roles-permissions.destroy');
    });

    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index')->name('users.index'); // Inertia page
        Route::get('/api/users', 'data')->name('users.data'); // Bootstrap Table endpoint
        // Route::post('/users/table', 'table')->name('users.table');
        // Route::match(['get','post'], '/users', 'index')->name('users.index');
        // Route::get('/api/users', 'data')->name('users.data'); // AJAX data endpoint

        // Route::get('/users', 'index')->name('users.index');
        Route::get('/api/users/{user}/roles', 'roles')->name('users.roles');
        Route::post('/users', 'store')->name('users.store');
        Route::put('/users/{user}', 'update')->name('users.update');
        Route::delete('/users/{user}', 'destroy')->name('users.destroy');
    });

    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::get('/api/roles', 'data')->name('roles.data');
        Route::post('/roles', 'store')->name('roles.store');
        Route::put('/roles/{role}', 'update')->name('roles.update');
        Route::delete('/roles/{role}', 'destroy')->name('roles.destroy');
    });

    Route::inertia('mypage', 'Mypage')->name('mypage');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('hr')->name('hr.')->middleware('auth')->group(function () {
    Route::get('/dashboard',         [EmployeeController::class, 'dashboard'])->name('dashboard');
    Route::get('/leave',             [EmployeeController::class, 'leave'])->name('leave');
    Route::post('/leave',            [EmployeeController::class, 'storeLeave'])->name('leave.store');
    
    // Timesheet Routes
    Route::get('/timesheet',         [TimesheetController::class, 'timesheet'])->name('timesheet');
    Route::post('/timesheet/submit', [TimesheetController::class, 'submitTimesheet'])->name('timesheet.submit');
    Route::post('/timesheet/day',    [TimesheetController::class, 'saveTimesheetDay'])->name('timesheet.day');
    Route::post('/timesheet/start',  [TimesheetController::class, 'startTimesheet'])->name('timesheet.start');
    Route::get('/timesheet-talent',                [TimesheetController::class, 'timesheetTalent'])->name('timesheet-talent');
    Route::post('/timesheet-talent/store',         [TimesheetController::class, 'timesheetTalentStore'])->name('timesheet-talent.store');
    Route::post('/timesheet-talent/status',        [TimesheetController::class, 'timesheetTalentStatus'])->name('timesheet-talent.status');
    Route::post('/timesheet-talent/entries/store', [TimesheetController::class, 'timesheetTalentEntriesStore'])->name('timesheet-talent.entries.store');
    Route::delete('/timesheet-talent/{id}',        [TimesheetController::class, 'timesheetTalentDestroy'])->name('timesheet-talent.destroy');
    
    Route::get('/documents',         [EmployeeController::class, 'documents'])->name('documents');
    Route::get('/payslips',          [EmployeeController::class, 'payslips'])->name('payslips');
    Route::get('/employee',          [EmployeeController::class, 'employee'])->name('employee');
    Route::get('/master-employee',   [EmployeeController::class, 'masterEmployee'])->name('master-employee');
    Route::post('/employee',         [EmployeeController::class, 'store'])->name('employee.store');
    Route::get('/employee/{id}/edit',[EmployeeController::class, 'edit'])->name('employee.edit');
    Route::put('/employee/{id}',     [EmployeeController::class, 'update'])->name('employee.update');
    Route::delete('/employee/{id}',  [EmployeeController::class, 'destroy'])->name('employee.destroy');
    Route::get('/employee/template/download', [EmployeeController::class, 'downloadTemplate'])->name('employee.template');
    Route::post('/employee/import',  [EmployeeController::class, 'import'])->name('employee.import');
    Route::get('/employee/export-failed', [EmployeeController::class, 'exportFailedRows'])->name('employee.export.failed');
    Route::post('/employee/assign-to-event', [EmployeeController::class, 'assignToEvent'])->name('employee.assign-to-event');
    Route::get('/profile',           [EmployeeController::class, 'profile'])->name('profile');
    Route::get('/approvals/leave',   [EmployeeController::class, 'approvalsLeave'])->name('approvals.leave');
    Route::post('/approvals/leave/approve', [EmployeeController::class, 'approveLeave'])->name('approvals.leave.approve');
    Route::post('/approvals/leave/reject', [EmployeeController::class, 'rejectLeave'])->name('approvals.leave.reject');
    Route::get('/approvals/time',    [TimesheetController::class, 'approvalsTime'])->name('approvals.time');
    Route::post('/approvals/time/approve', [TimesheetController::class, 'approveTimesheet'])->name('approvals.time.approve');
    Route::post('/approvals/time/reject', [TimesheetController::class, 'rejectTimesheet'])->name('approvals.time.reject');
    
    // Personal Data Management
    Route::get('/addresses',         [EmployeeController::class, 'addresses'])->name('addresses');
    Route::post('/addresses',        [EmployeeController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/addresses/{id}',    [EmployeeController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{id}', [EmployeeController::class, 'destroyAddress'])->name('addresses.destroy');
    
    // Bank Management
    Route::get('/banks',             [BankController::class, 'index'])->name('banks');
    Route::post('/banks',            [BankController::class, 'store'])->name('banks.store');
    Route::put('/banks/{id}',        [BankController::class, 'update'])->name('banks.update');
    Route::delete('/banks/{id}',     [BankController::class, 'destroy'])->name('banks.destroy');
    
    // Salary Management
    Route::get('/salary',            [SalaryController::class, 'index'])->name('salary');
    Route::post('/salary',           [SalaryController::class, 'store'])->name('salary.store');
    Route::put('/salary/{id}',       [SalaryController::class, 'update'])->name('salary.update');
    Route::delete('/salary/{id}',    [SalaryController::class, 'destroy'])->name('salary.destroy');
    
    Route::get('/emergency',         [EmployeeController::class, 'emergency'])->name('emergency');
    Route::post('/emergency',        [EmployeeController::class, 'storeEmergencyContact'])->name('emergency.store');
    Route::put('/emergency/{id}',    [EmployeeController::class, 'updateEmergencyContact'])->name('emergency.update');
    Route::delete('/emergency/{id}', [EmployeeController::class, 'destroyEmergencyContact'])->name('emergency.destroy');
    
    // Leave Type Management (Settings)
    Route::get('/leave-types',       [LeaveTypeController::class, 'index'])->name('leave-types');
    Route::post('/leave-types',      [LeaveTypeController::class, 'store'])->name('leave-types.store');
    Route::put('/leave-types/{id}',  [LeaveTypeController::class, 'update'])->name('leave-types.update');
    Route::delete('/leave-types/{id}',[LeaveTypeController::class, 'destroy'])->name('leave-types.destroy');
    
    // Leave Request Management
    Route::get('/leave-requests',       [EmployeeLeaveRequestController::class, 'index'])->name('leave-requests');
    Route::post('/leave-requests',      [EmployeeLeaveRequestController::class, 'store'])->name('leave-requests.store');
    Route::put('/leave-requests/{id}',  [EmployeeLeaveRequestController::class, 'update'])->name('leave-requests.update');
    Route::delete('/leave-requests/{id}',[EmployeeLeaveRequestController::class, 'destroy'])->name('leave-requests.destroy');
    
    // Event Management (Settings)
    Route::get('/events',       [MeridianEventController::class, 'index'])->name('events');
    Route::get('/events/{event}', [MeridianEventController::class, 'show'])->name('events.show');
    Route::post('/events',      [MeridianEventController::class, 'store'])->name('events.store');
    Route::put('/events/{event}',  [MeridianEventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}',[MeridianEventController::class, 'destroy'])->name('events.destroy');
    
    // Event-Employee Assignment
    Route::get('/events/{event}/employees', [MeridianEventController::class, 'showEmployees'])->name('events.employees');
    Route::post('/events/{event}/assign-employees', [MeridianEventController::class, 'assignEmployees'])->name('events.assign-employees');
    Route::delete('/events/{event}/employees/{employee}', [MeridianEventController::class, 'removeEmployee'])->name('events.remove-employee');
    Route::get('/events/{event}/unassigned-employees', [MeridianEventController::class, 'unassignedEmployees'])->name('events.unassigned-employees');
    Route::get('/events/{event}/source-events', [MeridianEventController::class, 'listSourceEvents'])->name('events.source-events');
    Route::post('/events/{event}/copy-team', [MeridianEventController::class, 'copyTeamFrom'])->name('events.copy-team');
    Route::post('/events/{event}/import-csv', [MeridianEventController::class, 'importFromCsv'])->name('events.import-csv');
    
    // Event Team Templates (Settings)
    Route::get('/event-templates', [EventTeamTemplateController::class, 'index'])->name('event-templates');
    Route::get('/event-templates/list', [EventTeamTemplateController::class, 'list'])->name('event-templates.list');
    Route::post('/event-templates', [EventTeamTemplateController::class, 'store'])->name('event-templates.store');
    Route::put('/event-templates/{template}', [EventTeamTemplateController::class, 'update'])->name('event-templates.update');
    Route::delete('/event-templates/{template}', [EventTeamTemplateController::class, 'destroy'])->name('event-templates.destroy');
    Route::post('/events/{event}/apply-template', [EventTeamTemplateController::class, 'applyToEvent'])->name('events.apply-template');
    
    // Venue Management (Settings)
    Route::get('/venues',       [MeridianVenueController::class, 'index'])->name('venues');
    Route::post('/venues',      [MeridianVenueController::class, 'store'])->name('venues.store');
    Route::put('/venues/{venue}',  [MeridianVenueController::class, 'update'])->name('venues.update');
    Route::delete('/venues/{venue}',[MeridianVenueController::class, 'destroy'])->name('venues.destroy');
});

require __DIR__ . '/auth.php';
