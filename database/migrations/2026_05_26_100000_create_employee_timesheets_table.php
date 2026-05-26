<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_timesheets', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N')->nullable();
            $table->integer('employee_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('event_id')->nullable();
            $table->integer('month_id');
            $table->string('year', 10);
            $table->string('timesheet_period', 20);
            $table->integer('days_in_month');
            $table->integer('status_id');
            $table->unsignedInteger('performer_id')->nullable();
            $table->string('additional_information', 4000)->nullable();
            $table->integer('payroll_approval_id')->nullable();
            $table->string('payroll_additional_information', 4000)->nullable();
            $table->integer('days_worked')->nullable();
            $table->integer('leave_taken')->nullable();
            $table->integer('unpaid_leave_taken')->nullable();
            $table->integer('total_days_eligible_for_payment')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->decimal('daily_rate', 15, 2)->nullable();
            $table->decimal('total_payment', 15, 2)->nullable();
            $table->integer('bank_id')->nullable();
            $table->string('entries_exists', 5)->nullable();
            $table->tinyInteger('payroll_reviewed')->default(0)->nullable();
            $table->string('note_1', 4000)->nullable();
            $table->string('note_2', 4000)->nullable();
            $table->integer('creator_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_timesheets');
    }
};
