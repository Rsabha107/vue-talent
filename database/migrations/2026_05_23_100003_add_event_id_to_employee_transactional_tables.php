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
        // Add event_id to employee_attachments
        if (Schema::hasTable('employee_attachments')) {
            Schema::table('employee_attachments', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
                $table->index('event_id');
            });
        }

        // Add event_id to employee_files
        if (Schema::hasTable('employee_files')) {
            Schema::table('employee_files', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
                $table->index('event_id');
            });
        }

        // Add event_id to employee_letters
        if (Schema::hasTable('employee_letters')) {
            Schema::table('employee_letters', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
                $table->index('event_id');
            });
        }

        // Add event_id to employee_generated_letters
        if (Schema::hasTable('employee_generated_letters')) {
            Schema::table('employee_generated_letters', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
                $table->index('event_id');
            });
        }

        // Add event_id to employee_salary (optional - depends on if salary is event-specific)
        if (Schema::hasTable('employee_salary')) {
            Schema::table('employee_salary', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('employee_id');
                $table->foreign('event_id')->references('id')->on('events')->onDelete('set null');
                $table->index('event_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('employee_attachments')) {
            Schema::table('employee_attachments', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['event_id']);
                $table->dropColumn('event_id');
            });
        }

        if (Schema::hasTable('employee_files')) {
            Schema::table('employee_files', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['event_id']);
                $table->dropColumn('event_id');
            });
        }

        if (Schema::hasTable('employee_letters')) {
            Schema::table('employee_letters', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['event_id']);
                $table->dropColumn('event_id');
            });
        }

        if (Schema::hasTable('employee_generated_letters')) {
            Schema::table('employee_generated_letters', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['event_id']);
                $table->dropColumn('event_id');
            });
        }

        if (Schema::hasTable('employee_salary')) {
            Schema::table('employee_salary', function (Blueprint $table) {
                $table->dropForeign(['event_id']);
                $table->dropIndex(['event_id']);
                $table->dropColumn('event_id');
            });
        }
    }
};
