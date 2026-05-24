<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table tracks employee leave balances per event.
     * Separates master leave types from employee-specific allocations.
     */
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');  // Match employees_all.id which is signed INT
            $table->unsignedBigInteger('event_id')->nullable();  // Nullable to support global balances
            $table->unsignedBigInteger('leave_type_id');
            
            // Balance tracking
            $table->decimal('allocated_days', 8, 2)->default(0);
            $table->decimal('used_days', 8, 2)->default(0);
            $table->decimal('pending_days', 8, 2)->default(0);
            $table->decimal('available_days', 8, 2)->default(0);
            
            // Period tracking
            $table->integer('year')->default(date('Y'));
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            
            // Status
            $table->tinyInteger('active_flag')->default(1);
            
            // Audit
            $table->timestamps();
            $table->integer('created_by')->default(1);
            $table->integer('updated_by')->default(1);
            
            // Constraints
            $table->unique(['employee_id', 'event_id', 'leave_type_id', 'year'], 'unique_employee_event_leave_year');
            $table->foreign('employee_id')->references('id')->on('employees_all')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            // Note: Skipping FK for leave_type_id due to type mismatch - handle at application level
            
            // Indexes
            $table->index(['event_id', 'employee_id'], 'idx_event_employee');
            $table->index(['employee_id', 'leave_type_id'], 'idx_employee_leave_type');
            $table->index('leave_type_id', 'idx_leave_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};
