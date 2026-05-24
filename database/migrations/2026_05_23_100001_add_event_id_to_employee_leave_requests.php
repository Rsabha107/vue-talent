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
        Schema::table('employee_leave_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
            $table->index(['event_id', 'employee_id'], 'idx_event_employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_leave_requests', function (Blueprint $table) {
            $table->dropForeign(['event_id']);
            $table->dropIndex('idx_event_employee');
            $table->dropColumn('event_id');
        });
    }
};
