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
        Schema::create('employee_salary', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N')->nullable();
            $table->integer('employee_id');
            $table->integer('payroll_cycle_id')->nullable();
            $table->double('net_salary');
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->integer('active_flag')->nullable();
            $table->integer('creator_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary');
    }
};
