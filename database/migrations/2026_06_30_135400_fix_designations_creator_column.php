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
        Schema::table('designations', function (Blueprint $table) {
            // Rename creator_id to created_by (if it exists)
            if (Schema::hasColumn('designations', 'creator_id')) {
                $table->renameColumn('creator_id', 'created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('designations', function (Blueprint $table) {
            // Rename back to creator_id
            if (Schema::hasColumn('designations', 'created_by')) {
                $table->renameColumn('created_by', 'creator_id');
            }
        });
    }
};
