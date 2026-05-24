<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_events', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');  // Match employees_all.id which is signed INT
            $table->unsignedBigInteger('event_id');
            
            // Assignment dates
            $table->date('assigned_at');
            $table->date('released_at')->nullable();
            
            // Event-specific role/position
            $table->string('event_role')->nullable();
            $table->unsignedBigInteger('event_department_id')->nullable();
            
            // Status
            $table->tinyInteger('is_active')->default(1);
            
            // Audit
            $table->timestamps();
            $table->integer('created_by')->default(1);
            $table->integer('updated_by')->default(1);
            
            // Constraints
            $table->unique(['employee_id', 'event_id'], 'unique_employee_event');
            $table->foreign('employee_id')->references('id')->on('employees_all')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            
            // Indexes
            $table->index(['event_id', 'is_active'], 'idx_event_active');
            $table->index(['employee_id', 'is_active'], 'idx_employee_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_events');
    }
};
