<?php

use App\Http\Controllers\Ems\EventController;
use App\Http\Controllers\Ems\FunctionalAreaController;
use App\Http\Controllers\Ems\VenueController;
use App\Http\Controllers\GlobalStatusController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolesPermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserExportController;
use Illuminate\Support\Facades\Route;

/**
 * Legacy EMS (Event Management System) Routes
 * These routes support the legacy Bootstrap UI
 * Consider migrating to Meridian HR equivalents
 */
Route::middleware(['auth'])->group(function () {
    
    // User Management (Legacy)
    Route::get('/users/export', [UserExportController::class, 'export'])->name('users.export');
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index')->name('users.index');
        Route::get('/api/users', 'data')->name('users.data');
        Route::get('/api/users/{user}/roles', 'roles')->name('users.roles');
        Route::post('/users', 'store')->name('users.store');
        Route::put('/users/{user}', 'update')->name('users.update');
        Route::delete('/users/{user}', 'destroy')->name('users.destroy');
    });
    
    // Permission Management (Legacy)
    Route::controller(PermissionController::class)->group(function () {
        Route::get('/permissions', 'index')->name('permissions.index');
        Route::get('/api/permissions', 'data')->name('permissions.data');
        Route::post('/permissions', 'store')->name('permissions.store');
        Route::get('/permissions/{permission}', 'show')->name('permissions.show');
        Route::put('/permissions/{permission}', 'update')->name('permissions.update');
        Route::delete('/permissions/{permission}', 'destroy')->name('permissions.destroy');
    });
    
    // Role Management (Legacy)
    Route::controller(RoleController::class)->group(function () {
        Route::get('/roles', 'index')->name('roles.index');
        Route::get('/api/roles', 'data')->name('roles.data');
        Route::post('/roles', 'store')->name('roles.store');
        Route::put('/roles/{role}', 'update')->name('roles.update');
        Route::delete('/roles/{role}', 'destroy')->name('roles.destroy');
    });
    
    // Roles-Permissions (Legacy)
    Route::controller(RolesPermissionController::class)->group(function () {
        Route::get('/roles-permissions', 'index')->name('roles-permissions.index');
        Route::get('/api/roles-permissions', 'data')->name('roles-permissions.data');
        Route::get('/api/roles-permissions/all-permissions', 'allPermissions')->name('roles-permissions.all-permissions');
        Route::get('/api/roles-permissions/all-roles', 'allRoles')->name('roles-permissions.all-roles');
        Route::put('/api/roles-permissions/{role}', 'syncPermissions')->name('roles-permissions.sync');
        Route::delete('/api/roles-permissions/{role}', 'destroy')->name('roles-permissions.destroy');
    });
    
    // Event Management (Legacy)
    Route::controller(EventController::class)->group(function () {
        Route::get('/events', 'index')->name('events.index');
        Route::get('/api/events', 'data')->name('events.data');
        Route::post('/events', 'store')->name('events.store');
        Route::get('/events/{event}', 'show')->name('events.show');
        Route::match(['put', 'post'], '/events/{event}', 'update')->name('events.update');
        Route::delete('/events/{event}', 'destroy')->name('events.destroy');
    });
    
    // Venue Management (Legacy)
    Route::controller(VenueController::class)->group(function () {
        Route::get('/venues', 'index')->name('venues.index');
        Route::get('/api/venues', 'data')->name('venues.data');
        Route::get('/api/venues/all', 'all')->name('venues.all');
        Route::post('/venues', 'store')->name('venues.store');
        Route::get('/venues/{venue}', 'show')->name('venues.show');
        Route::match(['put', 'post'], '/venues/{venue}', 'update')->name('venues.update');
        Route::delete('/venues/{venue}', 'destroy')->name('venues.destroy');
    });
    
    // Functional Area Management (Legacy)
    Route::controller(FunctionalAreaController::class)->group(function () {
        Route::get('/functional-areas', 'index')->name('functional-areas.index');
        Route::get('/api/functional-areas', 'data')->name('functional-areas.data');
        Route::get('/api/functional-areas/all', 'all')->name('functional-areas.all');
        Route::post('/functional-areas', 'store')->name('functional-areas.store');
        Route::get('/functional-areas/{functionalArea}', 'show')->name('functional-areas.show');
        Route::match(['put', 'post'], '/functional-areas/{functionalArea}', 'update')->name('functional-areas.update');
        Route::delete('/functional-areas/{functionalArea}', 'destroy')->name('functional-areas.destroy');
    });
    
    // Global Statuses (Legacy)
    Route::get('/statuses', [GlobalStatusController::class, 'getStatuses'])->name('global.statuses.get');
});
