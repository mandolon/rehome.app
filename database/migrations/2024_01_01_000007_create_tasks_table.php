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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->index()->comment('e.g., TASK/REDLINE, PROGRESS/UPDATE');
            $table->string('status')->index()->default('open')->comment('open | complete');
            $table->bigInteger('assignee_id')->nullable()->index()->comment('references users(id)');
            $table->bigInteger('created_by_id')->index()->comment('references users(id)');
            $table->date('due_date')->nullable();
            $table->boolean('allow_client')->default(false)->comment('if true, visible in client view/summaries');
            $table->integer('files_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');

            // Foreign key constraints
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('assignee_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');

            // Composite indexes for performance
            $table->index(['project_id', 'category', 'status']);
            $table->index(['assignee_id']);
            $table->index(['due_date']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
