<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get current authenticated user (assuming you'll manually set this)
// For testing, let's get the first user or you can specify a user_id
$userId = $argv[1] ?? 1; // Pass user ID as argument

$employee = App\Models\Employee::where('user_id', $userId)->first();

if (!$employee) {
    echo "No employee found for user ID {$userId}\n";
    exit;
}

echo "Employee: {$employee->full_name} (ID: {$employee->id})\n";
echo "========================================\n\n";

$approvedStatusId = App\Models\EmployeeLeaveStatus::approvedId();

$leaves = App\Models\EmployeeLeaveRequest::active()
    ->with('leaveType')
    ->where('status_id', $approvedStatusId)
    ->where('employee_id', $employee->id)
    ->get();

echo "Approved Leaves:\n";
echo "----------------\n";
foreach ($leaves as $leave) {
    $leaveTypeName = $leave->leaveType->title ?? 'Unknown';
    $isPaid = ($leave->leaveType && $leave->leaveType->isPaid()) ? 'PAID (L)' : 'UNPAID (U)';
    $days = $leave->date_from->diffInDays($leave->date_to) + 1;
    
    echo "Leave ID {$leave->id}:\n";
    echo "  Type: {$leaveTypeName} -> {$isPaid}\n";
    echo "  Dates: {$leave->date_from->format('Y-m-d')} to {$leave->date_to->format('Y-m-d')} ({$days} days)\n";
    echo "  Event: " . ($leave->event->name ?? 'N/A') . "\n";
    echo "\n";
}

echo "\nTotal approved leaves: " . $leaves->count() . "\n";
