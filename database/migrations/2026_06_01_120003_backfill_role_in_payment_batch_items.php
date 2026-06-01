<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill role for existing payment batch items from employee_events + designations
        DB::statement("
            UPDATE payment_batch_items pbi
            INNER JOIN employee_timesheets et ON pbi.timesheet_id = et.id
            INNER JOIN employee_events ee ON ee.employee_id = pbi.employee_id AND ee.event_id = et.event_id
            INNER JOIN designations d ON d.id = ee.designation_id
            SET pbi.role = d.name
            WHERE pbi.role IS NULL AND ee.designation_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        // No need to reverse this data migration
    }
};
