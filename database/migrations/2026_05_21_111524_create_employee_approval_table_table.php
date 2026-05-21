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
        Schema::create('employee_approval_table', function (Blueprint $table) {
            $table->id();
            $table->string('object_name', 100);
            $table->integer('object_id');
            $table->integer('sequence_number');
            $table->integer('performer_id');
            $table->integer('action_code_id');
            $table->dateTime('action_date')->useCurrent();
            $table->string('additional_information', 4000)->nullable();
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
        Schema::dropIfExists('employee_approval_table');
    }
};
