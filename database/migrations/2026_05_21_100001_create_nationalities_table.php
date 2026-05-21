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
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->integer('num_code')->nullable()->default(0);
            $table->string('alpha_2_code', 2)->nullable()->unique();
            $table->string('alpha_3_code', 3)->nullable()->unique();
            $table->string('en_short_name', 52)->nullable();
            $table->string('nationality', 39)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
