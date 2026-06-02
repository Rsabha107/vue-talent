<?php

use App\Http\Controllers\MeridianHR\PayrollController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payroll Module Routes
|--------------------------------------------------------------------------
|
| Routes for payroll-admin role:
| - Dashboard - Payroll overview and stats
| - Timesheet Review - Stage 2 approval (after manager approval)
| - Payment Batches - Generate and manage payment batches
| - Bank Files - Generate bank payment files
| - Missing Timesheets - Track employees without submitted timesheets
|
| Route naming convention: payroll.*
| URL pattern: /payroll/*
| Middleware: auth, payroll.access (checks for payroll permissions)
|
*/

Route::prefix('payroll')->name('payroll.')->middleware(['auth'])->group(function () {
    
    // Payroll Dashboard
    Route::get('/', [PayrollController::class, 'dashboard'])->name('dashboard');
    
    // Timesheet Review (Stage 2 - Final Approval)
    Route::get('/timesheets/review', [PayrollController::class, 'timesheetReview'])->name('timesheets.review');
    Route::post('/timesheets/{id}/approve', [PayrollController::class, 'approveTimesheet'])->name('timesheets.approve');
    Route::post('/timesheets/{id}/reject', [PayrollController::class, 'rejectTimesheet'])->name('timesheets.reject');
    Route::post('/timesheets/bulk-approve', [PayrollController::class, 'bulkApproveTimesheets'])->name('timesheets.bulk-approve');
    Route::post('/timesheets/bulk-reject', [PayrollController::class, 'bulkRejectTimesheets'])->name('timesheets.bulk-reject');
    
    // Missing Timesheets Report
    Route::get('/timesheets/missing', [PayrollController::class, 'missingTimesheets'])->name('timesheets.missing');
    
    // All Timesheets (Read-Only View)
    Route::get('/timesheets/all', [PayrollController::class, 'allTimesheets'])->name('timesheets.all');
    
    // Payment Batches
    Route::get('/payment-batches', [PayrollController::class, 'paymentBatches'])->name('payment-batches');
    Route::post('/payment-batches', [PayrollController::class, 'createPaymentBatch'])->name('payment-batches.store');
    Route::get('/payment-batches/{id}', [PayrollController::class, 'showPaymentBatch'])->name('payment-batches.show');
    Route::get('/payment-batches/{id}/export', [PayrollController::class, 'exportPaymentBatch'])->name('payment-batches.export');
    Route::post('/payment-batches/{id}/finalize', [PayrollController::class, 'finalizePaymentBatch'])->name('payment-batches.finalize');
    Route::post('/payment-batches/{id}/process', [PayrollController::class, 'processPaymentBatch'])->name('payment-batches.process');
    Route::post('/payment-batches/bulk-process', [PayrollController::class, 'bulkProcessPaymentBatches'])->name('payment-batches.bulk-process');
    Route::delete('/payment-batches/{id}', [PayrollController::class, 'deletePaymentBatch'])->name('payment-batches.destroy');
    
    // Payment Batch Items (Edit/Add/Delete)
    Route::post('/payment-batches/{id}/items', [PayrollController::class, 'addPaymentBatchItem'])->name('payment-batches.items.store');
    Route::put('/payment-batches/{batchId}/items/{itemId}', [PayrollController::class, 'updatePaymentBatchItem'])->name('payment-batches.items.update');
    Route::delete('/payment-batches/{batchId}/items/{itemId}', [PayrollController::class, 'deletePaymentBatchItem'])->name('payment-batches.items.destroy');
    
    // Bank Files
    Route::get('/bank-files', [PayrollController::class, 'bankFiles'])->name('bank-files');
    Route::post('/bank-files/generate', [PayrollController::class, 'generateBankFile'])->name('bank-files.generate');
    Route::get('/bank-files/{id}/download', [PayrollController::class, 'downloadBankFile'])->name('bank-files.download');
    Route::delete('/bank-files/{id}', [PayrollController::class, 'destroyBankFile'])->name('bank-files.destroy');
});
