<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_team_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('expected_team_size')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index('is_active');
        });

        Schema::create('event_team_template_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('event_team_templates')->onDelete('cascade');
            $table->string('role_name', 100);
            $table->integer('suggested_count')->default(1);
            $table->boolean('is_required')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
            
            $table->index(['template_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_team_template_roles');
        Schema::dropIfExists('event_team_templates');
    }
};
