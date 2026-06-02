<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, remove duplicate entries (keep the first occurrence, delete the rest)
        DB::statement('
            DELETE t1 FROM payment_batch_items t1
            INNER JOIN payment_batch_items t2 
            WHERE t1.id > t2.id 
            AND t1.timesheet_id = t2.timesheet_id
        ');

        // Now add the unique constraint
        Schema::table('payment_batch_items', function (Blueprint $table) {
            // Add unique constraint on timesheet_id to prevent duplicates
            // A timesheet can only be in one payment batch
            $table->unique('timesheet_id', 'payment_batch_items_timesheet_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_batch_items', function (Blueprint $table) {
            $table->dropUnique('payment_batch_items_timesheet_id_unique');
        });
    }
};
