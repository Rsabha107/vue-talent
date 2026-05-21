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
        Schema::create('employee_sponsorship', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N')->nullable();
            $table->string('title', 100);
            $table->integer('active_flag');
            $table->integer('created_by');
            $table->integer('updated_by');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_sponsorship');
    }
};
