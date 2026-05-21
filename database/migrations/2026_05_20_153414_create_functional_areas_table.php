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
        Schema::create('functional_areas', function (Blueprint $table) {
            $table->id();
            $table->string('fa_code', 45)->nullable();
            $table->string('title', 250);
            $table->string('focal_point_name', 45)->nullable();
            $table->string('focal_point_email', 45)->nullable();
            $table->integer('fax_id')->nullable();
            $table->integer('active_flag');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('functional_areas');
    }
};
