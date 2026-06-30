<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Standardize audit field names from creator_id to created_by
     */
    public function up(): void
    {
        // Designations table
        Schema::table('designations', function (Blueprint $table) {
            $table->renameColumn('creator_id', 'created_by');
        });
        
        // Departments table
        Schema::table('departments', function (Blueprint $table) {
            $table->renameColumn('creator_id', 'created_by');
        });
        
        // Add updated_by columns if they don't exist
        if (!Schema::hasColumn('designations', 'updated_by')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->integer('updated_by')->nullable()->after('created_by');
            });
        }
        
        if (!Schema::hasColumn('departments', 'updated_by')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->integer('updated_by')->nullable()->after('created_by');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove updated_by columns if they were added
        if (Schema::hasColumn('designations', 'updated_by')) {
            Schema::table('designations', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
        
        if (Schema::hasColumn('departments', 'updated_by')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('updated_by');
            });
        }
        
        // Rename back to creator_id
        Schema::table('designations', function (Blueprint $table) {
            $table->renameColumn('created_by', 'creator_id');
        });
        
        Schema::table('departments', function (Blueprint $table) {
            $table->renameColumn('created_by', 'creator_id');
        });
    }
};
