<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_timesheet_id');
            $table->integer('employee_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('calendar_day');
            $table->string('day_action', 11);
            $table->timestamps();

            $table->foreign('employee_timesheet_id')
                  ->references('id')->on('employee_timesheets')
                  ->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_timesheet_entries');
    }
};
