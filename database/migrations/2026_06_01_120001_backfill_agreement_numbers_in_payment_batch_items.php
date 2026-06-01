<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill agreement_number for existing payment batch items from employee_events
        DB::statement("
            UPDATE payment_batch_items pbi
            INNER JOIN employee_timesheets et ON pbi.timesheet_id = et.id
            INNER JOIN employee_events ee ON ee.employee_id = pbi.employee_id AND ee.event_id = et.event_id
            SET pbi.agreement_number = ee.agreement_number
            WHERE pbi.agreement_number IS NULL
        ");
    }

    public function down(): void
    {
        // No need to reverse this data migration
    }
};
