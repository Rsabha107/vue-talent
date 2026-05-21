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
        Schema::create('employee_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('archived', 5)->default('N');
            $table->integer('address_type');
            $table->string('primary_address', 5)->default('N')->nullable();
            $table->integer('employee_id');
            $table->string('address1', 250);
            $table->string('address2', 250)->nullable();
            $table->string('city', 100);
            $table->string('state', 10)->nullable();
            $table->string('zipcode', 15)->nullable();
            $table->integer('country_id');
            $table->integer('creator_id');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_addresses');
    }
};
