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
        Schema::create('employee_banks', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N');
            $table->integer('employee_id');
            $table->unsignedBigInteger('user_id');
            $table->string('bank_branch_name', 100);
            $table->string('bank_account_name', 100);
            $table->string('iban', 50);
            $table->string('swift_code', 50);
            $table->date('effective_start_date')->nullable();
            $table->date('effective_end_date')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_banks');
    }
};
