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
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('project_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false); // optional: project lead gets all tasks in project
            $table->timestamps();

            // Foreign key to projects
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();

            // Prevent duplicate assignments
            $table->unique(['project_id', 'user_id']);
            
            // Indexes for performance
            $table->index('project_id');
            $table->index('user_id');
            $table->index(['project_id', 'is_lead']); // for finding project leads
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
