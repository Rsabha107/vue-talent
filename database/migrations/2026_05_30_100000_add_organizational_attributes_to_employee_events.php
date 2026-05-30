<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Move organizational/role attributes from employees_all to employee_events
     * to support event-specific organizational structure (employees can have
     * different departments, managers, etc. across different events).
     */
    public function up(): void
    {
        Schema::table('employee_events', function (Blueprint $table) {
            // Drop old columns that will be replaced
            $table->dropColumn(['event_role', 'event_department_id']);
            
            // Event-specific organizational attributes
            $table->string('agreement_number', 45)->nullable()->after('released_at');
            $table->integer('entity_id')->nullable()->after('agreement_number');
            $table->integer('contract_type_id')->nullable()->after('entity_id');
            $table->integer('department_id')->nullable()->after('contract_type_id');
            $table->integer('designation_id')->nullable()->after('department_id');
            $table->integer('directorate_id')->nullable()->after('designation_id');
            $table->integer('functional_area_id')->nullable()->after('directorate_id');
            $table->integer('job_level_id')->nullable()->after('functional_area_id');
            $table->integer('reporting_to_id')->nullable()->after('job_level_id')->comment('Manager for this event assignment');
            $table->integer('employee_type')->nullable()->after('reporting_to_id');
            $table->integer('salary_basis_id')->nullable()->after('employee_type');
            
            // Add indexes for frequent lookups
            $table->index('department_id', 'idx_event_department');
            $table->index('designation_id', 'idx_event_designation');
            $table->index('reporting_to_id', 'idx_event_manager');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_events', function (Blueprint $table) {
            // Drop the new columns
            $table->dropIndex('idx_event_department');
            $table->dropIndex('idx_event_designation');
            $table->dropIndex('idx_event_manager');
            
            $table->dropColumn([
                'agreement_number',
                'entity_id',
                'contract_type_id',
                'department_id',
                'designation_id',
                'directorate_id',
                'functional_area_id',
                'job_level_id',
                'reporting_to_id',
                'employee_type',
                'salary_basis_id',
            ]);
            
            // Restore old columns
            $table->string('event_role')->nullable();
            $table->unsignedBigInteger('event_department_id')->nullable();
        });
    }
};
