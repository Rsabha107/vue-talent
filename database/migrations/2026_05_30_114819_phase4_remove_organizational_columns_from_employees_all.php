<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Phase 4: Remove organizational columns from employees_all table.
     * These fields are now stored in employee_events pivot table to support
     * event-specific organizational structures (different department, manager,
     * designation per event).
     */
    public function up(): void
    {
        Schema::table('employees_all', function (Blueprint $table) {
            $table->dropColumn([
                'agreement_number',
                'salary_basis_id',
                'employee_type',
                'reporting_to_id',
                'department_id',
                'designation_id',
                'directorate_id',
                'functional_area_id',
                'job_level_id',
                'entity_id',
                'contract_type_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     * 
     * Restore columns to employees_all table (for rollback scenarios).
     */
    public function down(): void
    {
        Schema::table('employees_all', function (Blueprint $table) {
            $table->string('agreement_number', 45)->nullable();
            $table->integer('salary_basis_id')->nullable();
            $table->integer('employee_type')->nullable();
            $table->integer('entity_id')->nullable();
            $table->integer('contract_type_id')->nullable();
            $table->integer('reporting_to_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('designation_id')->nullable();
            $table->integer('directorate_id')->nullable();
            $table->integer('functional_area_id')->nullable();
            $table->integer('job_level_id')->nullable();
        });
    }
};
