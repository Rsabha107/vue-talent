<?php

use App\Http\Controllers\Auth\MicrosoftController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\EventSessionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Application routes are organized into modular files:
| - routes/hr/employee.php - Employee-facing routes
| - routes/hr/manager.php  - Manager approval workflows
| - routes/hr/admin.php    - Admin management & settings
| - routes/legacy/ems.php  - Legacy event management system
| - routes/auth.php        - Authentication routes
|
*/

// ============================================================================
// GUEST ROUTES - Public pages (login, register, password reset)
// ============================================================================

// Legacy custom auth routes (use routes/auth.php instead for standard login)
// Route::inertia('MyAuth/Login', 'MyAuth/Login')->name('mylogin');
// Route::inertia('MyAuth/Register', 'MyAuth/Register')->name('myregister');
// Route::inertia('MyAuth/ForgotPassword', 'MyAuth/ForgotPassword')->name('myforgotpassword');

Route::get('password/confirmed', function () {
    return Inertia::render('MyAuth/Confirmation', [
        'icon'          => 'bx bx-check-circle',
        'iconColor'     => 'text-success',
        'title'         => 'Password Changed!',
        'message'       => session('status'),
        'buttonText'    => 'Sign In',
        'buttonHref'    => route('login'),
        'buttonVariant' => 'primary',
    ]);
})->name('password.confirmed');

// ============================================================================
// OTP VERIFICATION - Required after login before accessing main app
// ============================================================================

Route::middleware('otp.pending')->group(function () {
    Route::get('/otp', [OtpController::class, 'show'])->name('otp.show');
    Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
    Route::post('/otp/resend', [OtpController::class, 'resend'])->name('otp.resend');
});

// ============================================================================
// MICROSOFT SSO - OAuth authentication flow
// ============================================================================

Route::controller(MicrosoftController::class)->group(function () {
    Route::get('auth/microsoft/redirect', 'redirectToMicrosoft')->name('auth.microsoft');
    Route::get('auth/microsoft/callback', 'handleMicrosoftCallback');
});

// ============================================================================
// AUTHENTICATED ROUTES - Require login
// ============================================================================

Route::middleware('auth')->group(function () {
    
    // Home - Redirect to HR Dashboard
    Route::get('/', fn() => redirect()->route('hr.dashboard'))->name('home');
    
    // Event Session Management (Global - used by all HR pages)
    Route::post('/event/select', [EventSessionController::class, 'setEvent'])->name('event.select');
    Route::post('/event/clear', [EventSessionController::class, 'clearEvent'])->name('event.clear');
    
    // User Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Test Page
    Route::inertia('mypage', 'Mypage')->name('mypage');
    
    // ========================================================================
    // HR MODULE ROUTES - Organized by access level
    // ========================================================================
    
    require __DIR__ . '/hr/employee.php';  // Employee-facing routes
    require __DIR__ . '/hr/manager.php';   // Manager approvals
    require __DIR__ . '/hr/admin.php';     // Admin management
    
    // ========================================================================
    // PAYROLL MODULE ROUTES - Payroll operations
    // ========================================================================
    
    require __DIR__ . '/payroll.php';      // Payroll dashboard, timesheet review, payments
    
    // ========================================================================
    // LEGACY ROUTES - Old event management system
    // ========================================================================
    
    require __DIR__ . '/legacy/ems.php';
});

// ============================================================================
// AUTHENTICATION ROUTES - Login, logout, register, password reset
// ============================================================================

require __DIR__ . '/auth.php';
