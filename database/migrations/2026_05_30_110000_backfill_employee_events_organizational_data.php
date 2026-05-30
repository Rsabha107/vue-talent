<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Backfill employee_events with organizational data from employees_all.
     * For existing event assignments, copy department, designation, manager, etc.
     * from the employee's current values in employees_all.
     */
    public function up(): void
    {
        // Copy organizational data from employees_all to employee_events
        DB::statement("
            UPDATE employee_events ee
            INNER JOIN employees_all e ON ee.employee_id = e.id
            SET 
                ee.agreement_number = e.agreement_number,
                ee.entity_id = e.entity_id,
                ee.contract_type_id = e.contract_type_id,
                ee.department_id = e.department_id,
                ee.designation_id = e.designation_id,
                ee.directorate_id = e.directorate_id,
                ee.functional_area_id = e.functional_area_id,
                ee.job_level_id = e.job_level_id,
                ee.reporting_to_id = e.reporting_to_id,
                ee.employee_type = e.employee_type,
                ee.salary_basis_id = e.salary_basis_id
            WHERE ee.agreement_number IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     * 
     * No rollback - data has been copied, not moved.
     * The organizational columns still exist in employees_all.
     */
    public function down(): void
    {
        // No-op: We don't clear the backfilled data on rollback
        // because the original data still exists in employees_all
    }
};
