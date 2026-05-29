<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->string('batch_name', 200);
            $table->integer('event_id')->nullable();
            $table->string('period', 50); // e.g., "May 2026"
            $table->integer('month_id');
            $table->string('year', 10);
            $table->string('status', 20)->default('draft'); // draft, finalized, processed
            $table->integer('timesheet_count')->default(0);
            $table->integer('employee_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->date('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->date('processed_at')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('finalized_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('restrict');
        });

        Schema::create('payment_batch_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_batch_id');
            $table->unsignedBigInteger('timesheet_id');
            $table->integer('employee_id');
            $table->string('employee_number', 50)->nullable();
            $table->string('employee_name', 200);
            $table->integer('bank_id')->nullable();
            $table->string('account_number', 50)->nullable();
            $table->integer('days_worked')->default(0);
            $table->integer('leave_taken')->default(0);
            $table->integer('unpaid_leave_taken')->default(0);
            $table->integer('total_days_paid')->default(0);
            $table->decimal('daily_rate', 15, 2)->default(0);
            $table->decimal('payment_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('payment_batch_id')->references('id')->on('payment_batches')->onDelete('cascade');
            $table->foreign('timesheet_id')->references('id')->on('employee_timesheets')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_batch_items');
        Schema::dropIfExists('payment_batches');
    }
};
