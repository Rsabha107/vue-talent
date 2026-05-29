<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payment_batch_id');
            $table->string('file_name', 200);
            $table->string('file_path', 500);
            $table->string('file_format', 20)->default('csv'); // csv, txt, xml
            $table->integer('record_count')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('generation_notes')->nullable();
            $table->unsignedBigInteger('generated_by');
            $table->timestamps();

            $table->foreign('payment_batch_id')->references('id')->on('payment_batches')->onDelete('restrict');
            $table->foreign('generated_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_files');
    }
};
