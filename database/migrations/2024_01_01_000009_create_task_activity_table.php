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
        Schema::create('task_activity', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id')->index();
            $table->bigInteger('user_id')->index()->comment('references users(id)');
            $table->string('action_type')->index()->comment('created | updated | completed | commented | file_attached | file_removed | assigned | unassigned');
            $table->text('comment')->nullable()->comment('user comment or system message');
            $table->json('metadata')->nullable()->comment('action-specific data (file info, field changes, etc.)');
            $table->boolean('is_system')->default(false)->comment('true for system-generated activities');
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            // Foreign key constraints
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes for performance
            $table->index(['task_id', 'action_type']);
            $table->index(['task_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['is_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_activity');
    }
};
