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
        Schema::create('employee_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N')->nullable();
            $table->integer('employee_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('leave_type_id');
            $table->integer('number_of_days');
            $table->date('date_from');
            $table->date('date_to');
            $table->text('reason');
            $table->integer('status_id');
            $table->unsignedInteger('performer_id')->nullable();
            $table->string('additional_information', 4000)->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_requests');
    }
};
