<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('employee_timesheet_status')
            ->where('title', 'Pending')
            ->update(['title' => 'Saved']);

        cache()->forget('timesheet_status_Pending');
        cache()->forget('timesheet_status_Saved');
    }

    public function down(): void
    {
        DB::table('employee_timesheet_status')
            ->where('title', 'Saved')
            ->update(['title' => 'Pending']);

        cache()->forget('timesheet_status_Saved');
        cache()->forget('timesheet_status_Pending');
    }
};
