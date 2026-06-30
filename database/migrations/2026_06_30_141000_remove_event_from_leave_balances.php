<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows — keep the record with the lowest id per
        // (employee_id, leave_type_id, year) so the new unique constraint can be applied.
        DB::statement('
            DELETE FROM employee_leave_balances
            WHERE id NOT IN (
                SELECT * FROM (
                    SELECT MIN(id)
                    FROM employee_leave_balances
                    GROUP BY employee_id, leave_type_id, year
                ) AS keep_ids
            )
        ');

        // Nullify event_id — balances are now global, not per-event.
        DB::table('employee_leave_balances')->update(['event_id' => null]);

        Schema::table('employee_leave_balances', function (Blueprint $table) {
            // Drop the old event-scoped unique constraint
            $table->dropUnique('unique_employee_event_leave_year');

            // One balance record per employee per leave type per year
            $table->unique(['employee_id', 'leave_type_id', 'year'], 'unique_employee_leave_year');
        });
    }

    public function down(): void
    {
        Schema::table('employee_leave_balances', function (Blueprint $table) {
            $table->dropUnique('unique_employee_leave_year');
            $table->unique(
                ['employee_id', 'event_id', 'leave_type_id', 'year'],
                'unique_employee_event_leave_year'
            );
        });
    }
};
