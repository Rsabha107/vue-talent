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
        Schema::create('employees_all', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->string('archived', 5)->default('N');
            $table->string('employee_number', 15);
            $table->string('agreement_number', 45)->nullable();
            $table->integer('salary_basis_id')->nullable();
            $table->string('national_identifier_number', 100)->nullable();
            $table->integer('salutation_id')->nullable();
            $table->string('first_name', 50)->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name', 50);
            $table->string('full_name', 500);
            $table->string('gender_id', 11)->nullable();
            $table->integer('marital_status_id')->nullable();
            $table->integer('employee_type')->nullable();
            $table->integer('entity_id')->nullable();
            $table->integer('contract_type_id')->nullable();
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->string('sponsorship_id', 150)->nullable();
            $table->string('sponsorship_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('date_of_hire')->nullable();
            $table->date('join_date')->nullable();
            $table->string('town_of_birth', 100)->nullable();
            $table->string('country_of_birth', 11)->nullable();
            $table->string('personal_email_address', 240)->nullable();
            $table->string('work_email_address', 250);
            $table->string('phone_number', 50)->nullable();
            $table->string('alt_phone_number', 50)->nullable();
            $table->string('phone_area_code', 10)->nullable();
            $table->string('alt_area_code', 10)->nullable();
            $table->integer('nationality_id')->nullable();
            $table->integer('language_id')->nullable();
            $table->integer('reporting_to_id')->nullable();
            $table->integer('department_id')->nullable();
            $table->integer('designation_id')->nullable();
            $table->integer('directorate_id')->nullable();
            $table->integer('functional_area_id')->nullable();
            $table->integer('job_level_id')->nullable();
            $table->date('civil_id_expiry')->nullable();
            $table->string('passport_number', 50)->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('manager_flag', 5)->nullable();
            $table->string('administrator_flag', 5)->default('N')->nullable();
            $table->string('profile_photo', 250)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees_all');
    }
};
