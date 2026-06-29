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
        Schema::table('employee_banks', function (Blueprint $table) {
            $table->dropColumn('bank_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_banks', function (Blueprint $table) {
            $table->string('bank_account_name', 100)->nullable()->after('bank_branch_name');
        });
    }
};
