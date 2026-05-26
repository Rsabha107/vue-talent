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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->string('file_name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->integer('file_size'); // Bytes
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('uploaded_by');
            $table->text('description')->nullable();
            $table->tinyInteger('active_flag')->default(1);
            $table->timestamps();
            
            // Indexes only - no foreign key constraints due to compatibility issues
            $table->index('employee_id');
            $table->index(['employee_id', 'category_id']);
            $table->index('event_id');
            $table->index('category_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
