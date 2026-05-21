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
        Schema::create('employee_leave_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N');
            $table->integer('leave_id');
            $table->integer('employee_id');
            $table->string('file_name', 250);
            $table->string('original_file_name', 150);
            $table->string('file_extension', 10);
            $table->integer('file_size');
            $table->string('file_path', 150)->nullable();
            $table->integer('user_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_leave_attachments');
    }
};
